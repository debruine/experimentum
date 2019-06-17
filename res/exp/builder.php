<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
require_once DOC_ROOT . '/include/classes/exp.php';
auth($RES_STATUS);

if (validID($_GET['id']) && !permit('exp', $_GET['id'])) header('Location: /res/');

function imgname($src) {
    $name = str_replace('/stimuli', '', $src);
    $name = str_replace('.jpg', '', $name);
    $name = str_replace('.ogg', '', $name);
    $name = str_replace('.mp3', '', $name);
    return $name;
}

$title = array(
    '/res/' => 'Researchers',
    '/res/exp/' => 'Experiment',
    '/res/exp/builder' => 'Builder'
);

$styles = array(
    'form' => 'max-width: 100%; width:100%;',
    'table#experiment_builder' => 'table-layout: auto !important;',
    '.jnd .input_interface td, tr.ranking' => '-moz-user-select: text; -webkit-user-select: text; -ms-user-select: text;',
    '#motiv_info' => 'position: absolute; 
                      text-align: left; 
                      border: 5px solid white; 
                      border-radius: 10px; 
                      box-shadow: 4px 4px 6px rgba(0,0,0,0.5); 
                      padding: 1em 0; 
                      background-color: '. THEME .'; 
                      color: white;',
    '#button_display td'  => 'text-align: center;',
    '.button-wrapper' => 'display: inline-block; 
                          font-size: 150%; 
                          padding: 0;
                          line-height: 1.8em; 
                          min-height: 1.8em; 
                          min-width: 1.8em; 
                          border-radius: .25em; 
                          margin: 0 .1em; 
                          border: 1px solid '. THEME .'; 
                          background-color: #e3e6e8; 
                          box-shadow: 2px 2px 4px rgba(0,0,0,.5);',
    '#trial_builder, #image_chooser' => 'font-size: 80%; overflow:auto;',
    '#trial_builder img' => 'display: inline-block; min-width: 80px; min-height: 30px;',
    '#trial_builder img.trialimg, #img_list img, #trial_builder  #dv span' => 'width: 80px; border: 1px solid ' . THEME . '; margin: 3px;',
    '#trial_builder span.imgname' => 'display: none;',
    '#trial_builder.list img' => 'display: none;',
    '#trial_builder.list span.imgname, #trial_builder.list #dv span' => 'display: inline-block; border: 1px solid ' . THEME . '; min-height: 1em; width: 170px; padding: 5px; margin: 2px;',
    '#dv span' => 'background-color:' . THEME . '; color: white; text-align: center; display: inline-block; ',
    '#img_list img' => 'box-shadow: 2px 2px 4px rgba(0,0,0,.5);',
    '#img_list li:hover' => 'cursor: move;',
    '#img_list li' => 'padding:1px; margin:0; max-width: 30em;',
    '#image_search' => 'width: 300px;',
    '#listfill' => 'width: 100%; height: 150px;',
    '#trial_builder .img_column' => 'width: 80px;',
    '#trial_builder.list .img_column' => 'width: auto;',
    '#image_toggle a, #list_toggle a' => 'color: #999;',
    '#images_found' => 'padding-left: 1em;',
    '#search-bar' => 'width: 48%; text-align: right;',
    '#search-bar input' => 'width: 70%;',
    '.drop_hover' => 'border: 1px solid red !important;',
    '.trial' => 'border-bottom: 3px solid ' . THEME,
    '.trial p' => 'margin:0; padding:0; clear: left;',
    '.label_list' => 'display: inline-block; width: 120px;',
    '.label_list li' => 'padding:0; margin-left: 22px; font-size: 90%;',
    '.trial_icons' => '',
    '.trial_icons a' => 'border: none;',
    'audio' => 'width: 100px; height: 30px; padding-top: 5px;'
);

/****************************************************
 * AJAX Responses
 ***************************************************/

