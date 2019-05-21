<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/exp.php';
auth($RES_STATUS);

if (validID($_GET['id']) && !permit('exp', $_GET['id'])) header('Location: /res/');

$title = array(
    '/res/' => 'Researchers',
    '/res/exp/' => 'Experiment',
    '/res/exp/builder' => 'Builder'
);

$styles = array(
    'form' => 'max-width: 100%; width:100%;',
    '#change-button-number' => 'position: relative; height: 3em; width: 2em; padding: 0 3em 0 .5em;',
    '#add-button, #delete-button' => 'position: absolute; width: 1.5em; height: 1.5em; color: white; text-align: center; background-color: ' . THEME,
    '#add-button .ui-button-text, #delete-button .ui-button-text' => 'padding: 0;',
    '#add-button' => 'top: -1em; left: 5px;',
    '#delete-button' => 'bottom: -1em; left: 5px;',
    '.button-dv' => 'position: relative; top: -2.5em; left: 2em; width: 0; overflow: visible;',
    'table#experiment_builder' => 'table-layout: auto !important;',
    '.jnd .input_interface td, tr.ranking' => '-moz-user-select: text; -webkit-user-select: text; -ms-user-select: text;',
    //'#nImageChanger' => 'float: right; font-size: smaller; margin: .5em 1em;',
    '#motiv_info' => 'position: absolute; 
                      text-align: left; 
                      border: 5px solid white; 
                      border-radius: 10px; 
                      box-shadow: 4px 4px 6px rgba(0,0,0,0.5); 
                      padding: 1em 0; 
                      background-color: '. THEME .'; 
                      color: white;',
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
);

/****************************************************
 * AJAX Responses
 ***************************************************/
 
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
    //'motivation' => 'Motivation',
    //'nback' => 'N-back',
    //'other' => 'Custom'
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

if (is_array($trialInfo) && array_key_exists('nimages', $trialInfo)) {
    $nImages = $trialInfo['nimages'];
} else {
    switch ($eInfo['exptype']) {
        case '2afc': 
        case 'jnd':
            $nImages = 2;
            break;
        case 'buttons':
        case 'rating':
        case 'motivation':
        case 'slideshow':
        case 'slider':
            $nImages = 1;
            break;
        case 'xafc':
            $nImages = 3;
            break;
        case 'sort':
            $nImages = 5;
            break;
    } 
}

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

?>

<?= $infoTable->print_form() ?>

<form action='' method='post' id='exp_<?= $exp_id ?>'>
    
<div id="tabs">
  <ul>
    <li><a href="#intropage">Introduction</a></li>
    <li><a href="#exppage">Experiment</a></li>
    <li><a href="#fbpage">Feedback</a></li>
  </ul>
  
  <div id="intropage">
    <div class='instructions'><span id='instructions' 
        class='editText md' 
        title='Instructions on page before the experiment starts'><?= htmlspecialchars($eInfo['instructions']) ?></span></div>
    <input type='hidden' name='exp_id' id='exp_id' value='<?= $exp_id ?>' />
  </div>
  
  <div id="exppage">
    <div id="experiment">
        <div id='question'><span id='trial_question' 
            class='editText md' 
            title='Question to be displayed at the top of each trial. Leave blank for a different question for each trial.'><?= $eInfo['question'] ?></span></div>
        <table id="experiment_builder" class="<?= ('2afc' == $eInfo['exptype']) ? 'tafc' : $eInfo['exptype'] ?>">

<?php

/****************************************************/
/* !    Input Interface */
/***************************************************/

