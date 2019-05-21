<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';
auth($RES_STATUS);

if (validID($_GET['id']) && !permit('sets', $_GET['id'])) header('Location: /res/');

$title = array(
    '/res/' => 'Researchers',
    '/res/set/' => 'Sets',
    '/res/set/builder' => 'Builder'
);

$styles = array(
    '#myInformation_form'           => 'width: 100%;',
    '#myInformation_form td.question'  => 'max-width: 20em;',
    '.setlists'                     => 'font-size:85%; float:left; width: 45%; margin:1em 0 1em 1em;',
    '.setlists h2, .setlists h3'    => 'padding:0',
    '.setlists ul, .setlists ol'    => 'height:400px; overflow:auto;',
    '.setlists ul'                  => 'box-shadow: 2px 2px 4px rgba(0,0,0,.5);',
    '.setlists li'                  => 'border:1px solid grey; padding:2px;',
    '.setlists li .status'          => 'display: inline-block; width:1.2em; height:1.2em; 
                                        border-radius: 0 .5em; float: right; 
                                        text-align: center; line-height: 1.2em; font-size:100%',
    '.setlists li.active .status'   => 'background-color: hsla(120,100%,50%, 50%);',
    '.setlists li.archive .status'  => 'background-color: hsla(120,0%,50%, 50%);',
    '.setlists li.test .status'     => 'background-color: hsla(0,100%,50%, 50%);',
    '.setlists li:hover'            => 'color:hsl(0, 100%, 40%); cursor:default;',
    '.setlists li+li'               => 'border-top:0;',
    '#new_set'                      => '',
    '#new_set li'                   => 'list-style-position: outside; margin-left:30px; cursor: url(/images/icons/ns-move), ns-resize;',
    '#labnotes'                     => 'vertical-align: text-top;',
    '.search'                       => 'width: 94%; margin: 0 .5em;'
);

/****************************************************
 * Show existing set info
 ***************************************************/

$set_info = array(
    'set_id' => 'NULL',
    'set_type' => 'fixed',
    'labnotes' => ''
);

$item_list = array();

