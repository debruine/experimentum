<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';
auth($RES_STATUS);

if (validID($_GET['id']) && !permit('quest', $_GET['id'])) header('Location: /res/');

$title = array(
    '/res/' => 'Researchers',
    '/res/quest/' => 'Questionnaires',
    '/res/quest/builder' => 'Builder'
);

$styles = array(
    '.newQ .varinfo' => 'color: red;',
    '.newQ .qid' => 'display: none;',
    '.qid' => 'font-size: 75%;',
    'div.select, div.selectnum, div.input, div.textarea, div.radio, div.radiorow' => 'display: none',
    'textarea#feedback_query, textarea#feedback_graph' => 'font: 10px Monaco, monospace;',
    '#excel_input' => 'display: none; width: 80%; margin: 1em auto; font-size: smaller;',
    '#excel_input .buttons' => 'display: none;',
    '#excel_paste' => 'width: 100%; height:100px;',
    '#excel_input p' => 'width:auto; max-width:100%;',
    '#excel_box' => 'width: 100%;',
    '.excel th' => 'height: 1em; border-right: 2px solid white;',
    '#quest_table input[type="text"]' => 'float: left; clear: left; width: 100%;',
    '.varinfo' => 'position: absolute; margin-left: -200px; width: 190px; text-align: right;',
    'td.typechanger' => 'position: absolute; margin-right: -200px; width: 210px; text-align: left !important; padding-left: 10px;',
    'td.typechanger span' => 'display: inline-block; height: 28px; width: 28px; border-radius: 14px; float: left; 
                              background: transparent center center no-repeat;',
    'td.typechanger select' => 'float: left;',
    '#add-radio-option' => 'font-size: 0;background: transparent center center no-repeat url(/images/linearicons/plus-circle) !important;',
    '#delete-radio-option' => 'font-size: 0;background: transparent center center no-repeat url(/images/linearicons/circle-minus) !important;',
    'span.view-typechooser' => 'background-image: url(/images/linearicons/cog) !important;',
    'span.edit_question' => 'background-image: url(/images/linearicons/pencil) !important;',
    'span.delete_question' => 'display: inline-block; margin-left: 2em; background-image: url(/images/linearicons/trash) !important;',
    '.view-typechooser select' => 'display: none; position: relative; left: -13em; width: 14em;',
    '.move_anchor' => 'display: inline-block; width: 16px; height: 20px;',
    'form' => 'margin-left: 200px; margin-right: 200px; max-width: none; width: auto;',
    '.addRadio, .subRadio' => 'display: inline-block; 
                                width: 20px; height: 20px; 
                                border-radius: 13px; 
                                border: 3px solid white; 
                                box-shadow: 2px 2px 4px rgba(0,0,0,.5); 
                                background-color: '. THEME .'; 
                                color: white; 
                                font-size: 20px; line-height: 20px; 
                                text-align: center;',
    'td.input.beingedited' => 'background-color: hsl(60,100%, 90%);',
    '.slider_settings input' => 'width: 3em; display: inline;',
    '.slider_settings' => 'width: 15em;'
);

/****************************************************/
/* !AJAX Responses */
/****************************************************/

// !    Save Questionnaire 
if (array_key_exists('save', $_GET)) {
    $clean = my_clean($_POST['quest']);
    $cleanq = my_clean($_POST['questions']);
    $cleanr = my_clean($_POST['radiorow']);
    
    // update quest table
    if (!validID($clean['id'])) {
        $query = sprintf('INSERT INTO quest 
            (name, res_name, questtype, quest_order, 
            instructions, feedback_query, feedback_specific, feedback_general, 
            labnotes, sex, lower_age, upper_age, create_date)  
            VALUES ("%s", "%s", "%s", "%s", 
                    "%s", "%s", "%s", "%s", 
                    "%s", "%s", "%s", "%s", NOW())',
            $clean['name'], 
            $clean['res_name'], 
            $clean['questtype'], 
            $clean['quest_order'],  
            $clean['instructions'], 
            $clean['feedback_query'], 
            $clean['feedback_specific'], 
            $clean['i_feedback_general'], 
            $clean['labnotes'], 
            $clean['sex'], 
            $clean['lower_age'], 
            $clean['upper_age']
        );
    } else {
        $query = sprintf('UPDATE quest SET 
            name="%s", 
            res_name="%s", 
            questtype="%s", 
            quest_order="%s", 
            instructions="%s", 
            feedback_query="%s", 
            feedback_specific="%s", 
            feedback_general="%s", 
            labnotes="%s", 
            sex="%s", 
            lower_age="%s", 
            upper_age="%s" 
            WHERE id="%s"',
            $clean['name'], 
            $clean['res_name'], 
            $clean['questtype'], 
            $clean['quest_order'], 
            $clean['instructions'], 
            $clean['feedback_query'], 
            $clean['feedback_specific'], 
            $clean['feedback_general'], 
            $clean['labnotes'], 
            $clean['sex'],  
            $clean['lower_age'], 
            $clean['upper_age'],
            $clean['id']
        );
    }
    
    $query = str_replace('""', 'NULL', $query);
    $query = str_replace('"NULL"', 'NULL', $query);
    $quest = new myQuery($query);
    
    
    // set quest_id
    $quest_id = (validID($clean['id'])) ? $clean['id'] : $quest->get_insert_id();
    echo $quest_id . ';Questionnaire table updated: quest_' . $quest_id . ENDLINE;
    
    // update access table
    $update_access = new myQuery('REPLACE INTO access (type, id, user_id) 
                                      VALUES ("quest", '.$quest_id.', '.$_SESSION['user_id'].')');
                                      
    
    // get radiorow options if radiorow type
    if ($clean['questtype'] == 'radiopage') {
        $ropt = array();
        foreach ($cleanr as $opt) {
            $n = $opt['opt_order'];
            $v = $opt['value'];
            $d = $opt['display'];
            $ropt[] = "($quest_id, $n, '$v', '$d')";
        }
        
        $rr_query = new myQuery('DELETE FROM radiorow_options WHERE quest_id=' . $quest_id);
        $rr_query = new myQuery('INSERT INTO radiorow_options (quest_id, opt_order, opt_value, display) VALUES ' . implode(', ', $ropt));
        echo '<br />Radiorow options table updated: ' . $rr_query->get_affected_rows() . ' options' . ENDLINE;
    }

    // update questions table
    $query = new myQuery("DELETE FROM question WHERE quest_id=" . $quest_id);
    $query = new myQuery("DELETE FROM options WHERE quest_id=" . $quest_id);
    foreach ($cleanq as $i => $q) {
        $question_update_query = sprintf('REPLACE INTO question (quest_id, id, n, name, question, 
                                                                 type, maxlength, low_anchor, high_anchor, 
                                                                 startnum, endnum, step) 
                                          VALUES ("%s", %s, %s, "%s", "%s", 
                                                  "%s", %s, "%s", "%s", 
                                                  %s, %s, %s)' . ENDLINE,
            $quest_id,
            $q['newQ']=='true' ? 'NULL' : $q['id'],
            intval($q['n']),
            $q['name'],
            $q['question'],
            ($clean['questtype'] == 'ranking') ? 'ranking' : $q['type'],
            intval($q['maxlength']),
            $q['low_anchor'],
            $q['high_anchor'],
            floatval($q['startnum']),
            floatval($q['endnum']),
            floatval($q['step'])
        );
        
        $query = new myQuery($question_update_query);
        if ($q['newQ']=='true') {
            $q['id'] = $query->get_insert_id();
            $cleanq[$i]['id'] = $q['id'];
        }
        
        if (is_array($q['options'])) {
            $option_updates = array();
            foreach ($q['options'] as $opt) {
                $option_updates[] = sprintf('("%s", "%s", "%s", "%s", "%s")' . ENDLINE,
                    $quest_id,
                    $q['id'],
                    $opt['opt_order'],
                    $opt['value'],
                    $opt['display']
                );
            }

            $option_update_query = 'REPLACE INTO options (quest_id, q_id, opt_order, opt_value, display) VALUES ' . implode(',', $option_updates);
            $query = new myQuery($option_update_query);
        }
    }   
    
    
    echo '<br />Question and options tables updated'. ENDLINE;
    
    exit;
}

/***************************************************/
/* !Set Up Questionnaire from quest table */
/***************************************************/
 