$text = '';
switch ($eInfo['exptype']) {
    case '2afc': break;
    case 'slideshow': break;
    case 'motivation': break;
    case 'xafc': break;
    case 'jnd':
        $text .= '  <tr class="input_interface">' . ENDLINE;
        $text .= '      <td><span class="editText" id="label4" onchange="$(\'#label4b\').html($(\'#label4_field\').val());">' 
                        . $eInfo['label4'] . '</span></td>' . ENDLINE;
        $text .= '      <td><span class="editText" id="label3" onchange="$(\'#label3b\').html($(\'#label3_field\').val());">' 
                        . $eInfo['label3'] . '</span></td>' . ENDLINE;
        $text .= '      <td><span class="editText" id="label2" onchange="$(\'#label2b\').html($(\'#label2_field\').val());">' 
                        . $eInfo['label2'] . '</span></td>' . ENDLINE;
        $text .= '      <td><span class="editText" id="label1" onchange="$(\'#label1b\').html($(\'#label1_field\').val());">' 
                        . $eInfo['label1'] . '</span></td>' . ENDLINE;
        $text .= '      <td class="center" id="center_col" style="display:none;"></td>' . ENDLINE;
        $text .= '      <td><span class="editText" id="label1b" onchange="$(\'#label1\').html($(\'#label1b_field\').val());">' 
                        . $eInfo['label1'] . '</span></td>' . ENDLINE;
        $text .= '      <td><span class="editText" id="label2b" onchange="$(\'#label2\').html($(\'#label2b_field\').val());">' 
                        . $eInfo['label2'] . '</span></td>' . ENDLINE;
        $text .= '      <td><span class="editText" id="label3b" onchange="$(\'#label3\').html($(\'#label3b_field\').val());">' 
                        . $eInfo['label3'] . '</span></td>' . ENDLINE;
        $text .= '      <td><span class="editText" id="label4b" onchange="$(\'#label4\').html($(\'#label4b_field\').val());">' 
                        . $eInfo['label4'] . '</span></td>' . ENDLINE;
        $text .= '  </tr>' . ENDLINE;
        break;
    case 'rating':
        $text .= '  <tr class="input_interface">' . ENDLINE;
        $text .= '      <td colspan="3">' . ENDLINE;
        $text .= '          <span class="editText" id="low_anchor">' . $eInfo['low_anchor'] . '</span>' . ENDLINE;
        $text .= '              <input type="text" class="rating" name="rating_range" id="rating_range" value="' . $eInfo['rating_range'] . '" />' . ENDLINE;
        $text .= '          <span class="editText" id="high_anchor">' . $eInfo['high_anchor'] . '</span>' . ENDLINE;
        $text .= '      </div></td>' . ENDLINE;
        $text .= '  </tr>' . ENDLINE;
        break;
    case 'slider':
        $text .= '  <tr class="input_interface">' . ENDLINE;
        $text .= '      <td colspan="3">' . ENDLINE;
        $text .= '          <span class="editText" id="low_anchor">' . $eInfo['low_anchor'] . '</span>' . ENDLINE;
        $text .= '              <div id="exp_slider" /><div class="slider_handle ui-slider-handle"></div></div>' . ENDLINE;
        $text .= '          <span class="editText" id="high_anchor">' . $eInfo['high_anchor'] . '</span>' . ENDLINE;
        $text .= '      </div></td>' . ENDLINE;
        $text .= '  </tr>' . ENDLINE;
        break;
    case 'buttons':
        $text .= '  <tr class="input_interface">' . ENDLINE;    
        $text .= '      <td colspan="3"><div class="buttons">' . ENDLINE;
        $text .= '          <span class="editText" id="low_anchor">' . $eInfo['low_anchor'] . '</span>' . ENDLINE;
        
        if ($eInfo['id']) {
            $button_query = new myQuery('SELECT dv, display FROM buttons WHERE exp_id=' . $eInfo['id'] . " ORDER BY n");
            foreach ($button_query->get_assoc() as $b) {
                $buttons[$b['dv']] = $b['display'];
            }
        } else {
            // default button array
            $buttons = array(
                1 => '1',
                2 => '2',
                3 => '3',
                4 => '4',
                5 => '5',
                6 => '6',
                7 => '7'
            );
        }
        $text .= "<span class='button-dv'>DV:</span>";
        $n = 0;
        foreach ($buttons as $dv => $display) {
            $n++;
            //$text .= "        <input type='button' value='{$display}' onclick='nextTrial({$dv})'/>" . ENDLINE;
            $text .= "      <span class='editText button-dv' id='button-dv$n'>$dv</span><span class='button-wrapper'><span class='editText' id='button$n'>$display</span></span>" . ENDLINE;
        }
        $text .= '          <span id="change-button-number"><button id="delete-button">-</button><button id="add-button">+</button></span>' . ENDLINE;
        $text .= '          <span class="editText" id="high_anchor">' . $eInfo['high_anchor'] . '</span>' . ENDLINE;
        $text .= '      </div></td>' . ENDLINE;
        $text .= '  </tr>' . ENDLINE;
        break;
}
echo $text;