if (array_key_exists('search', $_GET)) {
    $searches = str_replace(array(' & ',    ' | ',      ' and ',    ' or '), 
                            array(' AND ',  ' OR ',     ' AND ',    ' OR '),
                            $_POST['image_search']);
                           
    $searches = my_clean($searches); // clean up search string
    
    $search_strings = preg_split("/( AND | OR |\(|\))/", $searches);
    
    $search_terms = array();
    foreach($search_strings as $string) {
        $term = trim($string);
        if (!empty($term)) {
            if (substr($term, 0, 1) =='!') {  // negate a term
                $search_terms[$term] = '(!LOCATE("' . substr($term,1) . '", path))';
            } else {
                $search_terms[$term] = '(LOCATE("' . $term . '", path) OR LOCATE("' . $term . '", description))';
            }
        }
    }
    
    $s = $searches;
    foreach ($search_terms as $term => $locate) {
        $s = str_replace($term, $locate, $s);
    }
    $s = "(LEFT(path,17) != '/stimuli/uploads/' OR LOCATE('/stimuli/uploads/{$_SESSION['user_id']}/', path)=1) AND {$s}";

    $query = new myQuery('SELECT id, CONCAT(id,":",path,":",type) as path FROM stimuli WHERE ' . $s . ' ORDER BY stimuli.path LIMIT 2000');
    $images = $query->get_key_val('id', 'path');
    
    //echo 'error ' . $query->get_query(); // debug query
    echo implode(';', $images);
    exit;
}

 
if (array_key_exists('save', $_GET)) {
    $clean = my_clean($_POST);
    
    // update exp table
    $exp_query = sprintf('REPLACE INTO exp 
        (id, name, res_name, exptype, subtype, design, trial_order, side,
        instructions, question, label1, label2, label3, label4, rating_range, low_anchor, high_anchor,
        feedback_query, feedback_specific, feedback_general, labnotes, 
        sex, lower_age, upper_age, 
        total_stim, random_stim, default_time, increment_time, create_date)  
        VALUES (%s, "%s", "%s", "%s", "%s", "%s", "%s", "%s", 
        "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", 
        "%s", "%s", "%s","%s", 
        "%s", "%s", "%s", 
        "%s", "%s", "%s", "%s", NOW())',
        (validID($clean['id'])) ? $clean['id'] : 'NULL',
        $clean['name'], 
        $clean['res_name'], 
        $clean['exptype'], 
        $clean['subtype'],
        $clean['design'],
        $clean['trial_order'],
        $clean['side'],
        $clean['i_instructions'],
        $clean['i_trial_question'],
        $clean['i_label1'],
        $clean['i_label2'],
        $clean['i_label3'],
        $clean['i_label4'],
        $clean['rating_range'],
        $clean['i_low_anchor'],
        $clean['i_high_anchor'],
        $clean['feedback_query'], 
        $clean['feedback_specific'], 
        $clean['i_feedback_general'], 
        $clean['labnotes'], 
        $clean['sex'],
        $clean['lower_age'], 
        $clean['upper_age'],
        $clean['total_stim'], 
        $clean['random_stim'], 
        $clean['default_time'],
        $clean['increment_time']
    );
    $exp_query = str_replace('""', 'NULL', $exp_query);
    $exp_query = str_replace('"NULL"', 'NULL', $exp_query);
    
    $exp = new myQuery($exp_query);
    
    // update access table if this is a new experiment
    if (!validID($clean['id'])) {
        $exp_id = $exp->get_insert_id();
        if ($exp_id > 0) {
            $update_access = new myQuery('REPLACE INTO access (type, id, user_id) 
                                          VALUES ("exp", '.$exp_id.', '.$_SESSION['user_id'].')');
        }
    } else {
        $exp_id = $clean['id'];
    }
    
    // delete extra trials if some have been deleted from a previous version
    $query = new myQuery("DELETE FROM trial WHERE exp_id={$exp_id} AND trial_n>{$clean['total_stim']}");
    $query = new myQuery("DELETE FROM xafc WHERE exp_id={$exp_id} AND trial_n>{$clean['total_stim']}");
    
    // save buttons if a buttons experiment
    if ($clean['exptype'] == 'buttons') {
        $dropbuttons = new myQuery('DELETE FROM buttons WHERE exp_id=' . $exp_id);
        
        $buttons = array();
        for ($i = 1; $i < 20; $i++) {
            if (isset($clean['i_button' . $i])) {
                $buttons[] = sprintf('(%s, "%s", "%s", %d)', 
                    $exp_id,
                    $clean['i_button-dv' . $i],
                    $clean['i_button' . $i],
                    $i
                );
            } else {
                break;
            }
        }
        
        $addbuttons = new myQuery('INSERT INTO buttons (exp_id, dv, display, n) VALUES ' . implode(', ', $buttons));
    }
    
    echo "id:$exp_id";
    //echo $exp_query;
    
    exit;
}
 
$exp_id=$_GET['id'];


/****************************************************
 * Other experiment Information
 ***************************************************/
 
$info = array();

$exptype_options = array(
    '2afc' => '2-Alternative Forced-choice (2AFC)',
    'jnd' => '2AFC with 8-Button Strength of Choice',
    'slider' => 'Slider',
    'rating' => 'Numeric Rating',
    'buttons' => 'Labelled Buttons',
    'slideshow' => 'Slideshow',
    'xafc' => 'X-alternative forced choice (XAFC)',
    'sort' => 'Sorting',
    //'motivation' => 'Motivation'
);

$default_nImages = array(
    '2afc' => 2,
    'jnd' => 2,
    'slider' => 1,
    'rating' => 1,
    'buttons' => 1,
    'slideshow' => 1,
    'xafc' => 3,
    'sort' => 5,
    //'motivation' => '1
);

$eInfo = array();
if (validID($exp_id)) {
    if (!permit('exp', $exp_id)) header('Location: /res/exp/');
    //$myexp = new experiment($exp_id);
    $query = new myQuery('SELECT * FROM exp WHERE id=' . $exp_id);
    $eInfo = $query->get_assoc(0);
    
    if ($eInfo['exptype'] == 'xafc') {
        $query = new myQuery("SELECT 
        COUNT(DISTINCT trial_n) as trials, 
        COUNT(DISTINCT n) as nimages
        FROM xafc 
        WHERE exp_id={$exp_id}
        GROUP BY NULL");
    } else {
        $query = new myQuery("SELECT 
        COUNT(*) as trials, 
        (COUNT(left_img)>0) + (COUNT(center_img)>0) + (COUNT(right_img)>0) as nimages 
        FROM trial 
        WHERE exp_id={$exp_id}
        GROUP BY NULL");
    }
    $trialInfo = $query->get_assoc(0);
    
    if ($query->get_num_rows() == 0) {
        $eInfo['total_stim'] = $eInfo['random_stim'];
    } else {
        $eInfo['total_stim'] = $trialInfo['trials'];
    }
    
    if (is_array($trialInfo) && array_key_exists('nimages', $trialInfo)) {
        $nImages = $trialInfo['nimages'];
    }
    
    if ($eInfo['exptype'] == 'buttons') {
        $button_query = new myQuery('SELECT dv, display FROM buttons WHERE exp_id=' . $eInfo['id'] . " ORDER BY n");
        $buttons = $button_query->get_key_val('dv', 'display');
    }
    
    // get existing trial info
    $query = new myQuery();
    $query->prepare('SELECT trial_n, name, label1, label2, label3, label4, question, q_image, color, 
            li.path as limg,
            ci.path as cimg,
            ri.path as rimg
        FROM trial
        LEFT JOIN stimuli AS li ON (li.id=left_img)
        LEFT JOIN stimuli AS ci ON (ci.id=center_img)
        LEFT JOIN stimuli AS ri ON (ri.id=right_img)
        WHERE exp_id = ? 
        AND trial_n <= ? 
        ORDER BY trial_n',
        array('ii', $exp_id, $eInfo['total_stim']));
        
    $trials = $query->get_assoc();
    
    if ($eInfo['exptype'] == 'xafc' || $eInfo['exptype'] == 'sort') {
        $query = new myQuery('SELECT trial_n, n, stimuli.path as path 
            FROM xafc 
            LEFT JOIN stimuli ON (stimuli.id=xafc.image) 
            WHERE exp_id=' . $exp_id . ' 
            AND trial_n <= ' . $eInfo['total_stim'] . ' 
            ORDER BY trial_n');
        $xafc_trials = $query->get_assoc();
        foreach ($xafc_trials as $xt) {
            $i = $xt['trial_n'] - 1;
            $trials[$i]['xafc'][] = $xt['path'];
        }
    }
    
    // get total trials
    $total_trials = max(1, $eInfo['total_stim']);
    
    // add more trials if the experiment table holds more trials than the trials table
    if (count($trials) > 0 && count($trials) < $total_trials) {
        for ($i=count($trials)+1; $i<=$total_trials; $i++) {
            $trials[$i] = $trials[0];
            $trials[$i]['trial_n'] = $i;
            $trials[$i]['name'] = 't' . $i;
        }
    }
    
    if (count($trials) == 0) {
        // no trials exist, set up trials
        $trials = array();
        
        $maxtrials = max(1,$eInfo['random_stim'], $total_trials);
        
        for ($i=0; $i<$maxtrials; $i++) {
            $trials[$i] = array(
                'trial_n' => ($i+1),
                'name' => 't' . ($i+1),
            );
            
            if (strpos($_GET['images'], 'l') !== false) $trials[$i]['limg'] = '/stimuli/blankface.jpg';
            if (strpos($_GET['images'], 'c') !== false) $trials[$i]['cimg'] = '/stimuli/blankface.jpg';
            if (strpos($_GET['images'], 'r') !== false) $trials[$i]['rimg'] = '/stimuli/blankface.jpg';
            
            if (is_numeric($_GET['images'])) {
                // this is an xafc, so display as many images as needed
                for ($x = 0; $x < $_GET['images']; $x++) {
                    $trials[$i]['xafc'][] = '/stimuli/blankface.jpg';
                }
            }
        }
    }
    
    // set up table width and margins to display trials and image chooser correctly
    $tablewidth = ((empty($trials[0]['limg'])) ? 0 : 90) +
                  ((empty($trials[0]['cimg'])) ? 0 : 90) +
                  ((empty($trials[0]['rimg'])) ? 0 : 90) + 
                  ((empty($trials[0]['label1'])) ? 0 : 120) +
                  ((empty($trials[0]['xafc'])) ? 0 : 90 * count($trials[0]['xafc'])) + 25;
                  
    if ($tablewidth > 500) $tablewidth = 500;
    if ($tablewidth < 100) $tablewidth = 100;
                  
    $styles['#trial_builder'] = 'float: left; width: ' . $tablewidth . 'px; margin-right: -' . ($tablewidth+20) . 'px';
    $styles['#image_chooser'] = 'margin-left: ' . ($tablewidth+20) . 'px;';
    $styles['#trial_builder.list'] = 'max-width: 50%; width: '. ($tablewidth*2) .'px; margin-right: -' . ($tablewidth*2+20) . 'px';
    $styles['#trial_builder.list + #image_chooser'] = 'margin-left: ' . ($tablewidth*2+20) . 'px;';
} else {
    // default exp info
    $eInfo = array(
        'exptype' => (array_key_exists($_GET['exptype'], $exptype_options)) ? $_GET['exptype'] : 'jnd',
        'subtype' => 'large_n',
        'design' => 'within',
        'trial_order' => 'random',
        'side' => 'random',
        'instructions' => '*Click here* to edit the **information page**. You can use [markdown](https://codepen.io/nmtakay/pen/gscbf) or html to format your instruction page.',
        'question' => '*Click here* to set the **question**',
        'label4' => 'Much more',
        'label3' => 'More',
        'label2' => 'Somewhat more',
        'label1' => 'Slightly more',
        'rating_range' => '7',
        'low_anchor' => 'Click Here to Set or Delete Low Anchor',
        'high_anchor' => 'Click Here to Set or Delete High Anchor',
        'slider_min' => '0',
        'slider_max' => '100',
        'slider_step' => '1',
        'random_stim' => '20',
        'total_stim' => '20',
        'default_time' => '4000',
        'increment_time' => '100',
        'sex' => 'both',
        'feedback_general' => ''
    );
    $buttons = array(
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5',
        6 => '6',
        7 => '7'
    );
    $nImages = $default_nImages[$_GET['exptype']];
}

$info['id'] = new hiddenInput('id', 'id', $eInfo['id']);

// name for users
$info['name'] = new input('name', 'name', $eInfo['name']);
$info['name']->set_question('Name for Users');
$info['name']->set_width(500);

// name for researchers
$info['res_name'] = new input('res_name', 'res_name', $eInfo['res_name']);
$info['res_name']->set_question('Name for Researchers');
$info['res_name']->set_width(500);

//exptype
$info['exptype'] = new hiddenInput('exptype', 'exptype', $eInfo['exptype']);
/*
$info['exptype'] = new select('exptype', 'exptype', $eInfo['exptype']);
$info['exptype']->set_question('Experiment Type');
$info['exptype']->set_null(false);
$info['exptype']->set_options($exptype_options);
$info['exptype']->set_eventHandlers(array('onchange' => "change_exptype(this.value)"));
*/

// subtype
$info['subtype'] = new hiddenInput('subtype', 'subtype', 'large_n');
/*
$info['subtype'] = new select('subtype', 'subtype', $eInfo['subtype']);
$info['subtype']->set_question('Subtype');
$info['subtype']->set_null(false);
$subtype_options = array(
    'large_n' => 'Standard',
    //'adapt' => 'Adaptation (with pre-test)',
    //'adapt_nopre' => 'Adaptation (no pre-test)'
);

if ("2afc" == $eInfo['exptype'] || "buttons" == $eInfo['exptype']) { $subtype_options['speeded'] = 'Speeded'; }
$info['subtype']->set_options($subtype_options);
*/

// design
$info['design'] = new select('design', 'design', $eInfo['design']);
$info['design']->set_question('Design');
$info['design']->set_null(false);
$info['design']->set_options(array(
    'between' => 'Between-subjects (can only complete once)',
    'within' => 'Within-subjects (can complete many times)'
));

if ($eInfo['exptype'] == 'slideshow') {
    $info['increment_time'] = new input('increment_time', 'increment_time', $eInfo['increment_time']);
    $info['increment_time']->set_question('Increment Time<div class="note">(in milliseconds)</div>');
    $info['increment_time']->set_width(100);
    $info['increment_time']->set_type('number');
    $info['increment_time']->set_int_only(true);
}

if ($eInfo['exptype'] == 'slider') {
    $slider_min= new input('slider_min', 'slider_min', $eInfo['slider_min']);
    $slider_min->set_width(50);
    $slider_min->set_type('number');
    
    $slider_max = new input('slider_max', 'slider_max', $eInfo['slider_max']);
    $slider_max->set_width(50);
    $slider_max->set_type('number');
    
    $slider_step = new input('slider_step', 'slider_step', $eInfo['slider_step']);
    $slider_step->set_width(50);
    $slider_step->set_type('number');
    
    $slider = $slider_min->get_element() . ' to ' . $slider_max->get_element() . 
              ' by steps of ' . $slider_step->get_element();
    $info['slider'] = new formElement('slider','slider');
    $info['slider']->set_question("Slider range");
    $info['slider']->set_custom_input($slider);
}

if ($eInfo['exptype'] == 'buttons') {
    $info['button_number'] = new selectnum('button_number', 'button_number', count($buttons));
    $info['button_number']->set_question('Number of Buttons');
    $info['button_number']->set_options(null, 1, 20);
    $info['button_number']->set_null(false);
}

// trial_order
$info['trial_order'] = new radio('trial_order', 'trial_order', $eInfo['trial_order']);
$info['trial_order']->set_question('Trial Order');
$info['trial_order']->set_options(array(
    'random' => 'Random',
    'fixed' => 'Fixed'
));

// side
$info['side'] = new radio('side', 'side', $eInfo['side']);
$info['side']->set_question('Image Side');
$info['side']->set_options(array(
    'random' => 'Random',
    'fixed' => 'Fixed'
));

// total images
$total_stim = new input('total_stim', 'total_stim', $eInfo['total_stim']);
$total_stim->set_width(50);
$total_stim->set_type('number');
$random_stim = new input('random_stim', 'random_stim', $eInfo['random_stim']);
$random_stim->set_width(50);
$random_stim->set_type('number');
$random_stim->set_eventHandlers(array('onchange' => '$(\'#random_stim_top\').html(this.value);'));
$ci = "Show " . $random_stim->get_element() . ' of ' . $total_stim->get_element() . ' total images';
$info['images'] = new formElement('images','images');
$info['images']->set_question("Images");
$info['images']->set_custom_input($ci);

// set up limits: sex, lower_age, upper_age
$sex = new select('sex', 'sex', $eInfo['sex']);
$sex->set_options(array(
    'both' => 'All genders',
    'male' => 'Men only',
    'female' => 'Women only'
));
$sex->set_null(false);
$lower_age = new selectnum('lower_age', 'lower_age', $eInfo['lower_age']);
$lower_age->set_options(array('NULL'=>'any'), 0, 100);
$lower_age->set_null(false);
$upper_age = new selectnum('upper_age', 'upper_age', $eInfo['upper_age']);
$upper_age->set_options(array('NULL'=>'any'), 0, 100);
$upper_age->set_null(false);
$ci = $sex->get_element() . 
    ' aged ' . $lower_age->get_element() . 
    ' to ' . $upper_age->get_element();
$info['limits'] = new formElement('limits','limits');
$info['limits']->set_question('Limited to');
$info['limits']->set_custom_input($ci);

$info['labnotes'] = new textarea('labnotes', 'labnotes', $eInfo['labnotes']);
$info['labnotes']->set_question('Labnotes');
$info['labnotes']->set_dimensions(500, 50, true, 50, 0, 0);

$min_max_images = array(
    '2afc'      => array(2, 3),
    'slideshow' => array(1, 3),
    'jnd'       => array(2, 3),
    'rating'    => array(1, 3),
    'slider'    => array(1, 3),
    'buttons'   => array(1, 3),
    'xafc'      => array(3, 10),
    'sort'      => array(2, 10),
    'nback'     => array(1, 1),
    'other'     => array(1, 10),
    'motivation' => array(1, 1),
    'interactive' => array(1, 1),
);

$info['nImages'] = new selectnum('nImages', 'nImages', $nImages);
$info['nImages']->set_question('Number of Images to Display');
$info['nImages']->set_options(array(), $min_max_images[$eInfo['exptype']][0], $min_max_images[$eInfo['exptype']][1]);
$info['nImages']->set_null(false);

/*
$info['feedback_general'] = new textarea('feedback_general', 'feedback_general', $eInfo['feedback_general']);
$info['feedback_general']->set_question('General Feedback');
$info['feedback_general']->set_dimensions(500, 50, true, 50, 0, 0);

$info['feedback_specific'] = new textarea('feedback_specific', 'feedback_specific', $eInfo['feedback_specific']);
$info['feedback_specific']->set_question('Specific Feedback (%1$s)<br /><button id="generic_fb">insert generic</button>');
$info['feedback_specific']->set_dimensions(500, 50, true, 50, 0, 0);

$info['feedback_query'] = new textarea('feedback_query', 'feedback_query', $eInfo['feedback_query']);
$info['feedback_query']->set_question('Feedback Query');
$info['feedback_query']->set_dimensions(500, 50, true, 50, 0, 0);
*/

$submit_buttons = array('Save' => 'saveExperiment();');
$submit_buttons['Reset'] = 'window.location.href=window.location.href;';

// set up other info table
$infoTable = new formTable();
$infoTable->set_table_id('myInformation');
$infoTable->set_title('My Information');
$infoTable->set_action('');
$infoTable->set_questionList($info);
$infoTable->set_method('post');
$infoTable->set_buttons($submit_buttons);
$infoTable->set_button_location('top');


/****************************************************/
/* !    Input Interface */
/***************************************************/

$introtext = '';
switch ($eInfo['exptype']) {
    case '2afc': break;
    case 'slideshow': break;
    case 'motivation': break;
    case 'xafc': break;
    case 'jnd':
        $introtext .= '  <tr class="input_interface">' . ENDLINE;
        $introtext .= '      <td><span class="editText" id="label4" onchange="$(\'#label4b\').html($(\'#label4_field\').val());">' 
                        . $eInfo['label4'] . '</span></td>' . ENDLINE;
        $introtext .= '      <td><span class="editText" id="label3" onchange="$(\'#label3b\').html($(\'#label3_field\').val());">' 
                        . $eInfo['label3'] . '</span></td>' . ENDLINE;
        $introtext .= '      <td><span class="editText" id="label2" onchange="$(\'#label2b\').html($(\'#label2_field\').val());">' 
                        . $eInfo['label2'] . '</span></td>' . ENDLINE;
        $introtext .= '      <td><span class="editText" id="label1" onchange="$(\'#label1b\').html($(\'#label1_field\').val());">' 
                        . $eInfo['label1'] . '</span></td>' . ENDLINE;
        $introtext .= '      <td class="center" id="center_col" style="display:none;"></td>' . ENDLINE;
        $introtext .= '      <td><span class="editText" id="label1b" onchange="$(\'#label1\').html($(\'#label1b_field\').val());">' 
                        . $eInfo['label1'] . '</span></td>' . ENDLINE;
        $introtext .= '      <td><span class="editText" id="label2b" onchange="$(\'#label2\').html($(\'#label2b_field\').val());">' 
                        . $eInfo['label2'] . '</span></td>' . ENDLINE;
        $introtext .= '      <td><span class="editText" id="label3b" onchange="$(\'#label3\').html($(\'#label3b_field\').val());">' 
                        . $eInfo['label3'] . '</span></td>' . ENDLINE;
        $introtext .= '      <td><span class="editText" id="label4b" onchange="$(\'#label4\').html($(\'#label4b_field\').val());">' 
                        . $eInfo['label4'] . '</span></td>' . ENDLINE;
        $introtext .= '  </tr>' . ENDLINE;
        break;
    case 'rating':
        $introtext .= '  <tr class="input_interface">' . ENDLINE;
        $introtext .= '      <td colspan="3">' . ENDLINE;
        $introtext .= '          <span class="editText" id="low_anchor">' . $eInfo['low_anchor'] . '</span>' . ENDLINE;
        $introtext .= '              <input type="text" class="rating" name="rating_range" id="rating_range" value="' . $eInfo['rating_range'] . '" />' . ENDLINE;
        $introtext .= '          <span class="editText" id="high_anchor">' . $eInfo['high_anchor'] . '</span>' . ENDLINE;
        $introtext .= '      </div></td>' . ENDLINE;
        $introtext .= '  </tr>' . ENDLINE;
        break;
    case 'slider':
        $introtext .= '  <tr class="input_interface">' . ENDLINE;
        $introtext .= '      <td colspan="3">' . ENDLINE;
        $introtext .= '          <span class="editText" id="low_anchor">' . $eInfo['low_anchor'] . '</span>' . ENDLINE;
        $introtext .= '              <div id="exp_slider" /><div class="slider_handle ui-slider-handle"></div></div>' . ENDLINE;
        $introtext .= '          <span class="editText" id="high_anchor">' . $eInfo['high_anchor'] . '</span>' . ENDLINE;
        $introtext .= '      </div></td>' . ENDLINE;
        $introtext .= '  </tr>' . ENDLINE;
        break;
    case 'buttons':
        $introtext .= '  <tr class="input_interface">' . ENDLINE;    
        $introtext .= '      <td colspan="3"><div class="buttons"><table id="button_display"><tr>' . ENDLINE;
        $introtext .= '          <td width="15%"><span class="editText" id="low_anchor">' . $eInfo['low_anchor'] . '</span></td>' . ENDLINE;
        $introtext .= '          <td width="70%"><table id="button_table"><tr>' . ENDLINE;
        $n = 0;
        foreach ($buttons as $dv => $display) {
            $n++;
            $introtext .= "      <td><span class='editText button-dv'>$dv</span></td>" . ENDLINE;
        }
        $introtext .= '         </tr><tr>' . ENDLINE;
        $n = 0;
        foreach ($buttons as $dv => $display) {
            $n++;
            $introtext .= "      <td><span class='button-wrapper'><span class='editText'>$display</span></span></td>" . ENDLINE;
        }
        $introtext .= '          </tr></table></td>';
        $introtext .= '          <td width="15%"><span class="editText" id="high_anchor">' . $eInfo['high_anchor'] . '</span></td>' . ENDLINE;
        $introtext .= '      </tr></table></div></td>' . ENDLINE;
        $introtext .= '  </tr>' . ENDLINE;
        break;
}

/****************************************************/
/* !    Image Display Interface */
/***************************************************/
$displaytext = '';

switch ($eInfo['exptype']) {
    case '2afc': 
        $displaytext .= '      <td id="left_image"><img src="/stimuli/blankface" /></td>' . ENDLINE;
        $displaytext .= '      <td id="center_image" style="display:none;"><img src="/stimuli/blankface"/></td>' . ENDLINE;
        $displaytext .= '      <td id="right_image"><img src="/stimuli/blankface" /></td>' . ENDLINE;
        $nImages = 2;
        break;
    case 'jnd':
        $displaytext .= '      <td id="left_image" colspan="4"><img src="/stimuli/blankface" /></td>' . ENDLINE;
        $displaytext .= '      <td id="center_image" style="display:none;"><img src="/stimuli/blankface"/></td>' . ENDLINE;
        $displaytext .= '      <td id="right_image" colspan="4"><img src="/stimuli/blankface" /></td>' . ENDLINE;
        $nImages = 2;
        break;
    case 'slideshow': 
    case 'buttons':
    case 'rating':
    case 'slider':
        $displaytext .= '      <td id="left_image" style="display:none;"><img src="/stimuli/blankface" /></td>' . ENDLINE;
        $displaytext .= '      <td id="center_image"><img src="/stimuli/blankface"/></td>' . ENDLINE;
        $displaytext .= '      <td id="right_image" style="display:none;"><img src="/stimuli/blankface" /></td>' . ENDLINE;
        $nImages = 1;
        break;
    case 'xafc':
        $displaytext .= '      <td id="center_image" class="xafc">' .ENDLINE;
        $displaytext .= '          <img src="/stimuli/blankface"/>' . ENDLINE;
        $displaytext .= '          <img src="/stimuli/blankface"/>' . ENDLINE;
        $displaytext .= '          <img src="/stimuli/blankface"/>' . ENDLINE;
        $displaytext .= '      </td>' . ENDLINE;
        $nImages = 3;
        break;
    case 'sort':
        $displaytext .= '      <td id="center_image" class="sort">' .ENDLINE;
        $displaytext .= '          <img src="/stimuli/blankface"/>' . ENDLINE;
        $displaytext .= '          <img src="/stimuli/blankface"/>' . ENDLINE;
        $displaytext .= '          <img src="/stimuli/blankface"/>' . ENDLINE;
        $displaytext .= '          <img src="/stimuli/blankface"/>' . ENDLINE;
        $displaytext .= '          <img src="/stimuli/blankface"/>' . ENDLINE;
        $displaytext .= '      </td>' . ENDLINE;
        $nImages = 5;
        break;
    case 'motivation':
        $default_time = new input('default_time', 'default_time', $eInfo['default_time']);
        $default_time->set_width(50);
        $default_time->set_type('number');
        $default_time->set_int_only(true);
        
        $increment_time = new input('increment_time', 'increment_time', $eInfo['increment_time']);
        $increment_time->set_width(50);
        $increment_time->set_type('number');
        $increment_time->set_int_only(true);
        
        $displaytext .= '      <td><dl id="motiv_info">' . ENDLINE;
        $displaytext .= '          <dt>Default time</dt><dd> ' . $default_time->get_element() . '</dd>' . ENDLINE;
        $displaytext .= '          <dt>Increment time</dt><dd> ' . $increment_time->get_element() . '</dd>' . ENDLINE;
        $displaytext .= '      </dl></td>' . ENDLINE;
        $displaytext .= '      <td><div id="motivation-container">' . ENDLINE;
        $displaytext .= '          <span id="countdownlabels">7 &amp; 8 &uarr;<br />1 &amp; 2 &darr;</span>' . ENDLINE;
        $displaytext .= '          <div id="countdown"></div>' . ENDLINE;
        $displaytext .= '          <img  id="center_image" class="motivation" src="/stimuli/blankface"/>' . ENDLINE;
        $displaytext .= '      </div></td>' . ENDLINE;
        $nImages = 1;
        break;
    case 'interactive':
        $nImages = 1;
        break;
} 


/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

if ($eInfo['exptype'] == "rating") {
    echo "<p class='warning'>Rating experiments don't work well on mobile devices 
    (the input keyboard covers the stimuli). Try a 
    <a href='builder?exptype=button'>button interface</a> instead if your 
    participants might use mobile devices.</p>";
}

// editable experiment

if ($eInfo['status'] == 'active') { 
    echo "<h3 class='warning'>Saving an active experiment will make it inactive</h3>"; 
}

?>

<?= $infoTable->print_form() ?>

<form action='' method='post' id='exp_<?= $exp_id ?>'>
    
<div id="tabs">
    <ul>
        <li><a href="#intropage">Introduction</a></li>
        <li><a href="#experiment">Experiment</a></li>
        <li><a href="#fbpage">Feedback</a></li>
        <li><a href="#trialpage">Trials</a></li>
    </ul>
  
    <div id="intropage">
        <div class='instructions'><span id='instructions' 
            class='editText md' 
            title='Instructions on page before the experiment starts'><?= htmlspecialchars($eInfo['instructions']) ?></span></div>
        <input type='hidden' name='exp_id' id='exp_id' value='<?= $exp_id ?>' />
    </div>
  
    <div id="experiment">
        <div id='question'><span id='trial_question' 
             class='editText md' 
             title='Question to be displayed at the top of each trial. Leave blank for a different question for each trial.'><?= $eInfo['question'] ?></span></div>
        <table id="experiment_builder" class="<?= ('2afc' == $eInfo['exptype']) ? 'tafc' : $eInfo['exptype'] ?>">
        <?= $introtext ?>

            <tr class="exp_images">
                <?= $displaytext ?>
            </tr>
        </table>

        Trial x of <span id="random_stim_top"><?= $eInfo['random_stim'] ?></span>
    </div>
    
    <div id="fbpage">
        <p class="fullwidth">Leave the feedback below blank unless this will be the last item in a 
            project and you want to display feedback specific to this experiment.</p>
        <hr>
        <p class='instructions'><span id='feedback_general' 
            class='editText md' 
            title='Feedback'><?= htmlspecialchars($eInfo['feedback_general']) ?></span></p>
    </div>

    <div id="trialpage">
        <div class="toolbar">
            <div class="toolbar-line">
                <span id="search-bar">
                    <input type="search" 
                        placeholder="Search for images"
                        id="image_search" 
                        name="image_search" 
                        onchange="showImages(50);"  />
            
                    <span id="image_list_toggle">
                        <input type="radio" id="list_toggle" name="radio" checked="checked" />
                        <label for="list_toggle">List</label>
                        <input type="radio" id="image_toggle" name="radio" />
                        <label for="image_toggle">Images</label>
                    </span>
                </span>
            </div>
            
            <div class="toolbar-line">              
                <button id="fill-from-list">Fill From List</button>
                Common Path: <span id="common_path"></span>
                
                <span id="images_found"></span>
            </div>
        </div>
        
        <div id="trial_builder" class="list">
        
            <div id="dv">
        
            <?php
            if (!empty($trials[0]['limg'])) {
                if ($eInfo['exptype'] == '2afc') {
                    echo '<span>DV = 1</span>' . ENDLINE;
                } else if ($eInfo['exptype'] == 'jnd') {
                    echo '<span>DV = 4-7</span>' . ENDLINE;
                }
            }
            
            if (!empty($trials[0]['cimg'])) {
                if ($eInfo['exptype'] == '2afc') {
                    echo '<span></span>' . ENDLINE;
                } else if ($eInfo['exptype'] == 'jnd') {
                    echo '<span></span>' . ENDLINE;
                }
            }
            
            
            if (!empty($trials[0]['rimg'])) {
                if ($eInfo['exptype'] == '2afc') {
                    echo '<span>DV = 0</span>' . ENDLINE;
                } else if ($eInfo['exptype'] == 'jnd') {
                    echo '<span>DV = 0-3</span>' . ENDLINE;
                }
            }
            
            if (!empty($trials[0]['xafc'])) {
                foreach ($trial['xafc'] as $i => $x) {
                    echo '<span>DV = '.$i.'</span>' . ENDLINE;
                }
            }
            ?>
            </div>
        
        <?php    
        foreach ($trials as $trial) {
        
            echo '<div id="trial_' . $trial['trial_n'] . '" class="trial">'. ENDLINE;
            
            echo '  <p>t' . $trial['trial_n'] . ': <span id="name_' . $trial['trial_n'] . '" class="editText" title="Click to edit!">' . $trial['name' ] . '</span></p>' . ENDLINE;
            
            if (empty($eInfo['question'])) { 
                echo '<p>Q' . $trial['trial_n'] . ': <span id="question_' . $trial['trial_n'] . '" class="editText" title="Click to edit!">' . $trial['question' ] . '</span></p>' . ENDLINE; 
            }
            
            if ($eInfo['exptype'] == 'jnd' && empty($eInfo['label1'])) {
                echo '  <ol class="label_list">' . ENDLINE;
                echo '  <li><span id="label1_' . $trial['trial_n'] . '" class="editText" title="Click to edit!">' . $trial['label1' ] . '</li>' . ENDLINE;
                echo '  <li><span id="label2_' . $trial['trial_n'] . '" class="editText" title="Click to edit!">' . $trial['label2' ] . '</li>' . ENDLINE;
                echo '  <li><span id="label3_' . $trial['trial_n'] . '" class="editText" title="Click to edit!">' . $trial['label3' ] . '</li>' . ENDLINE;
                echo '  <li><span id="label4_' . $trial['trial_n'] . '" class="editText" title="Click to edit!">' . $trial['label4' ] . '</li>' . ENDLINE;
                echo '  </ol>' . ENDLINE;
            }
            
            
            if (!empty($trial['limg'])) { 
                $imagelist[] = $trial['limg']; // add image to master image list
                echo '<img class="trialimg" id="limg_' . $trial['trial_n'] . '" 
                    src="' . (substr($trial['limg'],0,7) == '/audio/' ? '/images/linearicons/rocket.php' : $trial['limg']) . '" 
                    title="' . $trial['limg'] . '" />' . ENDLINE .
                    '<span class="imgname">' . imgname($trial['limg']) . '</span>' . ENDLINE; 
            }
            if (!empty($trial['cimg'])) { 
                $imagelist[] = $trial['cimg']; // add image to master image list
                echo '<img class="trialimg" id="cimg_' . $trial['trial_n'] . '" 
                    src="' . (substr($trial['cimg'],0,7) == '/audio/' ? '/images/linearicons/rocket.php' : $trial['cimg']) . '" 
                    title="' . $trial['cimg'] . '" />' . ENDLINE .
                    '<span class="imgname">' . imgname($trial['cimg']) . '</span>' . ENDLINE; 
            }
            if (!empty($trial['rimg'])) { 
                $imagelist[] = $trial['rimg']; // add image to master image list
                echo '<img class="trialimg" id="rimg_' . $trial['trial_n'] . '" 
                    src="' . (substr($trial['rimg'],0,7) == '/audio/' ? '/images/linearicons/rocket.php' : $trial['rimg']) . '" 
                    title="' . $trial['rimg'] . '" />' . ENDLINE .
                    '<span class="imgname">' . imgname($trial['rimg']) . '</span>' . ENDLINE; 
            }
            if (!empty($trial['xafc'])) {
                echo '<span id="xafc_' . $trial['trial_n'] . '">' . ENDLINE;
                foreach ($trial['xafc'] as $i => $x) {
                    $imagelist[] = $x; // add image to master image list
                        $n = $i+1;
                    echo '<img class="trialimg" id="xafc_' . $n . '_img_' . $trial['trial_n'] . '" 
                        src="' . (substr($x,0,7) == '/audio/' ? '/images/linearicons/rocket.php' : $x) . '" 
                        title="' . $x . '" />' . ENDLINE .
                    '<span class="imgname">' . imgname($x) . '</span>' . ENDLINE; 
                }
                echo '</span>' . ENDLINE;
            }
            echo '</div>' . ENDLINE;
        }
        ?>
        
        </div>
        
        <div id="image_chooser">
            <!--
            <div id="imagebox">
                <div id='imageurl'></div>
                <img />
            </div>
            <div id="finder"></div>
            -->
            <ul id="img_list"></ul>
        </div>
    </div>
</div>

</form>

<div id="dialog-form-fill" class="modal" title="Fill fields from list">
    <p>Paste an Excel column or type in a list of trial names, etc.</p>
    <textarea id="listfill"></textarea>
</div>

<div id="help" title="Experiment Builder Help">
    <h1>Editing the experiment</h1>
    <ul>
        <li>Click on the title, instructions, and question to edit them.</li>
        <li>You can increase or decrease the number of images shown on each trial (for some types of experiments) by clicking on the + and - buttons in the lower right corner.</li>
    </ul>
    
    <h1>Searching for Images</h1>
    <ul>
        <li>Type into the search box and press Return to search the image database.</li>
        <li>Both the full image name (e.g., <kbd>/stimuli/canada2003/sexdim/female/fem/white</kbd>) and the description (if set) are searched.</li>
        <li>Use <kbd>AND</kbd> or <kbd>OR</kbd> to search multiple terms (e.g., <kbd>composites AND (1ns OR sss)</kbd>).</li>
        <li>Use <kbd>!</kbd> to remove items with a term (e.g., <kbd>kdef AND !profile</kbd>).</li>
    </ul>
    
    <h1>Setting the Trials</h1>
    <ul>
        <li>Double-click an image in the trial builder on the left to fill all following images from the list on the right.</li>
        <li>You can set individual images by dragging images or image names from the list on the right.</li>
        <li>Images that will get high scores (1 on FC or 4-7 on JND) go on the left. Images that will get low scores (0 on FC or 0-3 on JND) go on the right.</li>
    </ul>
</div>


<!--**************************************************-->
<!-- !Javascripts for this page -->
<!--**************************************************-->

<script>
    var nImages = $('#nImages').val();
    
    var defaultTime = 4000;
    
    $(function() {
        
        $( "#tabs" ).tabs();
        $("#exp_slider").slider({
            min: parseFloat($("#slider_min").val()),
            max: parseFloat($("#slider_max").val()),
            step: parseFloat($("#slider_step").val()),
            change: function(e, ui) {
                $(ui.handle).show();
                //$(ui.handle).text( ui.value );
            },
            create: function(e, ui) {
                //$(ui.handle).text( ui.value );
            },
            slide: function(e, ui) {
                //$(ui.handle).text( ui.value );
            }
        });
        
        $(".slider_handle").hide();
        
        $('#slider_min').change(function() {
            $( "#exp_slider" ).slider( "option", "min", $('#slider_min').val() );
        });
        
        $('#slider_max').change(function() {
            $( "#exp_slider" ).slider( "option", "max", $('#slider_max').val() );
        });
        
        $('#slider_step').change(function() {
            $( "#exp_slider" ).slider( "option", "step", $('#slider_step').val() );
        });
    
        setOriginalValues('myInformation'); 
        
        $('#generic_fb').css('font-size', '70%').button().click(function() {
            $('#feedback_specific').val('<?= $fb_specifics[$eInfo['exptype']] ?>');
            $('#feedback_query').val("SELECT <?= $fb_queries[$eInfo['exptype']] ?> \n FROM exp_<?= $eInfo['id'] ?> GROUP BY NULL");
            return false;
        });
        
        // initialise countdown slider for motivation interface
        $( "#countdown" ).slider({
            orientation: "vertical",
            range: "min",
            min: 0,
            max: defaultTime,
            value: defaultTime
        });
        
        // change button number
        $('#button_number').on('change', function() {
            var oldn = $('#button_table tr:eq(0) > td').length;
            var newn = this.value;
            if (newn == 0) {
                growl('You must have at least 1 button');
                return false;
            }
            
            
            console.log("Changing buttons from", oldn, "to", newn);
            if (newn < oldn) {
                $('#button_table tr').find('td:gt('+(newn-1)+')').remove();
                return true;
            }
            
            for (var i = oldn; i < newn; i++) {
                $('#button_table tr').each(function(){
                    var lasttd = $(this).find("td").last();
                    var newtd = lasttd.clone();
                    newtd.find('.editText').text(i+1);
                    $(this).append(newtd);
                });
            }
            editbox_init();
        });
        
        $('#total_stim').change( function() {
            if (this.value > 300 && $('#subtype').val() != 'large_n') { 
                $('#subtype').val('large_n');
                alert('Subtype must be Large N or total images must be <300'); 
            }
        });
        $('#subtype').change(function() {
            if ($('#total_stim').val() > 300 && this.value != 'large_n') {
                alert('Subtype must be Large N or total images must be <300'); 
                $('#total_stim').val(300);
            }
        });
                
                
        $('#nImages').change( function() {
            nImages = $('#nImages').val();
            viewImages();
        } );
    }); // end of $(function(){})
    

    function viewImages() {
        $('table.jnd .input_interface').removeClass('jnd3');
        if ($('table.xafc').length > 0 || $('table.sort').length > 0) {
            $('#side_row').show();
            $('#center_image').html('');
            for (var i = 0; i < nImages; i++) {
                $('#center_image').append('<img src="/stimuli/blankface"/>');
            }
            $('td.xafc img').css('width', (100/nImages)-2 + '%').css('min-width', '18%');
        } else if (nImages == 1) {
            $('#side_row').hide();
            $('#left_image').hide();
            $('#center_image').show();
            if ($('#center_col')) $('#center_col').show();
            $('#right_image').hide();
        } else if (nImages == 2) {
            $('#side_row').show();
            $('#left_image').show();
            $('#center_image').hide();
            if ($('#center_col')) $('#center_col').hide();
            $('#right_image').show();
        } else if (nImages == 3) {
            $('#side_row').show();
            $('#left_image').show();
            $('#center_image').show();
            if ($('#center_col')) $('#center_col').show();
            $('#right_image').show();
            
            $('table.jnd .input_interface').addClass('jnd3');
        }
    }
    
    viewImages();
    
    function change_exptype(exptype) {
        alert("You can't change the experiment type yet. Remind Lisa to fix this.");
    }
    
    function saveNew() {
        $('#id').val('');
        saveExperiment();
    }

    function saveExperiment() {
        var formData = [];
        $('input.instantedit').each( function() {
            $(this).val(unescape($(this).val()));
        });
        
        if (nImages == 1) {
            $('#side_fixed').click()
        }
        
        $('#maincontent form').each( function(e) {
            formData[formData.length] = $(this).serialize(false);
        });
        
        
    
        $.ajax({
            url: './builder?save',
            type: 'POST',
            data: formData[0] + '&' + formData[1],
            success: function(data) {
                parsedResponse = data.split(':');
                if (parsedResponse[0] == 'id') {
                    $('#id').val(parsedResponse[1]);
                    editTrials();
                } else {
                    alert(data);
                }
            }
        });
    }
    
    function editTrials() {
        var ldisplay = '';
        var cdisplay = '';
        var rdisplay = '';
        
        if ($('td.xafc img').length) { 
            cdisplay = $('td.xafc img').length; 
        } else if ($('td.sort img').length) { 
            cdisplay = $('td.sort img').length; 
        } else {
            if ($('#left_image').length > 0 && $('#left_image').css('display') != 'none') { ldisplay = 'l'; }
            if ($('#center_image').length > 0 && $('#center_image').css('display') != 'none') { cdisplay = 'c'; }
            if ($('#right_image').length > 0 && $('#right_image').css('display') != 'none') { rdisplay = 'r'; }
        }
        
        window.location = "trials?id=" + $("#id").val() + "&images=" + ldisplay + cdisplay + rdisplay;
    }
    
        function imgname(src) {
        var name = src.replace('/stimuli', '');
        name = name.replace('.jpg', '');
        name = name.replace('.gif', '');
        name = name.replace('.png', '');
        name = name.replace('.ogg', '');
        name = name.replace('.mp3', '');
        return name;
    }

    $(function() {
        
        $("#trial_builder img.trialimg, #trial_builder span.imgname").droppable({
            tolerance: "pointer",
            hoverClass: "drop_hover",
            drop: function( event, ui ) {
                if ($(this).hasClass('imgname')) {
                    var theImg = $(this).prev('img.trialimg');
                    var theSpan = $(this);
                } else {
                    var theSpan = $(this).next('span.imgname');
                    var theImg = $(this);
                }
            
                var theSrc = $(ui.draggable).attr('title');
                
                theImg.attr({
                    'src': theSrc,
                    'title': theSrc
                });
                theSpan.html(imgname(theSrc));
            }
        });
        
        $("#trial_builder img.trialimg, #trial_builder span.imgname").dblclick( function() {
            if ($(this).hasClass('imgname')) {
                var coldata = $(this).prev('img.trialimg').attr('id').split('img_');
            } else {
                var coldata = $(this).attr('id').split('img_');
            }

            fill(coldata[0], coldata[1]);
        });
        
        // resize lists to window height
        $(window).resize(resizeContent);
        resizeContent();
        
        // add common path to common_path
        var common_path = "<?= str_replace('/stimuli', '', common_path($imagelist)) ?>";
        $("#common_path").html(common_path);
        

        // add functions to buttons
        $( "#image_list_toggle" ).buttonset();
        $( "#list_toggle" ).click(function() { toggleImages(0); });
        $( "#image_toggle" ).click(function() { toggleImages(1); });
        
        $('#start-exp').button().click( function() {
            window.location = '/exp?id=<?= $exp_id ?>';
        });
        
        $('#edit-exp').button().click( function() {
            window.location = '/res/exp/builder?id=<?= $exp_id ?>';
        });
        
        $( "#exp-info" ).button().click( function() {
            window.location.href='/res/exp/info?id=<?= $exp_id ?>'; 
        });
        
        $( "#save-trials" ).button().click(function() {
            var dataArray = {};
    
            $('#trial_builder div.trial').each( function() {
                var n = this.id.replace('trial_', '');          
                dataArray[n] = {};
                
                dataArray[n]['name'] = $('#name_' + n).html();
                if ($('#limg_' + n).length > 0) dataArray[n]['limg'] = $('#limg_' + n).attr('title');
                if ($('#cimg_' + n).length > 0) dataArray[n]['cimg'] = $('#cimg_' + n).attr('title');
                if ($('#rimg_' + n).length > 0) dataArray[n]['rimg'] = $('#rimg_' + n).attr('title');
                if ($('#label1_' + n).length > 0) {
                    dataArray[n]['label1'] = $('#label1_' + n).html();
                    dataArray[n]['label2'] = $('#label2_' + n).html();
                    dataArray[n]['label3'] = $('#label3_' + n).html();
                    dataArray[n]['label4'] = $('#label4_' + n).html();
                }
                if ($('#question_' + n).length > 0) dataArray[n]['question'] = $('#question_' + n).html();
                if ($('#xafc_' + n + ' img.trialimg').length > 0) {
                    // get array of all images in xafc
                    dataArray[n]['xafc'] = {};
                    $('#xafc_' + n + ' img.trialimg').each( function(i) {
                        dataArray[n]['xafc'][i] = $(this).attr('title'); 
                    });
                }
            });
            
            $.ajax({
                type: 'POST',
                url: './trials?save&exp_id=<?= $exp_id ?>',
                data: dataArray,
                success: function(response) {
                    <?php if (substr($eInfo['subtype'], 0, 5) == 'adapt') {
                        echo "window.location = 'adapt?id={$exp_id}';";
                    } ?>
                    
                    if (response == "Trials saved.") {
                        window.location = 'info?id=<?= $exp_id ?>';
                    }
                    
                    growl(response);
                }
            });
        });
        
        $( "#fill-from-list" ).button().click(function() { $( "#dialog-form-fill" ).dialog( "open" ); }); 
        $( "#dialog-form-fill" ).dialog({
            autoOpen: false,
            show: "scale",
            hide: "scale",
            height: 350,
            width: 350,
            modal: true,
            buttons: {
                "Fill Trial Names": function() {
                    var bValid = true;

                    if ( bValid ) {
                        var rows = $('#listfill').val().split("\n");
                        
                        for (var i=0; i < rows.length; i++) {
                            var c = '#name_' + (i+1);
                            if ($(c).length > 0 ) { $(c).html(rows[i]); }
                        }
                        $( this ).dialog( "close" );
                    }
                },
<?php if (empty($eInfo['question'])) { ?>
                "Fill Questions": function() {
                    var bValid = true;

                    if ( bValid ) {
                        var rows = $('#listfill').val().split("\n");
                        
                        for (var i=0; i < rows.length; i++) {
                            var c = '#question_' + (i+1);
                            if ($(c).length > 0 ) { $(c).html(rows[i]); }
                        }
                        $( this ).dialog( "close" );
                    }
                },
<?php } 
      if (empty($eInfo['label1']) && $eInfo['exptype'] == 'jnd') { ?>

                "Fill Labels": function() {
                    var bValid = true;

                    if ( bValid ) {
                        var rows = $('#listfill').val().split("\n");
                        
                        for (var i=0; i < rows.length; i++) {
                            var labels = rows[i].split("\t");
                            for (var n=0; n<4; n++) {
                                var c = '#label' + (n+1) + '_' + (i+1);
                                if ($(c).length > 0 ) { $(c).html(labels[n]); }
                            }
                        }
            
                        $( this ).dialog( "close" );
                    }
                },
<?php } ?>
                Cancel: function() {
                    $( this ).dialog( "close" );
                }
            },
        });
    });
    
    function fill(column, start) {
        // auto-fill image columns with selected images
    
        var i = 1;
        // get list of images
        var imagelist = [];
        $('#img_list li').each( function() {
            imagelist[i] = $(this).attr('title');
            i++
        });
        
        if (i == 1) {
            $('<div />').html('Search for images by typing part of the image folder name into the search box above.').dialog('open');
            return false;
        }
        
        // add images to the trial builder
        i = 1;
        var lastTrial = $('#trial_builder div').length;
        
        for (n = start; n <= lastTrial; n++) {
            var theimage = $('#' + column + 'img_' + n);
            
            if (theimage.length == 0) return false; // stop iterating when trials are done
            if (i >= imagelist.length) i = 1; // restart image list if more trials remain
            
            if (imagelist[i].substring(0,7) == '/audio/') {
                theimage.attr('src', '/images/icons/glyphish/icons/icons-theme/264-sound-on@2x');
            } else {
                theimage.attr('src', imagelist[i]);
            }
            theimage.attr('title', imagelist[i]);
            
            theimage.next('span.imgname').html(imgname(imagelist[i]));
            
            i++
        }       
    }
    
    function resizeContent() {
        var content_height = $(window).height() - $('#trial_builder').offset().top - $('#footer').height()-30;
        $('#trial_builder').height(content_height);
        $('#image_chooser').height(content_height);
    }
    
    function addDraggable() {
        $('#img_list li').draggable({
            helper: "clone",
            cursorAt: { top: 0, left: 0 }
        });
    }
    
    var imgToggle = 0;
    function toggleImages(t) {
        // if t == 0, turn off images and turn on list view in image chooser
        // if t == 1, turn off list view and turn on images in image chooser
        
        imgToggle = t;
        
        // make current option unclickable so you dont keep searching
        if (imgToggle == 0) {
            //$('#image_toggle').html("<a href='javascript:toggleImages(1);'>images</a>");
            //$('#list_toggle').html('list');
            $('#trial_builder').addClass('list');
        } else if (imgToggle == 1)  {
            //$('#list_toggle').html("<a href='javascript:toggleImages(0);'>list</a>");
            //$('#image_toggle').html('images');
            $('#trial_builder').removeClass('list');
        }
        
        showImages(50);
    }

    function showImages(max_images) {
        // exit if no search text is found
        if ($('#image_search').val() == "") {
            return false;
        }
    
        // retrieve image list asynchronously
        $.ajax({
            url: 'trials?search', 
            type: 'POST',
            data: $('#image_search').serialize(),
            success: function(resp) {
                if (resp.substr(0,5) == "error") {
                    alert(resp);
                } else {
                    $('#img_list').empty();
                    var id_path = resp.split(";");
                    var len = id_path.length;
                    var plus = '';
                    if (len == 2000) { plus = '+'; }
                    
                    $('#images_found').html(len + plus + '&nbsp;images&nbsp;found');
                    
                    for (var i = 0; i<len; ++i ){
                        var img = id_path[i].split(":");
                        if (imgToggle == 1) {
                            if (img[2] == "audio") {
                                var shortpath = img[1].split("/");
                                $('#img_list').append('<li id="img' + img[0] + '" title="' + img[1] + '"><audio controls="controls" src="' + img[1] + '.ogg" /></audio> ' + shortpath[(shortpath.length - 1)] + '<br /></li>');
                            } else {
                                $('#img_list').append('<li id="img' + img[0] + '" title="' + img[1] + '"><img src="' + img[1] + '" /></li>');
                            }
                            
                            if (i >= max_images) {
                                $('#img_list').append('<a href="javascript:showImages('+(max_images+50)+')">View more...</a>');
                                break;
                                
                            }
                        } else {
                            var shortname = img[1].replace('/stimuli', '');
                            $('#img_list').append('<li id="img' + img[0] + '" title="' + img[1] + '">' + shortname + '</li>');
                        }
                    }
                    
                    // make sure each li displays correctly and is draggable
                    if (imgToggle == 1) $('#img_list li').css('display','inline');
                    if (imgToggle == 0) {
                        $('#img_list li').css('display','block');
                        $('#img_list li:odd').addClass('odd');
                        $('#img_list li:even').addClass('even');
                    }
                    addDraggable();
                }
            }
        }); 
        
    }
    
</script>

<!-- enable instant edits -->
<script src="/include/js/instantedit.js"></script>

<?php

$page->displayFooter();

?>