$quest_id=$_GET['id'];

$qInfo = array(
    'questtype' => NULL,
    'quest_order' => 'random',
    'instructions' => '*Click here* to set instructions',
    'feedback_general' => 'Click here* to edit the **feedback page**. You can use [markdown](https://codepen.io/nmtakay/pen/gscbf) or html to format your feedback page.

## Make a list with numbers or asterisks

1. First item
2. Second item
  * sub item
  * sub item
3. Third item'
);
if (validID($quest_id)) {
    $query = new myQuery('SELECT * FROM quest WHERE id=' . $quest_id);
    $qInfo = $query->get_assoc(0);
} elseif (array_key_exists('radiopage', $_GET)) {
    // default quest info for radiopage
    $qInfo['questtype'] = 'radiopage';
} elseif (array_key_exists('ranking', $_GET)) {
    // default quest info for ranking
    $qInfo['questtype'] = 'ranking';
} else {
    // default quest info for mixed
    $qInfo['questtype'] = 'mixed';
}


if (validID($quest_id)) {
    $question_data = new myQuery('SELECT * FROM question WHERE quest_id='.$quest_id.' ORDER BY n');
    $questions = $question_data->get_assoc(false, 'n');
} else {
    $query = new myQuery('SELECT MAX(id) as m FROM quest');
    $max_quest_id = $query->get_assoc(0);
}
if (count($questions) == 0) {
    $query = new myQuery('SELECT MAX(id) as m FROM question');
    $max_q_id = $query->get_assoc(0);
    
    if (array_key_exists('radiopage', $_GET) || $qInfo['questtype'] == 'radiopage') {
        // default question for a radiopage questionnaire
        $questions = array(1 => array(
            'id'            => $max_q_id['m'] + 1,       
            'quest_id'      => $max_quest_id['m'] + 1,
            'n'             => 1,      
            'name'          => 'dv_name',   
            'question'      => 'Click here to type your question',
            'type'          => 'radiorow',  
            'maxlength'     => null,
            'include_path'  => null,
            'low_anchor'    => null,
            'high_anchor'   => null
        ));
    } else if (array_key_exists('ranking', $_GET) || $qInfo['questtype'] == 'ranking') {
        // default question for a ranking questionnaire
        $questions = array(1 => array(
            'id'            => $max_q_id['m'] + 1,       
            'quest_id'      => $max_quest_id['m'] + 1,
            'n'             => 1,      
            'name'          => 'dv_name',   
            'question'      => 'Click here to type your question',
            'type'          => 'ranking',
            'maxlength'     => null,
            'include_path'  => null,
            'low_anchor'    => null,
            'high_anchor'   => null
        ));
    } else {
        // default question for a mixed questionnaire
        $questions = array(1 => array(
            'id'            => $max_q_id['m'] + 1,       
            'quest_id'      => $max_quest_id['m'] + 1,
            'n'             => 1,      
            'name'          => 'dv_name',   
            'question'      => 'Click here to type your question',
            'type'          => 'text', 
            'maxlength'     => 10,
            'include_path'  => null,
            'low_anchor'    => null,
            'high_anchor'   => null
        ));
    }
}

/***************************************************/
/* !Other Questionnaire Information */
/***************************************************/
 
$info = array();

$info['id'] = new hiddenInput('id', 'id', $qInfo['id']);

// name for users
$info['name'] = new input('name', 'name', $qInfo['name']);
$info['name']->set_question('Name for Users');
$info['name']->set_width(500);

$info['res_name'] = new input('res_name', 'res_name', $qInfo['res_name']);
$info['res_name']->set_question('Name for Researchers');
$info['res_name']->set_width(500);

$info['questtype'] = new hiddenInput('questtype', 'questtype', $qInfo['questtype']);

$info['questtype2'] = new msgRow('questtype', 'questtype', $qInfo['questtype']);
$info['questtype2']->set_question('Questionnaire Type');

#$info['questtype'] = new radio('questtype', 'questtype', $qInfo['questtype']);
#$info['questtype']->set_question('Questionnaire Type');
#$info['questtype']->set_options(array('mixed'=>'mixed', 'radiopage'=>'page of radio buttons', 'ranking'=>'ranking items'));
#$info['questtype']->set_orientation('horiz'); 

$info['quest_order'] = new radio('quest_order', 'quest_order', $qInfo['quest_order']);
$info['quest_order']->set_question('Question Order');
$info['quest_order']->set_options(array('fixed'=>'fixed', 'random'=>'random'));
$info['quest_order']->set_orientation('horiz'); 

// set up limits: sex, lower_age, upper_age
$sex = new select('sex', 'sex', $qInfo['sex']);
$sex->set_options(array(
    'both' => 'All genders',
    'male' => 'Men only',
    'female' => 'Women only'
));
$sex->set_null(false);
$lower_age = new selectnum('lower_age', 'lower_age', $qInfo['lower_age']);
$lower_age->set_options(array('NULL'=>'any'), 0, 100);
$lower_age->set_null(false);
$upper_age = new selectnum('upper_age', 'upper_age', $qInfo['upper_age']);
$upper_age->set_options(array('NULL'=>'any'), 0, 100);
$upper_age->set_null(false);
$ci = $sex->get_element() . 
    ' aged ' . $lower_age->get_element() . 
    ' to ' . $upper_age->get_element();
$info['limits'] = new formElement('limits','limits');
$info['limits']->set_question('Limited to');
$info['limits']->set_custom_input($ci);

$info['labnotes'] = new textarea('labnotes', 'labnotes', $qInfo['labnotes']);
$info['labnotes']->set_question('Labnotes');
$info['labnotes']->set_dimensions(500, 50, true, 50, 0, 0);

/*
$info['feedback_general'] = new textarea('feedback_general', 'feedback_general', $qInfo['feedback_general']);
$info['feedback_general']->set_question('General Feedback');
$info['feedback_general']->set_dimensions(500, 50, true, 50, 0, 0);

$info['feedback_specific'] = new textarea('feedback_specific', 'feedback_specific', $qInfo['feedback_specific']);
$info['feedback_specific']->set_question("Specific Feedback");
$info['feedback_specific']->set_dimensions(500, 50, true, 50, 0, 0);

$info['feedback_query'] = new textarea('feedback_query', 'feedback_query', $qInfo['feedback_query']);
$info['feedback_query']->set_question("Feedback Query");
$info['feedback_query']->set_dimensions(500, 50, true, 50, 0, 0);
*/

// set up other info table
$infoTable = new formTable();
$infoTable->set_table_id('myInformation');
$infoTable->set_title('My Information');
$infoTable->set_action('');
$infoTable->set_questionList($info);
$infoTable->set_method('post');
$infoTable->set_buttons(array(
    'Save Questionnaire' => 'saveQuestionnaire();',
    'Reset' => 'window.location.href=window.location.href;'
));
$infoTable->set_button_location('top');

/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

echo $infoTable->print_form();

// !    editable questionnaire

echo '<div id="tabs">
  <ul>
    <li><a href="#questpage">Questionnaire</a></li>
    <li><a href="#fbpage">Feedback</a></li>
  </ul>
  
  <div id="questpage">';
  echo "<form action='' method='post' id='quest_form'>" . ENDLINE;

//echo "<h1><span id='title' class='editText'>{$qInfo['name']}</span></h1>" . ENDLINE;
echo "<div class='instructions'><span id='instructions' class='editText md'>" . htmlspecialchars($qInfo['instructions']). "</span></div>" . ENDLINE;
echo "<input type='hidden' name='quest_id' id='quest_id' value='{$quest_id}' />" . ENDLINE;

// !    questionnaire table
echo "<table class='questionnaire toedit {$qInfo['questtype']}' id='quest_table'>" . ENDLINE;