if (array_key_exists('id', $_GET)) {
    // get a set's information
    
    $id = my_clean($_GET['id']);
    if (is_numeric($id) & $id>0) {
        $q = new myQuery("SELECT id AS set_id, type as 'set_type', 
                            res_name, name as 'set_name', labnotes, 
                            sex, lower_age, upper_age,
                            feedback_general, feedback_specific, 
                            feedback_query, forward 
                            FROM sets WHERE id='{$id}'");
        $set_info = $q->get_assoc(0);
        
        $q->set_query("SELECT item_type as type, item_id as id, 
            IF(item_type='exp', exp.res_name, 
                IF(item_type='quest', quest.res_name, 
                        IF(item_type='set', sets.res_name,'No such item'))) as name 
            FROM set_items 
            LEFT JOIN exp ON item_type='exp' AND exp.id=item_id
            LEFT JOIN quest ON item_type='quest' AND quest.id=item_id
            LEFT JOIN sets ON item_type='set' AND sets.id=item_id
            WHERE set_id='$id' ORDER BY item_n");
        foreach ($q->get_assoc() as $item) {
            $item_list[] = "<li ondblclick='deleteItem(this)' title='{$item['type']}_{$item['id']}'>{$item['type']}_{$item['id']}: {$item['name']}</li>";
        }
    }
    
}

/***************************************************/
/* !AJAX Responses */
/***************************************************/
 
if (array_key_exists('save', $_GET)) {
    // save a set
    $clean = my_clean($_POST);

    
    $set_query = sprintf('REPLACE INTO sets (
            id, name, res_name, status, 
            type, labnotes, sex, lower_age, 
            upper_age, feedback_general, feedback_specific, feedback_query, 
            forward, create_date) 
            VALUES (%s, "%s", "%s", "%s", 
                    "%s", "%s", "%s", "%s", 
                    "%s", "%s", "%s", "%s", 
                    "%s", NOW())',
            check_null($clean['set_id'], 'id'),
            $clean['set_name'],
            $clean['res_name'],
            ifEmpty($status, 'test'),
            $clean['set_type'],
            $clean['labnotes'],
            $clean['sex'],
            $clean['lower_age'],
            $clean['upper_age'],
            $clean['feedback_general'],
            $clean['feedback_specific'],
            $clean['feedback_query'],
            $clean['forward']
    );
    
    $set_query = str_replace('""', 'NULL', $set_query);
    $set_query = str_replace('"NULL"', 'NULL', $set_query);
    
    $q = new myQuery($set_query);
    
    // get new set ID if a new set
    if ('NULL' == $clean['set_id']) $clean['set_id'] = $q->get_insert_id();
    
    // delete old items from set list
    $q = new myQuery('DELETE FROM set_items WHERE set_id=' . $clean['set_id']);
    
    // add set items to set list
    $set_items = explode(';', $clean['set_items']);
    $item_query = array();
    foreach ($set_items as $n => $item) {
        $item_n = $n+1;
        $i = explode('_', $item);
        $item_query[] = "('{$clean['set_id']}', '{$i[0]}', '{$i[1]}', '{$item_n}')";
    }
    $q = new myQuery('INSERT INTO set_items (set_id, item_type, item_id, item_n) VALUES ' . implode(',', $item_query));
    
    // add to access list
    $q = new myQuery("REPLACE INTO access (type, id, user_id) VALUES ('sets', {$clean['set_id']}, {$_SESSION['user_id']})");
    
    echo 'Set Saved'; exit;
} else if (array_key_exists('delete', $_GET) && validID($_POST['set_id'])) {
    // delete the set
    $q = new myQuery('DELETE FROM sets WHERE id=' . $_POST['set_id']);
    $q = new myQuery('DELETE FROM set_items WHERE set_id=' . $_POST['set_id']);
    $q = new myQuery('DELETE FROM access WHERE type="sets" AND id=' . $_POST['set_id']);
    $q = new myQuery('DELETE FROM dashboard WHERE type="sets" AND id=' . $_POST['set_id']);
    
    echo 'deleted';
    exit;
}

/****************************************************
 * Set Table
 ***************************************************/
 
$input_width = 550;

$table_setup = array();
 
$table_setup['set_id'] = new hiddenInput('set_id', 'set_id', $set_info['set_id']);

$table_setup['res_name'] = new input('res_name', 'res_name', $set_info['res_name']);
$table_setup['res_name']->set_width($input_width);
$table_setup['res_name']->set_question('Name for Researchers');

$table_setup['set_name'] = new input('set_name', 'set_name', $set_info['set_name']);
$table_setup['set_name']->set_width($input_width);
$table_setup['set_name']->set_question('Name for Participants');

$table_setup['set_type'] = new select('set_type', 'set_type', $set_info['set_type']);
$table_setup['set_type']->set_null(false);
$table_setup['set_type']->set_question('Type');
$table_setup['set_type']->set_options(array(
    'fixed' => 'Fixed Order',
    'random' => 'Random Order',
    'one_random' => 'One of (random)'
    #'one_equal' => 'One of (equal)'
));

$table_setup['sex'] = new select('sex', 'sex', $project_info['sex']);
$table_setup['sex']->set_options(array(
    'both' => 'All genders',
    'male' => 'Men only',
    'female' => 'Women only'
));
$table_setup['sex']->set_null(false);
$table_setup['sex']->set_question("Show to");

// set up age limits
$lower_age = new selectnum('lower_age', 'lower_age', $project_info['lower_age']);
$lower_age->set_options(array('NULL'=>'any'), 0, 100);
$lower_age->set_null(false);
$upper_age = new selectnum('upper_age', 'upper_age', $project_info['upper_age']);
$upper_age->set_options(array('NULL'=>'any'), 0, 100);
$upper_age->set_null(false);
$ci = $lower_age->get_element() . ' to ' . $upper_age->get_element();
$table_setup['limits'] = new formElement('limits','limits');
$table_setup['limits']->set_question('Age limits');
$table_setup['limits']->set_custom_input($ci);

$table_setup['labnotes'] = new textarea('labnotes', 'labnotes', $set_info['labnotes']);
$table_setup['labnotes']->set_question('Labnotes');
$table_setup['labnotes']->set_dimensions($input_width, 50, true, 50, 300);

$table_setup['feedback_general'] = new textarea('feedback_general', 'feedback_general', $set_info['feedback_general']);
$table_setup['feedback_general']->set_question('General Feedback <div class="note">If blank, the feedback from the last item will be displayed to participants.</div>');
$table_setup['feedback_general']->set_dimensions($input_width, 100, true, 100, 0, 0);

/*
$table_setup['feedback_specific'] = new textarea('feedback_specific', 'feedback_specific', $set_info['feedback_specific']);
$table_setup['feedback_specific']->set_question('Specific Feedback');
$table_setup['feedback_specific']->set_dimensions($input_width, 50, true, 50, 0, 0);

$table_setup['feedback_query'] = new textarea('feedback_query', 'feedback_query', $set_info['feedback_query']);
$table_setup['feedback_query']->set_question('Feedback Query');
$table_setup['feedback_query']->set_dimensions($input_width, 50, true, 50, 0, 0);

$table_setup['forward'] = new textarea('forward', 'forward', $set_info['forward']);
$table_setup['forward']->set_question('Forward URL');
$table_setup['forward']->set_dimensions($input_width, 50, true, 50, 0, 0);
*/

// set up table
$form_table = new formTable();
$form_table->set_table_id('myInformation');
$form_table->set_title('Set Information');
$form_table->set_action('');
$form_table->set_questionList($table_setup);

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['status'] == 'admin') ? "TRUE" : "FALSE";

