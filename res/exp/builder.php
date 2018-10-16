<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/exp.php';
auth(array('student', 'researcher', 'admin'));

$title = array(
    '/res/' => 'Researchers',
    '/res/exp/' => 'Experiment',
    '/res/exp/builder' => 'Builder'
);

$styles = array(
    'form' => 'max-width: 100%; width:100%;',
    '#experiment' => 'border-top: 5px solid hsl(200,100%,20%); border-bottom: 5px solid hsl(200,100%,20%); padding: 1em 0;',
    '#change-button-number' => 'position: relative; height: 3em; width: 2em; padding: 0 3em 0 .5em;',
    '#add-button, #delete-button' => 'position: absolute; width: 1.5em; height: 1.5em; color: white; text-align: center; background-color: ' . THEME,
    '#add-button .ui-button-text, #delete-button .ui-button-text' => 'padding: 0;',
    '#add-button' => 'top: -1em; left: 5px;',
    '#delete-button' => 'bottom: -1em; left: 5px;',
    '.button-dv' => 'position: relative; top: -2.5em; left: 2em; width: 0; overflow: visible;',
    'table#experiment_builder' => 'table-layout: auto !important;',
    '.jnd .input_interface td, tr.ranking' => '-moz-user-select: text; -webkit-user-select: text; -ms-user-select: text;',
    '#nImageChanger' => 'float: right; font-size: smaller; margin: .5em 1em;',
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
    
    // make sure user has permission to edit this experiment
    if ($_SESSION['status'] == 'student') {
        // student researchers cannot edit anything
        echo 'You may not edit or create experiments'; exit; 
    } elseif ($_SESSION['status'] == 'researcher') { 
        // researchers can edit only their own experiments
        if (validID($clean['id'])) {
            $myaccess = new myQuery('SELECT user_id FROM access WHERE type="exp" AND id='.$clean['id']." AND user_id=".$_SESSION['user_id']);
            $checkuser = $myaccess->get_assoc(0);
            if ($checkuser['user_id'] != $_SESSION['user_id']) { echo 'You do not have permission to edit this experiment'; exit; }
        }
    }
    
    // update exp table
    $exp_query = sprintf('REPLACE INTO exp 
        (id, name, res_name, exptype, subtype, design, trial_order, side,
        instructions, question, label1, label2, label3, label4, rating_range, low_anchor, high_anchor,
        feedback_query, feedback_specific, feedback_general, labnotes, 
        sex, sexpref, lower_age, upper_age, 
        randomx, password, blurb, forward, default_time, increment_time, create_date)  
        VALUES (%s, "%s", "%s", "%s", "%s", "%s", "%s", "%s", 
        "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", 
        "%s", "%s", "%s","%s", 
        "%s", "%s", "%s", "%s", 
        "%s", "%s", "%s", "%s", "%s", "%s", NOW())',
        (validID($clean['id'])) ? $clean['id'] : 'NULL',
        $clean['i_title'], 
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
        $clean['feedback_general'], 
        $clean['labnotes'], 
        $clean['sex'],
        $clean['sexpref'],
        $clean['lower_age'], 
        $clean['upper_age'],
        $clean['randomx'], 
        $clean['password'], 
        $clean['blurb'], 
        $clean['forward'],
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
    
    // check if data from non-researchers exists and drop old data table
    $make_new_table = false;
    $table_exists = new myQuery('SHOW TABLES WHERE Tables_in_exp="exp_' . $exp_id . '"');
    if (1 == $table_exists->get_num_rows()) {
        $total_participants = new myQuery('SELECT user_id AS c FROM exp_' . $exp_id . ' LEFT JOIN user USING (user_id) WHERE status="guest" OR status="registered"');
        if (0 < $total_participants->get_num_rows()) {
            echo 'alert:There are ' . number_format($total_participants->get_num_rows()) . ' non-researcher participants in this experiment, so a new table was not saved. If you have changed the number of trials in the experiment, you will need to ask Lisa to update the table for you.;';
        } else {
            $make_new_table = true;
            $drop_exp_table = new myQuery('DROP TABLE IF EXISTS exp_' . $exp_id);
        }
    } else {
        $make_new_table = true;
    }
    
    if ($make_new_table) {
        // get experiment data
        $exp_data = new myQuery('SELECT exptype, subtype FROM exp WHERE id=' . $exp_id);
        $types = $exp_data->get_one_array();
        
        $exptype = $types['exptype'];
        $subtype = $types['subtype'];
        
        $trials = $clean['total_images'];
        
        if ($subtype == "large_n") {
            // make by trial table to hold largeN experiment data
            $query = "CREATE TABLE exp_$exp_id (";
            $query .= "user_id int(11) DEFAULT NULL,";
            $query .= "trial INT(6) DEFAULT NULL,";
            if ("sort" == $exptype) { 
                $images_per_trial = $clean['nImages'];
                for ($i=1; $i<= $images_per_trial; $i++) {
                    $query .= "dv_{$i} INT(2) DEFAULT NULL,";
                }
                $query .= "moves INT(3) DEFAULT NULL,";
            } elseif ("motivation" == $exptype) {
                $query .= "up INT(3) DEFAULT NULL,";
                $query .= "down INT(3) DEFAULT NULL,";
            } elseif ("rating" == $exptype && "0" == $clean['rating_range']) {
                $query .= "dv VARCHAR(64) DEFAULT NULL,";
            } else {
                $query .= "dv INT(3) DEFAULT NULL,";
            }
            $query .= "rt INT(6) DEFAULT NULL,";
            if ("xafc" == $exptype || "sort" == $exptype) { 
                $query .= "side VARCHAR(20) DEFAULT NULL,";
            } else {
                $query .= "side enum('same','switch') DEFAULT NULL,";
            }
            $query .= "`order` int(4) DEFAULT NULL,";
            $query .= "dt DATETIME,";
            $query .= "INDEX (user_id, trial)";
            $query .= ") ENGINE=InnoDB";
        } else {
            // make by user table to hold normal experiment data
            $query = "CREATE TABLE exp_$exp_id (";
            $query .= "id int(11) NOT NULL auto_increment,";
            $query .= "user_id int(11) DEFAULT NULL,";
            
            for ($n=1; $n<=$trials; $n++) {
                if ("sort" == $exptype) { 
                    $images_per_trial = $clean['nImages'];
                    for ($i=1; $i<= $images_per_trial; $i++) {
                        $query .= "t{$n}_{$i} INT(2) DEFAULT NULL,";
                    }
                    $query .= "moves$n INT(3) DEFAULT NULL,";
                } elseif ("motivation" == $exptype) {
                    $query .= "up$n INT(3) DEFAULT NULL,";
                    $query .= "down$n INT(3) DEFAULT NULL,";
                } elseif ("rating" == $exptype && "0" == $clean['rating_range']) {
                    $query .= "t$n VARCHAR(64) DEFAULT NULL,";
                } else {
                    $query .= "t$n INT(3) DEFAULT NULL,";
                }
                $query .= "rt$n INT(6) DEFAULT NULL,";
                if ("xafc" == $exptype || "sort" == $exptype) { 
                    $query .= "side$n VARCHAR(20) DEFAULT NULL,";
                } else {
                    $query .= "side$n enum('same','switch') DEFAULT NULL,";
                }
                $query .= "order$n int(3) default NULL,";
            }
            if ("adapt" == $subtype || "adapt_nopre" == $subtype) { $query .= "version TINYINT(1) UNSIGNED DEFAULT 0,"; }
            $query .= "starttime DATETIME,";
            $query .= "endtime DATETIME,";
            $query .= "PRIMARY KEY (id),";
            $query .= "INDEX (user_id)";
            $query .= ") ENGINE=MyISAM";
        } 
        
        $new_table = new myQuery( $query );
        
        // delete extra trials if some have been deleted fro a previous version
        $query = new myQuery("DELETE FROM trial WHERE exp_id=$exp_id AND trial_n>$trials");
    }
    
    // save buttons if a buttons experiment
    if ($clean['exptype'] == 'buttons') {
        $dropbuttons = new myQuery('DELETE FROM buttons WHERE exp_id=' . $exp_id);
        
        $buttons = array();
        for ($i = 1; $i < 20; $i++) {
            if (isset($clean['i_button' . $i])) {
                $buttons[] = sprintf('(%s, %s, "%s", %d)', 
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
    '2afc' => '2-alternative forced choice',
    'jnd' => 'Forced-choice rating (JND)',
    'rating' => 'Rating',
    'buttons' => 'Buttons',
    'interactive' => 'Interactive',
    'xafc' => 'X-alternative forced choice',
    'sort' => 'Sorting',
    'motivation' => 'Motivation',
    //'nback' => 'N-back',
    'other' => 'Custom'
);

$eInfo = array();
if (validID($exp_id)) {
    //$myexp = new experiment($exp_id);
    $query = new myQuery('SELECT * FROM exp WHERE id=' . $exp_id);
    $eInfo = $query->get_assoc(0);
    $eInfo['total_images'] = 0;
    
    $query = new myQuery('DESC exp_' . $exp_id);
    $fields = $query->get_assoc(false, false, 'Field');
    foreach ($fields as $field) {
        if (substr($field, 0, 4) == 'side') { $eInfo['total_images']++; }
    }
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
        $eInfo['total_images'] = $eInfo['randomx'];
    } else {
        $eInfo['total_images'] = $trialInfo['trials'];
    }
} else {
    // default exp info
    $eInfo = array(
        'exptype' => (array_key_exists($_GET['exptype'], $exptype_options)) ? $_GET['exptype'] : 'jnd',
        'subtype' => 'large_n',
        'design' => 'within',
        'trial_order' => 'random',
        'side' => 'random',
        'instructions' => 'Click here to set instructions',
        'question' => 'Click here to set question',
        'name' => 'Name of experiment',
        'label4' => 'Much more',
        'label3' => 'More',
        'label2' => 'Somewhat more',
        'label1' => 'Slightly more',
        'rating_range' => '7',
        'low_anchor' => 'Click Here to Set or Delete Low Anchor',
        'high_anchor' => 'Click Here to Set or Delete High Anchor',
        'randomx' => '20',
        'total_images' => '20',
        'default_time' => '4000',
        'increment_time' => '100',
        'sex' => 'both',
        'sexpref' => 'NULL'
    );
}

$info['id'] = new hiddenInput('id', 'id', $eInfo['id']);

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
    'standard' => 'Standard',
    'large_n' => 'Large N',
    'adapt' => 'Adaptation',
    'adapt_nopre' => 'Adaptation (no pre-test)'
);

if ("2afc" == $eInfo['exptype'] || "buttons" == $eInfo['exptype']) { $subtype_options['speeded'] = 'Speeded decisions'; }
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
$total_images = new input('total_images', 'total_images', $eInfo['total_images']);
$total_images->set_width(50);
$total_images->set_type('number');
$randomx = new input('randomx', 'randomx', $eInfo['randomx']);
$randomx->set_width(50);
$randomx->set_type('number');
$randomx->set_eventHandlers(array('onchange' => '$(\'#randomx_top\').html(this.value);'));
$ci = "Show " . $randomx->get_element() . ' of ' . $total_images->get_element() . ' total images';
$info['images'] = new formElement('images','images');
$info['images']->set_question("Images");
$info['images']->set_custom_input($ci);

// set up limits: sex, sexpref, lower_age, upper_age
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

/*
$info['forward'] = new input('forward', 'forward', $eInfo['forward']);
$info['forward']->set_question('Forward to URL');
$info['forward']->set_width(500);
*/

$info['feedback_general'] = new textarea('feedback_general', 'feedback_general', $eInfo['feedback_general']);
$info['feedback_general']->set_question('General Feedback');
$info['feedback_general']->set_dimensions(500, 50, true, 50, 0, 0);

/*
$info['feedback_specific'] = new textarea('feedback_specific', 'feedback_specific', $eInfo['feedback_specific']);
$info['feedback_specific']->set_question('Specific Feedback (%1$s)<br /><button id="generic_fb">insert generic</button>');
$info['feedback_specific']->set_dimensions(500, 50, true, 50, 0, 0);

$info['feedback_query'] = new textarea('feedback_query', 'feedback_query', $eInfo['feedback_query']);
$info['feedback_query']->set_question('Feedback Query');
$info['feedback_query']->set_dimensions(500, 50, true, 50, 0, 0);
*/

$submit_buttons = array('Save' => 'saveExperiment();');
if (validID($exp_id)) {
    //$submit_buttons['Save as new'] = 'saveNew();';
    $submit_buttons['Feedback Page'] = 'window.open("/fb?type=exp&id=" + $("#id").val());';
    $submit_buttons['Edit Trials'] = 'editTrials()';
}
$submit_buttons['Reset'] = 'window.location.href=window.location.href;';

// set up other info table
$infoTable = new formTable();
$infoTable->set_table_id('myInformation');
$infoTable->set_title('My Information');
$infoTable->set_action('');
$infoTable->set_questionList($info);
$infoTable->set_method('post');
$infoTable->set_buttons($submit_buttons);
$infoTable->set_button_location('bottom');

/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

// editable experiment

?>

<form action='' method='post' id='exp_<?= $exp_id ?>'>  
<h2><span id='title' class='editText' title='Title for participants to see'><?= $eInfo['name'] ?></span></h2>
<p class='instructions'><span id='instructions' class='editText' title='Instructions on page before the experiment starts'><?= htmlspecialchars($eInfo['instructions']) ?></span></p>
<input type='hidden' name='exp_id' id='exp_id' value='<?= $exp_id ?>' />
<div id="experiment">
<div id='question'><span id='trial_question' class='editText' title='Question to be displayed at the top of each trial. Leave blank for a different question for each trial.'><?= $eInfo['question'] ?></span></div>
<table id="experiment_builder" class="<?= ('2afc' == $eInfo['exptype']) ? 'tafc' : $eInfo['exptype'] ?>">

<?php

/****************************************************/
/* !    Input Interface */
/***************************************************/

$text = '';
switch ($eInfo['exptype']) {
    case '2afc': break;
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
        $text .= '      <td id="center_col" style="display:none;"></td>' . ENDLINE;
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
    case 'motivation':
        $text.= '';
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
    case 'buttons':
    case 'rating':
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

if (is_array($trialInfo) && array_key_exists('nimages', $trialInfo)) {
    $nImages = $trialInfo['nimages'];
}

 $min_max_images = array(
    '2afc'      => array(2, 3),
    'jnd'       => array(2, 3),
    'rating'    => array(1, 3),
    'buttons'   => array(1, 3),
    'xafc'      => array(3, 10),
    'sort'      => array(2, 10),
    'nback'     => array(1, 1),
    'other'     => array(1, 10),
    'motivation' => array(1, 1),
    'interactive' => array(1, 1),
);

$fb_queries = array(
    '2afc'      => "ROUND(AVG(t1+t2+t3+t4+t5+t6+t7+t8+t9+t10)/10*100) as avg_score, ROUND(AVG(IF(@myid=id, t1+t2+t3+t4+t5+t6+t7+t8+t9+t10, NULL))/10*100) as my_score",
    'jnd'       => "ROUND(AVG((t1>3)+(t2>3)+(t3>3)+(t4>3)+(t5>3)+(t6>3)+(t7>3)+(t8>3)+(t9>3)+(t10>3))/10*100) as avg_score, ROUND(AVG(IF(@myid=id, (t1>3)+(t2>3)+(t3>3)+(t4>3)+(t5>3)+(t6>3)+(t7>3)+(t8>3)+(t9>3)+(t10>3), NULL))/10*100) as my_score",
    'rating'    => "ROUND(AVG(t1+t2+t3+t4+t5+t6+t7+t8+t9+t10)/10, 1) as avg_score, ROUND(AVG(IF(@myid=id, t1+t2+t3+t4+t5+t6+t7+t8+t9+t10, NULL))/10, 1) as my_score",
    'buttons'   => "ROUND(AVG((t1=1)+(t2=1)+(t3=1)+(t4=1)+(t5=1)+(t6=1)+(t7=1)+(t8=1)+(t9=1)+(t10=1))/10*100) as avg_score, ROUND(AVG(IF(@myid=id, (t1=1)+(t2=1)+(t3=1)+(t4=1)+(t5=1)+(t6=1)+(t7=1)+(t8=1)+(t9=1)+(t10=1), NULL))/10*100) as my_score",
    'xafc'      => "ROUND(AVG((t1=1)+(t2=1)+(t3=1)+(t4=1)+(t5=1)+(t6=1)+(t7=1)+(t8=1)+(t9=1)+(t10=1))/10*100) as avg_score, ROUND(AVG(IF(@myid=id, (t1=1)+(t2=1)+(t3=1)+(t4=1)+(t5=1)+(t6=1)+(t7=1)+(t8=1)+(t9=1)+(t10=1), NULL))/10*100) as my_score",
    'sort'      => "NO DEFAULT QUERY",
    'nback'     => "NO DEFAULT QUERY",
    'other'     => "NO DEFAULT QUERY",
    'motivation' => "NO DEFAULT QUERY",
    'interactive' => "NO DEFAULT QUERY",
);

$fb_specifics = array(
    '2afc'      => 'On average, people chose the XXXX face %1$s%% of the time. You chose the XXXX face %2$s%% of the time.',
    'jnd'       => 'On average, people chose the XXXX face %1$s%% of the time. You chose the XXXX face %2$s%% of the time.',
    'rating'    => 'On average, people rated the faces %1$s. You rated the faces %2$s.',
    'buttons'   => 'On average, people chose the XXXX button %1$s%% of the time. You chose the XXXX button %2$s%% of the time.',
    'xafc'      => 'On average, people chose the XXXX face %1$s%% of the time. You chose the XXXX face %2$s%% of the time.',
    'sort'      => "NO DEFAULT QUERY",
    'nback'     => "NO DEFAULT QUERY",
    'other'     => "NO DEFAULT QUERY",
    'motivation' => "NO DEFAULT QUERY",
    'interactive' => "NO DEFAULT QUERY",
);

?>

</tr>

</table>

Trial x of <span id="randomx_top"><?= $eInfo['randomx'] ?></span>
</div>

<div id="nImageChanger">Number of images to display: 
    <button id="add_image">+</button>
    <button id="delete_image">-</button>
</div>

</form>

<div id="help" title="Experiment Builder Help">
    <h1>Editing the experiment</h1>
    <ul>
        <li>Click on the title, instructions, and question to edit them.</li>
        <li>You can increase or decrease the number of images shown on each trial (for some types of experiments) by clicking on the + and - buttons in the lower right corner.</li>
    </ul>
</div>

<?= $infoTable->print_form() ?>

<!--**************************************************-->
<!-- !Javascripts for this page -->
<!--**************************************************-->

<script>
    var nImages = <?= $nImages ?>;
    var minImages = <?= $min_max_images[$eInfo['exptype']][0] ?>;
    var maxImages = <?= $min_max_images[$eInfo['exptype']][1] ?>;
    
    var defaultTime = 4000;
    
    $(function() {
    
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
        
        $('#total_images').change( function() {
            if (this.value > 300 && $('#subtype').val() != 'large_n') { 
                $('#subtype').val('large_n');
                alert('Subtype must be Large N or total images must be <300'); 
            }
        });
        $('#subtype').change(function() {
            if ($('#total_images').val() > 300 && this.value != 'large_n') {
                alert('Subtype must be Large N or total images must be <300'); 
                $('#total_images').val(300);
            }
        });
                
        $('#nImageChanger').buttonset();
        
        $('#add_image').click( function() {
            if (nImages <= maxImages) { 
                nImages++;
                viewImages();
                
                $('#delete_image').button('enable');
                if (nImages == maxImages) $(this).button('disable');
            } else {
                $('<div />').html("You can't show more than " + maxImages + " images.")
                             .dialog({ buttons: { "OK": function() {$(this).dialog('close');} } });
            }
            return false;
        });
        
        $('#delete_image').click( function() {
            if (nImages > minImages) { 
                nImages--;
                viewImages();
                
                $('#add_image').button('enable');
                if (nImages == minImages) $(this).button('disable');
            } else {
                $('<div />').html("You must show at least " + minImages + " image(s).")
                             .dialog({ buttons: { "OK": function() {$(this).dialog('close');} } });
            }
            return false;
        });
        
        // disable image buttons is at max or min
        if (nImages == minImages) $('#delete_image').button('disable');
        if (nImages == maxImages) $('#add_image').button('disable');
        
    }); // end of $(function(){})
    

    function viewImages() {
        if ($('table.xafc').length > 0 || $('table.sort').length > 0) {
            $('#center_image').html('');
            for (var i = 0; i < nImages; i++) {
                $('#center_image').append('<img src="/stimuli/blankface"/>');
            }
            $('td.xafc img').css('width', (100/nImages)-2 + '%').css('min-width', '18%');
        } else if (nImages == 1) {
            $('#left_image').hide();
            $('#center_image').show();
            if ($('#center_col')) $('#center_col').show();
            $('#right_image').hide();
        } else if (nImages == 2) {
            $('#left_image').show();
            $('#center_image').hide();
            if ($('#center_col')) $('#center_col').hide();
            $('#right_image').show();
        } else if (nImages == 3) {
            $('#left_image').show();
            $('#center_image').show();
            if ($('#center_col')) $('#center_col').show();
            $('#right_image').show();
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
        
        $('#maincontent form').each( function(e) {
            formData[formData.length] = $(this).serialize(false);
        });
    
        $.ajax({
            url: './builder?save',
            type: 'POST',
            data: formData[0] + '&' + formData[1] + '&nImages=' + nImages,
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