// !    print radiorow option row
if ($qInfo['questtype'] == 'radiopage') { 
    if (array_key_exists('radiopage', $_GET)) {
        $radiorow_options = array(
            array('opt_order'=>1, 'opt_value'=>1, 'display'=>'much less than average'),
            array('opt_order'=>2, 'opt_value'=>2, 'display'=>'less than average'),
            array('opt_order'=>3, 'opt_value'=>3, 'display'=>'average'),
            array('opt_order'=>4, 'opt_value'=>4, 'display'=>'more than average'),
            array('opt_order'=>5, 'opt_value'=>5, 'display'=>'much more than average'),
        );
    } else {
        $query = new myQuery("SELECT opt_order, opt_value, display 
                              FROM radiorow_options 
                              WHERE quest_id='{$quest_id}' 
                              ORDER BY opt_order");
        $radiorow_options = $query->get_assoc();
    }
    
    $radio_width = round(50/max(count($radiorow_options),1), 1);
    $opt_row = "<tr class='radiorow_options'><th></th>";
    $val_row = "<tr class='radiorow_values'><td>Option values (not shown to participants) --></td></td>";
    foreach ($radiorow_options as $rr) {
        $opt_row .= "<th style='width:{$radio_width}%' class='input'><span id='radiorow_option_{$rr['opt_order']}' class='editText input'>{$rr['display']}</span></th>";
        $val = new input("radiorow_value_{$rr['opt_order']}", "radiorow_value_{$rr['opt_order']}", $rr['opt_value']);
        $val->set_width(40);
        $val_row .= "<td class='input'>" . $val->get_element() . "</td>";
    }
    
    $opt_row .= "<td class='typechanger'></td>" . ENDLINE;
    $val_row .= "<td class='typechanger'>
        <span id='add-radio-option' title='Add option to end'>+</span><br><br>
        <span id='delete-radio-option' title='Delete last option'>-</span>
    </td>" . ENDLINE;
    
    $opt_row .= "</tr>" . ENDLINE;
    $val_row .= "</tr>" . ENDLINE;
    
    echo '<thead>' . ENDLINE . $val_row . ENDLINE . $opt_row . ENDLINE . '</thead>' . ENDLINE;
}

echo '<tbody id="quest_body">' . ENDLINE;

$type_options = array(
    'select' => 'Pulldown Menu',
    'selectnum' => 'Numeric Pulldown Menu',
    //'radio' => 'Radio Buttons',
    'slider' => 'Slider',
    'radioanchor' => 'Radio Buttons With Anchors',
    'datemenu' => 'Date Menu',
    'countries' => 'Countries',
    'text' => 'Text',
    //'textarea' => 'Text (>255 characters)'
);

// !    questions
foreach ($questions as $n => $q) {
    
    // create new question object
    $q_object = ($q['type'] == 'text' || empty($q['type'])) ? 'input' : $q['type'];
    $input = new $q_object('q' . $q['id'], $q['id']);
    $opt = array();
    
    
    $n = $q['id']; // NEW EDIT TO CHANGE Q_N to Q_ID

    // set type handler for everything but rankings and radiorow/rev
        $type = new select('type_'.$n, 'type_'.$n, $q['type']);
        $type->set_null(false);
        $type->set_options($type_options);
        $type->set_eventHandlers(array(
            'onchange' => "changeType($n)",
        ));
        $typechooser = '<span class="view-typechooser"><span></span>' . $type->get_element() . '</span>';
        $inputParameters = "<span class='edit_question' title='Edit the input parameters' onclick='editQuestion(this);'></span>";
    
    // object-spcific settings
    switch ($q['type']) {
        case 'ranking': 
            $ranktype = new input('type_'.$n, 'type_'.$n, 'ranking');
            $typechooser = $ranktype->get_element();
            $typechooser = '';
            $inputParameters = '';
            break;
        case 'radiorow':
        case 'radiorev':
            $input->set_options($radiorow_options);
            $rowrev = new select('type_'.$n, 'type_'.$n, $q['type']);
            $rowrev->set_options(array('radiorow' => 'fwd', 'radiorev' => 'rev'));
            $rowrev->set_null(false);
            $typechooser = $rowrev->get_element();
            $inputParameters = '';
            break;
        case 'select':
        case 'radio':
            // get options
            $optData = new myQuery('SELECT opt_value, display FROM options WHERE q_id=' . $q['id'] . ' ORDER BY opt_order');
            $opt = $optData->get_key_val('opt_value', 'display');
            $input->set_options($opt);
            break;
        case 'radioanchor':
            $input->set_options($q['maxlength'],$q['low_anchor'],$q['high_anchor']);
            break;
        case 'slider':
            $input->set_options($q['startnum'],$q['endnum'],$q['step'],$q['low_anchor'],$q['high_anchor']);
            break;
        case 'selectnum':
            $input->set_options(null, $q['low_anchor'], $q['high_anchor']);
            break;
        case 'datemenu':    
            $input->set_years($q['low_anchor'], $q['high_anchor']);
            break;
        case 'input': break;
        case 'textarea': break;
        
    }

    

?>
<tr id='row_<?= $n ?>'><input type='hidden' id='id_<?= $n ?>' name='id_<?= $n ?>' value='<?= $q['id'] ?>' />
    <td class='question'>
        <div class='varinfo'>
            <span class='qid'>q<?= $q['id'] ?></span> 
            <span id='name_<?= $n ?>' class='editText'><?= $q['name'] ?></span>
        </div>
        <span class="move_anchor ui-icon ui-icon-arrowthick-2-n-s"></span>
        <span id='question_<?= $n ?>' class='editText'><?= $q['question'] ?></span>
    </td>
    <td class='input'>
        <?= $input->get_element() ?>
    </td>
    <td class='typechanger'>
            <?= $typechooser ?>
            <?= $inputParameters ?>
            <span class='delete_question' title='Delete the question' onclick='removeQuestion(this);'></span>
    </td>
</tr>

<?php } ?>

</tbody>
</table>

<div class='buttons'>
    <input type='button' onclick='addQuestion();' value='Add a Question' />
    <input type='button' onclick='addExcel();' value='Add from Spreadsheet' />
</div>
</form>
</div>

    <div id="fbpage">
        <p class='instructions'><span id='feedback_general' 
            class='editText md' 
            title='Feedback'><?= htmlspecialchars($qInfo['feedback_general']) ?></span></p>
    </div>
</div>

<div id="excel_input">
    Drag a header to reorder or double-click to remove it.
    
    <p>Question types for mixed questionnaires are: <kbd>select</kbd>, 
        <kbd>selectnum</kbd>, <kbd>radioanchor</kbd>, 
        <kbd>datemenu</kbd> and <kbd>text</kbd>. Question types for 
        radiopage questionnaires are: <kbd>radiorow</kbd> 
        (normally scored) and <kbd>radiorev</kbd> (reverse scored).</p>
    
    <p>Options for select and radio questions are in the format 
        <kbd>{integer value}:{display 1}; {integer value}:{display2}</kbd> 
        (e.g., <kbd>0:male; 1:female</kbd>).</p>
    
    <p>Download templates for <a href="mixed_template.xls">mixed</a>, 
        <a href="ranking_template.xls">ranking</a> or 
        <a href="radiopage_template.xls">radiopage</a> questionnaires.</p>
        
    
    <table id="excel_box" class="questionnaire">
        <thead><tr class="excel">
            <th>name</th>
            <th>question</th>
            <th>type</th>
            <?php if ($qInfo['questtype'] == 'mixed') {
            echo '
            <th>options</th>
            <th>maxlength</th>
            <th>low_anchor</th>
            <th>high_anchor</th>';
            } ?>
        </tr></thead>
        <tbody>
            <tr>
                <td colspan="2" style="text-align: center;">Double-click to paste data from a spreadsheet</td>
            </tr>
        </tbody>
    </table>
</div>


<div id="help" title="Questionnaire Builder Help">
    <h1>Click on the edit pencil icon next to a question to edit the options</h1>
    <h2>Drop-down menu and radio button </h2>
    <ul>
        <li>Options must have unique integer values. This goes before a colon (e.g., <kbd>1:First option</kbd>).</li>
        <li>Options also must have unique labels, which go after the colon. You cannot have a colon in the option label.</li>
        <li>Add an option at the end and press tab or return to keep adding options.</li>
        <li>Delete an option and press tab or return to clear it.</li>
        <li>Click on the edit pencil icon next to a question again to change the option editing list back to a drop-down menu.</li>
    </ul>
    <h2>Input boxes (single line of text)</h2>
    <ul>
        <li>Click on the edit pencil icon next to a question  to edit the maximum number of characters that can be entered</li>
        <li>maxlength can range from 1 to 255</li>
    </ul>
    <h2>Date Menus</h2>
    <ul>
        <li>Click on the edit pencil icon next to a question to edit the minimum and maximum dates allowed</li>
        <li>Min and max dates are entered as number of days, weeks, months or years from today, e.g.: <ul>
            <li><kbd>-10y to +0d</kbd> = 10 years before today to 0 days after today (i.e., today)</li>
            <li><kbd>-1m to +1w</kbd> = 1 month before today to 1 week after today</li>
        </ul></li>
</div>

<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->


<script>
    
    $( "#tabs" ).tabs();

    if ($('#quest_id').val() == '') { $('#quest_table tbody > tr').addClass('newQ'); }
    
    $('#excel_box thead tr').sortable({
        revert: true,
        connectWith: ".excel",
        cancel: ".nomove"
    });
    
    $('#excel_box thead tr th').dblclick( function() { $(this).hide(); } );
    
    // !    Make questionnaire table sortable
    $('#quest_table > tbody').sortable({
        handle: '.move_anchor',
        start: function() { $(this).find('td.typechanger').hide(); },
        stop: function() { 
            $(this).find('td.typechanger').show(); 
            stripe('#quest_table > tbody'); 
        }
    });
    
    $('.radioanchor').each( function() {
        var qid = $(this).attr('id').replace('q','');
        $(this).find('.anchor:first').wrapInner($('<span class="editText" id="low_anchor_' + qid + '" />'));
        $(this).find('.anchor:last').wrapInner($('<span class="editText" id="high_anchor_' + qid + '" />'));
    });
    
    $('table.slider').each( function() {
        var qid = $(this).find('div.slider').attr('id').replace('q','');
        $(this).find('.anchor:first').wrapInner($('<span class="editText" id="low_anchor_' + qid + '" />'));
        $(this).find('.anchor:last').wrapInner($('<span class="editText" id="high_anchor_' + qid + '" />'));
    });
    
    $('.view-typechooser span').click( function() {
        $(this).hide().siblings('select').show().focus();
    });
    
    $('.view-typechooser select').blur( function() {
        $(this).hide().siblings("span").show();
    });
    
    function editQuestion(r) {
        var $input = $(r).closest('tr').find('td.input');
        $input.toggleClass('beingedited');
        
        if ($input.find('input[type="text"].textinput').length > 0) { 
            // !        edit text (input[type=text].textinput) 
            var theSelect = $input.find('input[type="text"].textinput');
            if (!$input.hasClass('beingedited')) {
                var newMaxlength = parseInt(theSelect.val().replace('maxlength:', ''));
                if (newMaxlength > 255) newMaxlength = 255;
                if (newMaxlength < 1) newMaxlength = 1;
                theSelect   .attr('maxlength', newMaxlength)
                            .attr('placeholder', 'maxlength:' + newMaxlength)
                            .val('').blur();
            } else {
                theSelect   .val('maxlength:' + theSelect.attr('maxlength'))
                            .attr('maxlength', 13).focus();  // set so that you can't input more than 3 digits
            }
        } else if ($input.find('div.slider').length > 0) { 
            // !        edit slider
            var theSlider = $input.find('div.slider');
            if (!$input.hasClass('beingedited')) {
                theSlider.slider("option", {
                    min: parseFloat($input.find('.slidermin').val()),
                    max: parseFloat($input.find('.slidermax').val()),
                    step: parseFloat($input.find('.sliderstep').val())
                }).show();
                $input.find('.slider_settings').remove();
            } else {
                theSlider.hide();
                theSlider.after("<div class='slider_settings'><input class='slidermin' type='number' value='"+theSlider.slider("option","min")+"'> to " +
                                "<input class='slidermax' type='number' value='"+theSlider.slider("option","max")+"'> by " +
                                "<input class='sliderstep' type='number' value='"+theSlider.slider("option","step")+"'></div>");
            }
        } else if ($input.find('table.radioanchor').length > 0) {
            // !        edit radioanchor (table.radioanchor)
            console.log('editing radioanchor');
            theRadioAnchor = $input.find('table.radioanchor');
            if (!$input.hasClass('beingedited')) {
                // remove add and sub buttons
                theRadioAnchor.find('.addRadio').parent('td').remove();
                theRadioAnchor.find('.subRadio').parent('td').remove();
            } else {
                var addRadio = $('<span class="addRadio" />')
                                .html('+')
                                .click( function() {
                                    var lastRadio = theRadioAnchor.find('label:last').parent('td')
                                    var newRadio = lastRadio.clone(true);
                                    var newID = parseInt(lastRadio.find('input').attr('name').replace('q', ''))+1;
                                    var newVal = parseInt(lastRadio.find('input').val())+1;
                                    
                                    newRadio.find('input').val(newVal).attr('id', 'q' + newID + '_' + newVal).attr('name', 'q' + newID);
                                    newRadio.find('label').attr('for', 'q' + newID + '_' + newVal).html(newVal+1);
                                    
                                    newRadio.insertAfter(lastRadio);
                                });
                var subRadio = $('<span class="subRadio" />')
                                .html('-')
                                .click( function() {
                                    if (theRadioAnchor.find('label').length < 3) {
                                        // do nothing
                                    } else {
                                        theRadioAnchor.find('label:last').parent('td').remove();
                                    }
                                });
                
                theRadioAnchor.find('tr').append($('<td />').append(addRadio)).prepend($('<td />').append(subRadio));
            }
        } else if ($input.find('input.datepicker').length > 0) {
            // !        edit datepicker (input.datepicker) 
            var theDatepicker = $input.find('input.datepicker');
            if ($input.hasClass('beingedited')) {
                var minDate = theDatepicker.datepicker('option', 'minDate');
                var maxDate = theDatepicker.datepicker('option', 'maxDate');
                
                theDatepicker.parent().append($('<label />').html('Date Range: '));
                $('<input />') .attr({ 'type': 'text' })
                                    .val(minDate)
                                    .addClass('tmp')
                                    .css({'display':'inline', 'float':'none', 'width':'100px'})
                                    .appendTo(theDatepicker.parent());
                theDatepicker.parent().append($('<label />').html(' to '));
                $('<input />') .attr({ 'type': 'text' })
                                    .val(maxDate)
                                    .addClass('tmp')
                                    .css({'display':'inline', 'float':'none', 'width':'100px'})
                                    .appendTo(theDatepicker.parent());
                theDatepicker.datepicker('hide');
                theDatepicker.hide();
            
            } else {
                // rebuild the datepicker and show it - destroy all inputs
                var newMinDate = theDatepicker.parent().find('input.tmp:first').val();
                var newMaxDate = theDatepicker.parent().find('input.tmp:last').val();
                var minYear = newMinDate.match(/[+-]\d+y/);
                var maxYear = newMaxDate.match(/[+-]\d+y/);
                var yearRangeMin = (minYear !== null) ? minYear[0].replace('y','') : '+0';
                var yearRangeMax = (maxYear !== null) ? maxYear[0].replace('y','') : '+0';
                
                theDatepicker.parent().find('input.tmp, label').remove();
                theDatepicker.datepicker('option', 'minDate', newMinDate);
                theDatepicker.datepicker('option', 'maxDate', newMaxDate);
                theDatepicker.datepicker('option', 'yearRange', yearRangeMin + ':' + yearRangeMax);     
                theDatepicker.show();
            }
        } else if ($input.find('select:not(.selectnum):not(.countries)').length > 0) {
            // !        edit select
            var theSelect = $input.find('select:not(.selectnum):not(.countries)');
            if (!$input.hasClass('beingedited')) {
                // rebuild select and show it - destroy all inputs
                theSelect.html('');
                $('<option />').attr({'value':'NULL'}).html('').appendTo(theSelect);
                theSelect.parent().find('input').each( function(i) {
                    optVal = $(this).val().split(':');
                    if (optVal.length == 2) {
                        $('<option />').attr({'value': optVal[0]}).html(optVal[1]).appendTo(theSelect);
                    }
                    $(this).remove();
                });
                theSelect.show();
            } else {
                theSelect.find('option').each(function() {
                    if ($(this).val() != 'NULL') {
                        var theVal = $(this).val() + ':' + $(this).html();
                        $('<input />') .attr({ 'type': 'text' })
                                        .val(theVal)
                                        .appendTo(theSelect.parent())
                                        .change( function() {
                                            if ($(this).val() == '') $(this).remove();
                                        });
                    }
                });
                
                // add blank option at the end. doesn't remove on blanking
                $('<input />') .attr({ 'type': 'text', 'placeholder':'n:add an option' })
                                .appendTo(theSelect.parent())
                                .change(function() {
                                    $(this).clone(true)
                                            .val('')
                                            .attr('placeholder', 'n:add an option')
                                            .insertAfter($(this)).focus();
                                    $(this).unbind('change');
                                });
                theSelect.hide();
            }
        } else if ($input.find('select.selectnum').length > 0) {
            // !        edit selectnum 
            var theSelect = $input.find('select.selectnum');
            if ($input.hasClass('beingedited')) {
                
                var minVal = theSelect.find('option:eq(1)').val();
                var maxVal = theSelect.find('option:last').val();
                
                theSelect.parent().append($('<label />').html('Range: '));
                $('<input />') .attr({ 'type': 'text' })
                                    .val(minVal)
                                    .addClass('tmp')
                                    .css({'display':'inline', 'float':'none', 'width':'50px'})
                                    .appendTo(theSelect.parent());
                theSelect.parent().append($('<label />').html(' to '));
                $('<input />') .attr({ 'type': 'text' })
                                    .val(maxVal)
                                    .addClass('tmp')
                                    .css({'display':'inline', 'float':'none', 'width':'50px'})
                                    .appendTo(theSelect.parent());  
                theSelect.hide();
            } else {
                // rebuild the selectnum and show it - destroy all inputs
                var newMin = parseInt(theSelect.parent().find('input.tmp:first').val());
                var newMax = parseInt(theSelect.parent().find('input.tmp:last').val());
                theSelect.parent().find('input.tmp, label').remove();
                theSelect.html('');
                $('<option />').attr({'value':'NULL'}).html('').appendTo(theSelect);
                for (var i = newMin; i <= newMax; i++) {
                    $('<option />').attr({'value':i}).html(i).appendTo(theSelect);
                }
                theSelect.show();
            }
        } else if ($input.find('ul.radio').length > 0) {
            // !        edit radio 
            var theRadio = $input.find('ul.radio');
            if ($input.hasClass('beingedited')) {
                var theName = theRadio.attr('id').replace('_options', '');
                theRadio.find('li').each( function() {
                    var theVal = $(this).find('input[type="radio"]').val() + ':' + $(this).find('span.ui-button-text').html();
                    
                    $('<input />') .attr({ 'type': 'text' })
                                    .val(theVal)
                                    .appendTo(theRadio.parent())
                                    .change( function() {
                                        if ($(this).val() == '') $(this).remove();
                                    });
                });
                
                // add blank option at the end. doesn't remove on blanking
                $('<input />') .attr({ 'type': 'text', 'placeholder':'n:add an option' })
                                .appendTo(theRadio.parent())
                                .change(function() {
                                    if ($(this).val() != '') {
                                        var clone = $(this).clone(true)
                                            .val('')
                                            .attr('placeholder', 'n:add an option')
                                            .insertAfter($(this));
                                        $(this).unbind('change');
                                        clone.focus();
                                    }
                                });
                theRadio.hide();
            } else {
                // rebuild radio and show it - destroy all inputs
                theRadio.html('');
                theRadio.parent().find('input').each( function(i) {
                    optVal = $(this).val().split(':');
                    if (optVal.length == 2) {
                        var newRadio = $('<input />')  .attr({ 'value': optVal[0],
                                                'type': 'radio',
                                                'id': theName + '_' + optVal[0],
                                                'name': theName });
                                        
                        var newLabel = $('<label />')  .attr('for', theName + '_' + optVal[0])
                                        .html(optVal[1]);
                                        
                        $('<li />').append(newRadio).append(newLabel).appendTo(theRadio);
                                        
                    }
                    $(this).remove();
                });
                theRadio.buttonset().show();
            }
        }
    }

    $(function() {
        setOriginalValues('myInformation');
         
        
        $("#excel_box tbody").dblclick(function() {
            var header_cols = $("#excel_box thead tr th").length;
            $("#excel_box tbody").html("<tr><td colspan='" + header_cols + "'><textarea id='excel_paste' onkeyup='excelPaste();'></textarea></td></tr>");
            $("#excel_paste").focus();
        });
        
        // !    add radiorow option
        $('#add-radio-option').click( function() {
            // add last option cell
            $('#quest_table tr').each( function() {
                var lastTD = $(this).find('> *:last').prev();
                lastTD.clone(true).insertAfter(lastTD);
            });
            
            var n = $('tr.radiorow_values input').length;
            $('tr.radiorow_values input:last').val(function(i, v) { return parseInt(v) + 1; })
                                                .attr('name', 'radiorow_value_' + n)
                                                .attr('id', 'radiorow_value_' + n);
            
            $('tr.radiorow_options span:last').attr('id', 'radiorow_option_' + n)
                                                .unbind("click")
                                                .html(n);
                                                
            $('tr.radiorow_options input.instantedit:last').remove();
            
            editbox_init();
        });
        
        // !    delete radiorow option
        $('#delete-radio-option').click( function() {
            // count selects in radiorow_values tr
            var opts = $('tr.radiorow_values input');
            var optN = opts.length;
            
            // quit if < 3 options remain (you can't have < 2 options)
            if (optN > 2) {
                $('#quest_table tr').find('> *:last').prev().remove();
            } else {
                $('<div />').html('You must have at least 2 options').dialog();
            }
        });

    });
    
    
    // !    changeType(qid)
    function changeType(qid) {
        var typechooser = $('#type_' + qid);
        var theType = typechooser.val();
        var theInput = $('#q' + qid);
        var newInput = null;
            
        if (theType == 'select') { 
            // !        Change to select
            newInput = $('<select />')
                            .attr('id', theInput.attr('id'))
                            .attr('name', theInput.attr('id'));
            $('<option />').attr('value', 'NULL').appendTo(newInput);
        
            if (theInput.is('ul.radio')) {
                // convert select to radio buttons
                theInput.find('input[type=radio]').each( function(i) {
                    $('<option />').attr('value', $(this).attr('value'))
                                    .html($(this).next('label').text())
                                    .appendTo(newInput);

                });
            } else {
                // generic select
                $('<option />').attr('value', '1').html('option 1').appendTo(newInput);
                $('<option />').attr('value', '2').html('option 2').appendTo(newInput);
            }
            
            theInput.replaceWith(newInput);
        } else if (theType == 'countries') { 
            // !        Change to countries
            newInput = $('<select />')
                            .attr('id', theInput.attr('id'))
                            .attr('name', theInput.attr('id'))
                            .addClass('countries')
                            .append("<option value='NULL' selected='selected'></option><option value='AF'>Afghanistan</option><option value='AL'>Albania</option><option value='DZ'>Algeria</option><option value='AS'>American Samoa</option><option value='AD'>Andorra</option><option value='AO'>Angola</option><option value='AI'>Anguilla</option><option value='AQ'>Antarctica</option><option value='AG'>Antigua and Barbuda</option><option value='AR'>Argentina</option><option value='AM'>Armenia</option><option value='AW'>Aruba</option><option value='AU'>Australia</option><option value='AT'>Austria</option><option value='AZ'>Azerbaijan</option><option value='BS'>Bahamas</option><option value='BH'>Bahrain</option><option value='BD'>Bangladesh</option><option value='BB'>Barbados</option><option value='BY'>Belarus</option><option value='BE'>Belgium</option><option value='BZ'>Belize</option><option value='BJ'>Benin</option><option value='BM'>Bermuda</option><option value='BT'>Bhutan</option><option value='BO'>Bolivia</option><option value='BA'>Bosnia and Herzegowina</option><option value='BW'>Botswana</option><option value='BV'>Bouvet Island</option><option value='BR'>Brazil</option><option value='IO'>British Indian Ocean Territory</option><option value='BN'>Brunei Darussalam</option><option value='BG'>Bulgaria</option><option value='BF'>Burkina Faso</option><option value='BI'>Burundi</option><option value='KH'>Cambodia</option><option value='CM'>Cameroon</option><option value='CA'>Canada</option><option value='CV'>Cape Verde</option><option value='KY'>Cayman Islands</option><option value='CF'>Central African Republic</option><option value='TD'>Chad</option><option value='CL'>Chile</option><option value='CN'>China</option><option value='CX'>Christmas Island</option><option value='CC'>Cocos (Keeling) Islands</option><option value='CO'>Colombia</option><option value='KM'>Comoros</option><option value='CG'>Congo</option><option value='CD'>Congo, the Democratic Republic of the</option><option value='CK'>Cook Islands</option><option value='CR'>Costa Rica</option><option value='CI'>Cote d'Ivoire</option><option value='HR'>Croatia (Hrvatska)</option><option value='CU'>Cuba</option><option value='CY'>Cyprus</option><option value='CZ'>Czech Republic</option><option value='DK'>Denmark</option><option value='DJ'>Djibouti</option><option value='DM'>Dominica</option><option value='DO'>Dominican Republic</option><option value='TP'>East Timor</option><option value='EC'>Ecuador</option><option value='EG'>Egypt</option><option value='SV'>El Salvador</option><option value='GQ'>Equatorial Guinea</option><option value='ER'>Eritrea</option><option value='EE'>Estonia</option><option value='ET'>Ethiopia</option><option value='FK'>Falkland Islands (Malvinas)</option><option value='FO'>Faroe Islands</option><option value='FJ'>Fiji</option><option value='FI'>Finland</option><option value='FR'>France</option><option value='GF'>French Guiana</option><option value='PF'>French Polynesia</option><option value='TF'>French Southern Territories</option><option value='GA'>Gabon</option><option value='GM'>Gambia</option><option value='GE'>Georgia</option><option value='DE'>Germany</option><option value='GH'>Ghana</option><option value='GI'>Gibraltar</option><option value='GR'>Greece</option><option value='GL'>Greenland</option><option value='GD'>Grenada</option><option value='GP'>Guadeloupe</option><option value='GU'>Guam</option><option value='GT'>Guatemala</option><option value='GN'>Guinea</option><option value='GW'>Guinea-Bissau</option><option value='GY'>Guyana</option><option value='HT'>Haiti</option><option value='HM'>Heard and Mc Donald Islands</option><option value='HN'>Honduras</option><option value='HK'>Hong Kong</option><option value='HU'>Hungary</option><option value='IS'>Iceland</option><option value='IN'>India</option><option value='ID'>Indonesia</option><option value='IR'>Iran (Islamic Republic of)</option><option value='IQ'>Iraq</option><option value='IE'>Ireland</option><option value='IL'>Israel</option><option value='IT'>Italy</option><option value='JM'>Jamaica</option><option value='JP'>Japan</option><option value='JO'>Jordan</option><option value='KZ'>Kazakhstan</option><option value='KE'>Kenya</option><option value='KI'>Kiribati</option><option value='KP'>Korea, Democratic People's Republic of</option><option value='KR'>Korea, Republic of</option><option value='KW'>Kuwait</option><option value='KG'>Kyrgyzstan</option><option value='LA'>Lao People's Democratic Republic</option><option value='LV'>Latvia</option><option value='LB'>Lebanon</option><option value='LS'>Lesotho</option><option value='LR'>Liberia</option><option value='LY'>Libyan Arab Jamahiriya</option><option value='LI'>Liechtenstein</option><option value='LT'>Lithuania</option><option value='LU'>Luxembourg</option><option value='MO'>Macau</option><option value='MK'>Macedonia, The Former Yugoslav Republic of</option><option value='MG'>Madagascar</option><option value='MW'>Malawi</option><option value='MY'>Malaysia</option><option value='MV'>Maldives</option><option value='ML'>Mali</option><option value='MT'>Malta</option><option value='MH'>Marshall Islands</option><option value='MQ'>Martinique</option><option value='MR'>Mauritania</option><option value='MU'>Mauritius</option><option value='YT'>Mayotte</option><option value='MX'>Mexico</option><option value='FM'>Micronesia, Federated States of</option><option value='MD'>Moldova, Republic of</option><option value='MC'>Monaco</option><option value='MN'>Mongolia</option><option value='MS'>Montserrat</option><option value='MA'>Morocco</option><option value='MZ'>Mozambique</option><option value='MM'>Myanmar</option><option value='NA'>Namibia</option><option value='NR'>Nauru</option><option value='NP'>Nepal</option><option value='NL'>Netherlands</option><option value='AN'>Netherlands Antilles</option><option value='NC'>New Caledonia</option><option value='NZ'>New Zealand</option><option value='NI'>Nicaragua</option><option value='NE'>Niger</option><option value='NG'>Nigeria</option><option value='NU'>Niue</option><option value='NF'>Norfolk Island</option><option value='MP'>Northern Mariana Islands</option><option value='NO'>Norway</option><option value='OM'>Oman</option><option value='PK'>Pakistan</option><option value='PW'>Palau</option><option value='PA'>Panama</option><option value='PG'>Papua New Guinea</option><option value='PY'>Paraguay</option><option value='PE'>Peru</option><option value='PH'>Philippines</option><option value='PN'>Pitcairn</option><option value='PL'>Poland</option><option value='PT'>Portugal</option><option value='PR'>Puerto Rico</option><option value='QA'>Qatar</option><option value='RE'>Reunion</option><option value='RO'>Romania</option><option value='RU'>Russian Federation</option><option value='RW'>Rwanda</option><option value='KN'>Saint Kitts and Nevis</option><option value='LC'>Saint LUCIA</option><option value='VC'>Saint Vincent and the Grenadines</option><option value='WS'>Samoa</option><option value='SM'>San Marino</option><option value='ST'>Sao Tome and Principe</option><option value='SA'>Saudi Arabia</option><option value='SN'>Senegal</option><option value='SC'>Seychelles</option><option value='SL'>Sierra Leone</option><option value='SG'>Singapore</option><option value='SK'>Slovakia (Slovak Republic)</option><option value='SI'>Slovenia</option><option value='SB'>Solomon Islands</option><option value='SO'>Somalia</option><option value='ZA'>South Africa</option><option value='GS'>South Georgia and the South Sandwich Islands</option><option value='ES'>Spain</option><option value='LK'>Sri Lanka</option><option value='SH'>St. Helena</option><option value='PM'>St. Pierre and Miquelon</option><option value='SD'>Sudan</option><option value='SR'>Suriname</option><option value='SJ'>Svalbard and Jan Mayen Islands</option><option value='SZ'>Swaziland</option><option value='SE'>Sweden</option><option value='CH'>Switzerland</option><option value='SY'>Syrian Arab Republic</option><option value='TW'>Taiwan, Province of China</option><option value='TJ'>Tajikistan</option><option value='TZ'>Tanzania, United Republic of</option><option value='TH'>Thailand</option><option value='TG'>Togo</option><option value='TK'>Tokelau</option><option value='TO'>Tonga</option><option value='TT'>Trinidad and Tobago</option><option value='TN'>Tunisia</option><option value='TR'>Turkey</option><option value='TM'>Turkmenistan</option><option value='TC'>Turks and Caicos Islands</option><option value='TV'>Tuvalu</option><option value='UG'>Uganda</option><option value='UA'>Ukraine</option><option value='AE'>United Arab Emirates</option><option value='GB'>United Kingdom</option><option value='US'>United States</option><option value='UY'>Uruguay</option><option value='UZ'>Uzbekistan</option><option value='VU'>Vanuatu</option><option value='VA'>Vatican City State (Holy See)</option><option value='VE'>Venezuela</option><option value='VN'>Viet Nam</option><option value='VG'>Virgin Islands (British)</option><option value='VI'>Virgin Islands (U.S.)</option><option value='WF'>Wallis and Futuna Islands</option><option value='EH'>Western Sahara</option><option value='YE'>Yemen</option><option value='YU'>Yugoslavia</option><option value='ZM'>Zambia</option><option value='ZW'>Zimbabwe</option><option value='--'>none</option>");
            theInput.replaceWith(newInput);
        } else if (theType == 'selectnum') { 
            // !        Change to selectnum
        
            // convert everything to an empty selectnum
            newInput = $('<select class="selectnum" />')   
                            .attr('id', theInput.attr('id'))
                            .attr('name', theInput.attr('id'))
                            .append($('<option />').attr('value', 'NULL'))
                            .append($('<option />').attr('value', 0).html('0'))
                            .append($('<option />').attr('value', 1).html('1'));
            
            theInput.replaceWith(newInput);
        } else if (theType == 'radio') { 
            // !        Change to radio
            
            newInput = $('<ul class="radio" />').attr('id', theInput.attr('id'));
            
            if (theInput.is('select:not(.selectnum)')) {
                // convert select to radio buttons
                theInput.find('option[value!="NULL"]').each( function(i) {
                    var newLi = $('<li />');
                    $('<input />') .attr('type', 'radio')
                                    .attr('name', theInput.attr('id'))
                                    .attr('id', theInput.attr('id') + '_' + $(this).attr('value'))
                                    .val($(this).attr('value'))
                                    .appendTo(newLi);
                    $('<label />').html($(this).html())
                                    .attr('for', theInput.attr('id') + '_' + $(this).attr('value'))
                                    .appendTo(newLi);
                    newInput.append(newLi);
                }); 
            } else {
                // add generic radio options
                for (var i = 1; i<3; i++) {
                    var newLi = $('<li />');
                    $('<input />') .attr('type', 'radio')
                                    .attr('name', theInput.attr('id'))
                                    .attr('id', theInput.attr('id') + '_' + i)
                                    .val(i)
                                    .appendTo(newLi);
                    $('<label />').html('Option ' + i)
                                    .attr('for', theInput.attr('id') + '_' + i)
                                    .appendTo(newLi);
                    newInput.append(newLi);
                }
            }
            
            newInput.buttonset();
            theInput.replaceWith(newInput);
        } else if (theType == 'radioanchor') { 
            // !        Change to radioanchor
            
            // convert everything to a new radioanchor
            newInput = $('<table class="radioanchor" />').attr('id', 'q' + qid);
            var ra = newInput.append('<tbody />').find('tbody').append('<tr />').find('tr');
            $('<td />').addClass('anchor').html('<span class="editText" id="low_anchor_'+qid+'">low anchor</span>').appendTo(ra);
            for (var i = 0; i<5; i++) {
                var rb = $('<input />').attr('name', 'q' + qid)
                                        .attr('id', 'q' + qid + '_' + i)
                                        .attr('type', 'radio');
                var rl = $('<label />').html(i)
                                        .attr('for', 'q' + qid + '_' + i);
                var td = $('<td />').append(rb).append(rl);
                td.appendTo(ra);
            }
                            
            ra.append($('<td />')
                                .addClass('anchor editText')
                                .html('<span class="editText" id="high_anchor_'+qid+'">high anchor</span>')
                            );      
            
            theInput.replaceWith(newInput);
            editbox_init();
        } else if (theType == 'slider') { 
            // !        Change to slider
            
            // convert everything to a new slider
            newInput = $('<table class="slider" />').attr('id', 'q' + qid);
            var ra = newInput.append('<tbody />').find('tbody').append('<tr />').find('tr');
            $('<td />').addClass('anchor').html('<span class="editText" id="low_anchor_'+qid+'">low anchor</span>').appendTo(ra);
            
            $slider = $("<div class='slider' />").slider({
                min: 0,
                max: 100,
                step: 1
            });
            
            ra.append($('<td />').append($slider));
             
            ra.append($('<td />').addClass('anchor editText')
                                .html('<span class="editText" id="high_anchor_'+qid+'">high anchor</span>'));
            theInput.replaceWith(newInput);
            editbox_init();
        } else if (theType == 'datemenu') {
            // !        Change to datemenu
            newInput = $('<input />')
                        .attr('type', 'text')
                        .attr('id', 'q' + qid)
                        .addClass('datepicker');
                        
            newInput.datepicker({
                dateFormat: "yy-mm-dd",
                changeMonth: true,
                changeYear: true,
                minDate: '-100y',
                maxDate: '+0y',
                yearRange: "-100:+0"
            });
            
            theInput.replaceWith(newInput);
        } else if (theType == 'text') { 
            // !        Change to text input
            newInput = $('<input />')  .attr('type', 'text')
                            .attr('name', theInput.attr('id'))
                            .attr('id', theInput.attr('id'))
                            .attr('maxlength', 255)
                            .attr('placeholder', 'maxlength:255');
            theInput.replaceWith(newInput);
        } else if (theType == 'textarea') { 
            alert('change to textarea');
        }
        
        if (theType != 'radiorow' && theType != 'radiorev') {
            typechooser.hide().siblings('span').show();
        }
    }
    
    // !    excelPaste()
    // get pasted data from excel, display it in the table, stripe the table, and show buttons
    function excelPaste() {
        var data = $("#excel_paste").val();
        var headers = new Array();
        $('#excel_box thead tr th').each(function(i) {
            headers[i] = $(this).html();
        });
        
        $('#excel_box tbody').html("<tr><td>" +
            data.replace(/\n+$/i, '')
                .replace(/\n/g, '</td></tr><tr><td>')
                .replace(/\t/g, '</td><td>') +
                "</td></tr>"
            );
            
        stripe('#quest_table > tbody');
    }
    
    // !    addExcel
    // display box to copy Excel into
    function addExcel() {
        $("#excel_input").dialog({
            width: 650,
            title: 'Add from Spreadsheet',
            show: 'scale',
            hide: 'scale',
            buttons: {
                'Add Questions': function() { addQuestionsFromExcel(); },
                'Reset Headers': function() { $('#excel_box th').show(); },
                'Cancel': function() { $(this).dialog("close"); }
            }
        });
        $("#excel_paste").select();
    }
    
    // !    addQuestionsFromExcel()
    function addQuestionsFromExcel() {
        var headers = new Array();
        $('#excel_box thead tr th:visible').each(function(i) {
            headers[i] = $(this).html();
        });
        
        $('#excel_box tbody tr').each(function() {

            var new_q_id = addQuestion();
            var cells = new Array();
            
            $(this).children().each(function(i) {
                cells[i] = $(this).html();
            });
            
            // set name
            if ($.inArray('name', headers) > -1) {
                $("#name_" + new_q_id).html(cells[$.inArray('name', headers)]);
                $("#i_name_" + new_q_id).val(cells[$.inArray('name', headers)]);
            }
            
            //set question
            if ($.inArray('question', headers) > -1) {
                $("#question_" + new_q_id).html(cells[$.inArray('question', headers)]);
                $("#i_question_" + new_q_id).val(cells[$.inArray('question', headers)]);
            }
            
            //set type
            if ($.inArray('type', headers) > -1 && cells[$.inArray('type', headers)] != '') {
                $("#type_" + new_q_id).val(cells[$.inArray('type', headers)]);
                changeType(new_q_id);
            }
            
            var newType = $("#type_" + new_q_id).val();
            var theInput = $('#q' + new_q_id);
            
            // set maxlength
            if ($.inArray('maxlength', headers) > -1 && cells[$.inArray('maxlength', headers)] != '') {
                var ml = cells[$.inArray('maxlength', headers)];
                
                if (newType == 'text') {
                    theInput.attr('maxlength', ml);
                } else if (newType == 'radioanchor') {
                    // set correct number of radioanchors
                    theInput.find('td:not(.anchor)').remove();
                    for (var i = 0; i < ml; i++) {
                        var newRadio = $('<td />')
                                        .append($('<input name="q'+new_q_id+'" value="'+i+'" id="q'+new_q_id+'_'+i+'" type="radio" />'))
                                        .append($('<label for="q'+new_q_id+'_'+i+'" />').text(i+1));
                        theInput.find('td.anchor:last').before(newRadio);
                    }
                }
            }
            
            // set options
            if ($.inArray('options', headers) > -1 && cells[$.inArray('options', headers)] !='') {
                var opts = cells[$.inArray('options', headers)].split(';');
                
                if (newType == 'radio') {
                    theInput.find('li').remove(); // clear current contents
                    
                    $.each(opts, function(i) {
                        var opt = opts[i].split(':');
                        var optVal = opt[0].trim();
                        var optDisplay = opt[1].trim();
                    
                        var newLi = $('<li />');
                        $('<input />') .attr('type', 'radio')
                                        .attr('name', 'q' + new_q_id)
                                        .attr('id', 'q' + new_q_id + '_' + optVal)
                                        .val(optVal)
                                        .appendTo(newLi);
                        $('<label />').html(optDisplay)
                                        .attr('for', 'q' + new_q_id + '_' + optVal)
                                        .appendTo(newLi);
                        theInput.append(newLi);
                    });
                    theInput.buttonset();
                } else if (newType == 'select') {
                    theInput.find('option').remove(); // clear current contents
                
                    $('<option />').attr('value', 'NULL').appendTo(theInput);
                    
                    $.each(opts, function(i) {
                        var opt = opts[i].split(':');
                        var optVal = opt[0].trim();
                        var optDisplay = opt[1].trim();

                        $('<option />').attr('value', optVal).html(optDisplay).appendTo(theInput);
                    });
                }
            }
            
            // set low and high anchors
            if ($.inArray('low_anchor', headers) > -1 && 
                $.inArray('high_anchor', headers) > -1 && 
                cells[$.inArray('low_anchor', headers)] != '') {
                // set low and high 
                var newMin = cells[$.inArray('low_anchor', headers)];
                var newMax = cells[$.inArray('high_anchor', headers)];
                    
                if (newType == 'selectnum') {
                    theInput.html('');
                    $('<option />').attr({'value':'NULL'}).html('').appendTo(theInput);
                    for (var i = newMin; i <= newMax; i++) {
                        $('<option />').attr({'value':i}).html(i).appendTo(theInput);
                    }
                } else if (newType == 'radioanchor' || newType == 'slider') {
                    theInput.find('td.anchor:first input').val(newMin);
                    theInput.find('td.anchor:first span').text(newMin);
                    theInput.find('td.anchor:last input').val(newMax);
                    theInput.find('td.anchor:last span').text(newMax);
                } else if (newType == 'datemenu') {
                    var theDatepicker = theInput;
                    
                    var minYear = newMin.match(/[+-]\d+y/);
                    var maxYear = newMax.match(/[+-]\d+y/);
                    var yearRangeMin = (minYear !== null) ? minYear[0].replace('y','') : '+0';
                    var yearRangeMax = (maxYear !== null) ? maxYear[0].replace('y','') : '+0';
                    
                    theDatepicker.datepicker('option', 'minDate', newMin);
                    theDatepicker.datepicker('option', 'maxDate', newMax);
                    theDatepicker.datepicker('option', 'yearRange', yearRangeMin + ':' + yearRangeMax);
                }
            }
        });
        
        $("#excel_box tbody").html('').dblclick();
        $("#excel_input").dialog("close");
    }

    // !    saveQuestionnaire()
    function saveQuestionnaire() {
        // check that all the inputs are in participant view
        // [TODO] make this automatic or unnecessary 
        if ($('#quest_table tr td.input.beingedited').length) {
            $('<div />').html('You need to set the questionnaire to participant view before you save it. (Click the edit pencil next to all the yellow items.)').dialog();
            return false;
        }
        
        $('input.instantedit').each( function() {
            $(this).val(unescape($(this).val()));
        });
        
        var qInfo = [];
        // get specific question info for each question
        $('#quest_table > tbody > tr[id^="row_"]').each( function(i) {
            var theID = parseInt($(this).attr('id').replace('row_', ''));
            var theType = $('#type_' + theID).val();
            var theQ = {
                id: theID,
                type: theType,
                n: i+1,
                name: $('#i_name_' + theID).val(),
                question: $('#i_question_' + theID).val(),
                newQ: $(this).hasClass('newQ') ? true : false
            };
            
            if (theType == 'select') {
                var theOptions = {};
                $('#q' + theID + ' option[value!="NULL"]').each( function(i) {
                    theOptions[i] = {
                        value: $(this).val(),
                        display: $(this).text(),
                        opt_order: i+1
                    };
                });
                theQ['options'] = theOptions;
            } else if (theType == 'countries') {
                // no options needed until default options are added
            } else if (theType == 'radio') {
                var theOptions = {};
                $('input[type=radio][name="q' + theID + '"]').each( function(i) {
                    theOptions[i] = {
                        value: $(this).val(),
                        display: $(this).next('label').find('span.ui-button-text').html(),
                        opt_order: i+1
                    };
                });
                theQ['options'] = theOptions;
            } else if (theType == 'datemenu') {
                theQ['low_anchor'] = $('#q' + theID).datepicker('option', 'minDate');
                theQ['high_anchor'] = $('#q' + theID).datepicker('option', 'maxDate');
            } else if (theType == 'text') {
                theQ['maxlength'] = $('#q' + theID).attr('maxlength');
            } else if (theType == 'selectnum') {
                theQ['low_anchor'] = $('select#q' + theID).find('option:eq(1)').val();
                theQ['high_anchor'] = $('select#q' + theID).find('option:last').val();
            } else if (theType == 'radioanchor') {
                theQ['maxlength'] = $('#q' + theID + ' td label').length;
                theQ['low_anchor'] = $('#i_low_anchor_' + theID).val();
                theQ['high_anchor'] = $('#i_high_anchor_' + theID).val();
            } else if (theType == 'slider') {
                theQ['startnum'] = $(this).find('div.slider').slider('option', 'min');
                theQ['endnum'] = $(this).find('div.slider').slider('option', 'max');
                theQ['step'] = $(this).find('div.slider').slider('option', 'step');
                theQ['low_anchor'] = $('#i_low_anchor_' + theID).val();
                theQ['high_anchor'] = $('#i_high_anchor_' + theID).val();
            }
            
            qInfo[qInfo.length] = theQ;
        });
        
        var radioRowOptions = {};
        $('input[id^="i_radiorow_option_"]').each( function() {
            var n = $(this).attr('id').replace('i_radiorow_option_', '');
            radioRowOptions[n] = {
                display: $(this).val(),
                value: $('#radiorow_value_' + n).val(),
                opt_order: n
            };
        });
    
        var myInfo = {};
        $.each($('#myInformation_form').serializeArray(), function(index,value) {
            myInfo[value.name] = value.value;
        });
        myInfo['instructions'] = $('#i_instructions').val();
        myInfo['feedback_general'] = $('#i_feedback_general').val();
        
        var submitData = {
            'questions': qInfo,
            'quest': myInfo,
            'radiorow': radioRowOptions
        };
            
        $.ajax({
            url: './builder?save',
            type: 'POST',
            data: submitData,
            success: function(data) {
                r = data.split(";");
                $('#quest_id').val(parseInt(r[0]));
                window.location='/res/quest/info?id=' + parseInt(r[0]);
            }
        });
    }
    
    // !    addQuestion()
    function addQuestion() {
        var oldID = parseInt($('#quest_table > tbody > tr:last').attr('id').replace('row_', ''));
        //var newID = oldID + 1;
        newID = Math.floor(Math.random()*100000000);
        
        var oldRegex = new RegExp('_' + oldID, 'g');
        $('#quest_table > tbody').append($('#quest_table > tbody > tr:last').clone(true));
        var $lastTr = $('#quest_table > tbody > tr:last');
        $lastTr.attr('id', 'row_' + newID).addClass('newQ');
        
        // remove instantedit and unbind its functions
        $lastTr.find('.editText').unbind("click");
        $lastTr.find('.instantedit').remove();
        
        
        $lastTr.find('*[id]').attr('id', function(i, a) { return a.replace(oldID, newID); });
        $lastTr.find('*[name]').attr('name', function(i, a) { return a.replace(oldID, newID); })
        $lastTr.find('*[value]').attr('value', function(i, a) { return a.replace(oldID, newID); });
        $lastTr.find('*[onclick]').attr('onclick', function(i, a) { return a.replace(oldID, newID); });
        $lastTr.find('*[href]').attr('href', function(i, a) { return a.replace(oldID, newID); });
        $lastTr.find('*[onchange]').attr('onchange', function(i, a) { return a.replace(oldID, newID); });
        $lastTr.find('*[for]').attr('for', function(i, a) { return a.replace(oldID, newID); });
        $lastTr.find('.qid').html(function(i, a) { return a.replace(oldID, newID); });
        
        editbox_init();
        stripe('#quest_table > tbody');
        $('#quest_table ul.radio').each( function() { $(this).buttonset(); } );
        
        return newID;
    }
    
    function removeQuestion(r) {
        // make sure at least one question remains for cloning
        if ($('#quest_table > tbody tr').length>1) { 
            $(r).parents('tr').remove();
            stripe('#quest_table > tbody');
        } else {
            $('<div />').html('You must have at least one question.').dialog();
        }
    }
     
</script>

<!-- enable instant edits -->
<script src="/include/js/instantedit.js" type="text/javascript"></script>

<?php

$page->displayFooter();

?>