$q = new myQuery(
     "SELECT sets.id, sets.res_name, sets.status
        FROM access 
        LEFT JOIN sets USING (id)
       WHERE access.type='sets' 
         AND (access.user_id=$user_id OR {$is_admin} OR
           access.user_id IN (
                SELECT supervisee_id 
                  FROM supervise 
                 WHERE supervisor_id=$user_id
           )
         )"
);

$sets = $q->get_assoc();
foreach ($sets as $s) {
    $abr = ucwords(substr($s['status'], 0,1));
    $set_list[] = "<li title='set_{$s['id']}'>{$s['id']}: {$s['res_name']}
                    <span class='status'>{$abr}</span></li>" . ENDLINE;
}

$q = new myQuery(
     "SELECT exp.id, exp.res_name, exp.status
        FROM access 
        LEFT JOIN exp USING (id)
       WHERE access.type='exp' 
         AND (access.user_id=$user_id OR {$is_admin} OR
           access.user_id IN (
                SELECT supervisee_id 
                  FROM supervise 
                 WHERE supervisor_id=$user_id
           )
         )"
);
$exps = $q->get_assoc();
$exp_list = array();
foreach ($exps as $s) {
    $abr = ucwords(substr($s['status'], 0,1));
    $exp_list[] = "<li title='exp_{$s['id']}'>{$s['id']}: {$s['res_name']}
                    <span class='status'>{$abr}</span></li>" . ENDLINE;
}

$q = new myQuery(
     "SELECT quest.id, quest.res_name, quest.status
        FROM access 
        LEFT JOIN quest USING (id)
       WHERE access.type='quest' 
         AND (access.user_id=$user_id OR {$is_admin} OR
           access.user_id IN (
                SELECT supervisee_id 
                  FROM supervise 
                 WHERE supervisor_id=$user_id
           )
         )"
);
$quests = $q->get_assoc();
$quest_list = array();
foreach ($quests as $s) {
    $abr = ucwords(substr($s['status'], 0,1));
    $quest_list[] = "<li title='quest_{$s['id']}'>{$s['id']}: {$s['res_name']}
                        <span class='status'>{$abr}</span></li>" . ENDLINE;
}


/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<div class='toolbar'>
    <button id='save-set'>Save</button>
    <button id='info-set'>Info</button>
</div>

<?= $form_table->print_form() ?>

<div class='toolbar'>
    <span id="typeChanger">View Items: 
        <input type="radio" id="viewExp" name="typeChanger" checked="checked"><label for="viewExp">Exp</label><input 
        type="radio" id="viewQuest" name="typeChanger"><label for="viewQuest">Quest</label><input 
        type="radio" id="viewSets" name="typeChanger"><label for="viewSets">Sets</label> 
    </span>
</div>

<div class="setlists" id="expView">
    <h2>Available Experiments</h2>
    <h3 class="note">Click to add to Set List</h3>
    <input type='text' class='search' onkeyup='search(this.value, "exp");' placeholder="search" />
    <ul id="exp">
        <?= implode('', $exp_list) ?>
    </ul>
</div>

<div class="setlists" id="questView">
    <h2>Available Questionnaires</h2>
    <h3 class="note">Click to add to Set List</h3>
    <input type='text' class='search' onkeyup='search(this.value, "quest");' placeholder="search" />
    <ul id="quest">
        <?= implode('', $quest_list) ?>
    </ul>
</div>