/****************************************************/
/* !    Image Display Interface */
/***************************************************/
echo '  <tr class="exp_images">' . ENDLINE;
switch ($eInfo['exptype']) {
    case '2afc': 
        echo '      <td id="left_image"><img src="/stimuli/blankface" /></td>' . ENDLINE;
        echo '      <td id="center_image" style="display:none;"><img src="/stimuli/blankface"/></td>' . ENDLINE;
        echo '      <td id="right_image"><img src="/stimuli/blankface" /></td>' . ENDLINE;
        $nImages = 2;
        break;
    case 'jnd':
        echo '      <td id="left_image" colspan="4"><img src="/stimuli/blankface" /></td>' . ENDLINE;
        echo '      <td id="center_image" style="display:none;"><img src="/stimuli/blankface"/></td>' . ENDLINE;
        echo '      <td id="right_image" colspan="4"><img src="/stimuli/blankface" /></td>' . ENDLINE;
        $nImages = 2;
        break;
    case 'slideshow': 
    case 'buttons':
    case 'rating':
    case 'slider':
        echo '      <td id="left_image" style="display:none;"><img src="/stimuli/blankface" /></td>' . ENDLINE;
        echo '      <td id="center_image"><img src="/stimuli/blankface"/></td>' . ENDLINE;
        echo '      <td id="right_image" style="display:none;"><img src="/stimuli/blankface" /></td>' . ENDLINE;
        $nImages = 1;
        break;
    case 'xafc':
        echo '      <td id="center_image" class="xafc">' .ENDLINE;
        echo '          <img src="/stimuli/blankface"/>' . ENDLINE;
        echo '          <img src="/stimuli/blankface"/>' . ENDLINE;
        echo '          <img src="/stimuli/blankface"/>' . ENDLINE;
        echo '      </td>' . ENDLINE;
        $nImages = 3;
        break;
    case 'sort':
        echo '      <td id="center_image" class="sort">' .ENDLINE;
        echo '          <img src="/stimuli/blankface"/>' . ENDLINE;
        echo '          <img src="/stimuli/blankface"/>' . ENDLINE;
        echo '          <img src="/stimuli/blankface"/>' . ENDLINE;
        echo '          <img src="/stimuli/blankface"/>' . ENDLINE;
        echo '          <img src="/stimuli/blankface"/>' . ENDLINE;
        echo '      </td>' . ENDLINE;
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
        
    
        echo '      <td><dl id="motiv_info">' . ENDLINE;
        echo '          <dt>Default time</dt><dd> ' . $default_time->get_element() . '</dd>' . ENDLINE;
        echo '          <dt>Increment time</dt><dd> ' . $increment_time->get_element() . '</dd>' . ENDLINE;
        echo '      </dl></td>' . ENDLINE;
        echo '      <td><div id="motivation-container">' . ENDLINE;
        echo '          <span id="countdownlabels">7 &amp; 8 &uarr;<br />1 &amp; 2 &darr;</span>' . ENDLINE;
        echo '          <div id="countdown"></div>' . ENDLINE;
        echo '          <img  id="center_image" class="motivation" src="/stimuli/blankface"/>' . ENDLINE;
        echo '      </div></td>' . ENDLINE;
        $nImages = 1;
        break;
    case 'interactive':
        $nImages = 1;
        break;
} 

?>

</tr>

</table>

Trial x of <span id="random_stim_top"><?= $eInfo['random_stim'] ?></span>
</div>

</div>

    <div id="fbpage">
        <p class='instructions'><span id='feedback_general' 
            class='editText md' 
            title='Feedback'><?= htmlspecialchars($eInfo['feedback_general']) ?></span></p>
    </div>
</div>
</form>

<div id="help" title="Experiment Builder Help">
    <h1>Editing the experiment</h1>
    <ul>
        <li>Click on the title, instructions, and question to edit them.</li>
        <li>You can increase or decrease the number of images shown on each trial (for some types of experiments) by clicking on the + and - buttons in the lower right corner.</li>
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
        
        $('#delete-button').click( function() {
            // remove a button from the buttons interface if there are more than 1 left
            if ($('div.buttons .button-wrapper').length > 1) {
                $('div.buttons .button-wrapper:last').remove();
                $('div.buttons .button-dv:last').remove();
            } else {
                growl('You must have at least 1 button');
            }
            return false;
        });
        
        $('#add-button').click( function() {
            // add a button to the buttons interface
            var button_n = $('div.buttons .button-wrapper').length + 1;
            $('div.buttons .button-wrapper:last').after("\n<span class='editText button-dv' id='button-dv" + button_n + "'>" + button_n + "</span><span class='button-wrapper'><span class='editText' id='button" + button_n + "'>" + button_n + "</span></span>");
            editbox_init();
            return false;
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
</script>

<!-- enable instant edits -->
<script src="/include/js/instantedit.js"></script>

<?php

$page->displayFooter();

?>