<div class="setlists" id="setView"> 
    <h2>Available Sets</h2>
    <h3 class="note">Click to add to Set List</h3>
    <input type='text' class='search' onkeyup='search(this.value, "set");' placeholder="search" />   
    <ul id="set">
        <?= implode('', $set_list) ?>
    </ul>
</div>

<div class="setlists">
    <h2>Set List</h2>
    <h3 class="note">Double-click to remove</h3>
    <ol id="new_set">
        <?= implode('', $item_list) ?>
    </ol>
</div>

<div id="help" title="Set Builder Help">
    <ul>
        <li>Type into the search box to narrow down your list. It searches the ID number and name of items in the list, so you can type in the ID to find an item quickly.</li>
        <li>Click on items to add them to your set list.</li>
        <li>Double-click on items in your set list to delete them.</li>
        <li>Drag items in your set list to reorder them.</li>
        <li>Set the "type" of the set to change how the items are presented. <ul>
            <li>&ldquo;Fixed Order&rdquo; presents each item in the order you set.</li>
            <li>&ldquo;Random Order&rdquo; presents each item in a randomised order.</li>
            <li>&ldquo;One of (random)&rdquo; presents only a single random item from your list.</li>
            <li>&ldquo;One of (equal)&rdquo; presents only a single item from your list and tries to ensure that an equal number of men and women participate in each item. Do not use this option if some of your items already have different numbers of participants.</li>
        </ul></li>
    </ul>
</div>

<!--**************************************************-->
<!-- !Javascripts for this page -->
<!--**************************************************-->

<script>

$(function() {
    stripeList('#new_set');
    stripeList('#exp');
    stripeList('#quest');
    stripeList('#set');

    $('#questView').hide();
    $('#setView').hide();
    
    $('#typeChanger').buttonset();

    $('#viewExp').click( function() {
        $('#expView').show();
        $('#questView').hide();
        $('#setView').hide();
    });
    
    $('#viewQuest').click( function() {
        $('#expView').hide();
        $('#questView').show();
        $('#setView').hide();
    });
    
    $('#viewSets').click( function() {
        $('#expView').hide();
        $('#questView').hide();
        $('#setView').show();
    });
    
    
    
    $('.setlists ul li').click(function() { add(this); });
    
    $('#info-set').button().click( function() { 
        window.location = '/res/set/info?id=' + $('#set_id').val();
    });
    
    $('#save-set').button().click( function() { 
        // serialize the new set order
        var setitems = '';
        
        $('#new_set li').each( function(e) {
            setitems = setitems + ';' + $(this).attr('title');
        });
        
        var serial =    $('#myInformation_form').serialize() + '&set_items=' + setitems.substring(1);
    
        $.ajax({
            url: './builder?save',
            type: 'POST',
            data: serial,
            success: function(data) {
                if (data == "Set Saved") {
                    growl("Set Saved", 500);
                } else {
                    $('<div />').html(data).dialog();
                }
            }
        });
    });
    
    $('#new_set').sortable({
        change: function(event, ui) { stripeList('#new_set'); }
    });
    
    sizeToViewport();
    
    window.onresize = sizeToViewport;
});


function sizeToViewport() {
    var ul_height = $(window).height() - $('ul#exp').offset().top - $('#footer').height()-30;
    var ol_height = $(window).height() - $('ol#new_set').offset().top - $('#footer').height()-30;
    $('.setlists ul').height(ul_height);
    $('.setlists ol').height(ol_height);
}

function deleteItem(item) {
    item.parentNode.removeChild(item); 
    stripeList('#new_set');
}

function add(item) {
    var name = item.innerHTML;
    var id = item.title;
    var type = id.split("_")[0];
    
    var newItem = document.createElement('li');
    newItem.innerHTML = type + '_' + name;
    newItem.title = id;
    newItem.ondblclick = Function('deleteItem(this)');
    
    $('#new_set').append(newItem);
    $('#new_set').sortable({
        change: function(event, ui) { stripeList('#new_set'); }
    });
    
    stripeList('#new_set');
}

function stripeList(list) {
    $(list + ' > li:visible:odd').addClass("odd").removeClass("even");
    $(list + ' > li:visible:even').addClass("even").removeClass("odd");
}

function search(find, list) {
    $('#' + list + ' li').each( function(li) {
        $(this).hide();
        
        if ($(this).html().toLowerCase().indexOf(find.toLowerCase()) != -1) {
            $(this).show();
        }
        
        
    });
    stripeList('#' + list);
}

</script>

<?php

$page->displayFooter();

?>