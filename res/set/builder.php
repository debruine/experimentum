<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';
auth(array('student', 'researcher', 'admin'));

$title = array(
    '/res/' => 'Researchers',
    '/res/set/' => 'Set',
    '/res/set/builder' => 'Builder'
);

$styles = array(
    '#myInformation_form'           => 'float:left; width: 30%; margin:0',
    '.setlists'                     => 'font-size:85%; float:right; width: 30%; margin:1em 0 1em 1em;',
    '.setlists h2'                  => 'padding:0',
    '.setlists ul, .setlists ol'    => 'height:400px; overflow:auto;',
    '.setlists ul'                  => 'box-shadow: 2px 2px 4px rgba(0,0,0,.5);',
    '.setlists li'                  => 'border:1px solid grey; padding:2px;',
    '.setlists li:hover'            => 'color:hsl(0, 100%, 40%); cursor:default;',
    '.setlists li+li'               => 'border-top:0;',
    '#new_set'                      => '',
    '#new_set li'                   => 'list-style-position: outside; margin-left:30px; cursor: url(/images/icons/ns-move), ns-resize;',
    '#labnotes'                     => 'vertical-align: text-top;',
    '.search'                       => 'width: 94%; margin: 0 .5em;',
    '#typeChanger'                  => 'float: right;'
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
        $q = new myQuery("SELECT id AS set_id, type as 'set_type', res_name, name as 'set_name', labnotes, sex, sexpref, lower_age, upper_age,
                            feedback_general, feedback_specific, feedback_query, forward, chart_id FROM sets WHERE id='{$id}'");
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
    
    // make sure user has permission to edit this set
    if ($_SESSION['status'] == 'student') {
        // student researchers cannot edit anything
        echo 'You may not edit or create sets'; exit; 
    } elseif ($_SESSION['status'] == 'researcher') { 
        // researchers can edit only their own experiments
        if (validID($clean['set_id'])) {
            $myaccess = new myQuery('SELECT user_id, status FROM access LEFT JOIN sets USING (id) WHERE access.type="sets" AND access.id='.$clean['set_id']." AND user_id=".$_SESSION['user_id']);
            $checkuser = $myaccess->get_assoc(0);
            $status = $checkuser['status'];
            if ($checkuser['user_id'] != $_SESSION['user_id']) { echo 'You do not have permission to edit this set'; exit; }
        }
    } elseif ($_SESSION['status'] == 'admin') { 
        if (validID($clean['set_id'])) {
            $myaccess = new myQuery('SELECT status FROM sets WHERE id='.$clean['set_id']);
            $status = $myaccess->get_one();
        }
    }
    
    $set_query = sprintf('REPLACE INTO sets (id, name, res_name, status, type, labnotes, 
            sex, sexpref, lower_age, upper_age,
            feedback_general, feedback_specific, feedback_query, forward, chart_id, create_date) 
            VALUES (%s, "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", %s, NOW())',
            check_null($clean['set_id'], 'id'),
            $clean['set_name'],
            $clean['res_name'],
            ifEmpty($status, 'test'),
            $clean['set_type'],
            $clean['labnotes'],
            $clean['sex'],
            $clean['sexpref'],
            $clean['lower_age'],
            $clean['upper_age'],
            $clean['feedback_general'],
            $clean['feedback_specific'],
            $clean['feedback_query'],
            $clean['forward'],
            check_null($clean['chart_id'], 'id')
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
 
$input_width = 250;

$set_table = array();
 
$set_table['set_id'] = new hiddenInput('set_id', 'set_id', $set_info['set_id']);

$set_table['res_name'] = new input('res_name', 'res_name', $set_info['res_name']);
$set_table['res_name']->set_width($input_width);
$set_table['res_name']->set_question('Name for Researchers');

$set_table['set_name'] = new input('set_name', 'set_name', $set_info['set_name']);
$set_table['set_name']->set_width($input_width);
$set_table['set_name']->set_question('Name for Participants');

$set_table['set_type'] = new select('set_type', 'set_type', $set_info['set_type']);
$set_table['set_type']->set_null(false);
$set_table['set_type']->set_question('Type');
$set_table['set_type']->set_options(array(
    'fixed' => 'Fixed Order',
    'random' => 'Random Order',
    'one_random' => 'One of (random)',
    'one_equal' => 'One of (equal)'
));

// set up limits: sex, sexpref, lower_age, upper_age
$sex = new select('sex', 'sex', $set_info['sex']);
$sex->set_options(array(
    'both' => 'Both sexes',
    'male' => 'Men only',
    'female' => 'Women only'
));
$sex->set_null(false);
$sexpref = new select('sexpref', 'sexpref', $set_info['sexpref']);
$sexpref->set_options(array(
    'NULL' => 'any',
    'men' => 'men',
    'women' => 'women',
    'either' => 'bisexuals'
));
$sexpref->set_null(false);
$lower_age = new selectnum('lower_age', 'lower_age', $set_info['lower_age']);
$lower_age->set_options(array('NULL'=>'any'), 0, 100);
$lower_age->set_null(false);
$upper_age = new selectnum('upper_age', 'upper_age', $set_info['upper_age']);
$upper_age->set_options(array('NULL'=>'any'), 0, 100);
$upper_age->set_null(false);
$ci = $sex->get_element() . 
    ' aged ' . $lower_age->get_element() . 
    ' to ' . $upper_age->get_element() . 
    ' who prefer ' . $sexpref->get_element();
$set_table['limits'] = new formElement('limits','limits');
$set_table['limits']->set_question('Limited to');
$set_table['limits']->set_custom_input($ci);

$set_table['labnotes'] = new textarea('labnotes', 'labnotes', $set_info['labnotes']);
$set_table['labnotes']->set_question('Labnotes');
$set_table['labnotes']->set_dimensions($input_width, 18, true, 18, 300);

$set_table['feedback_general'] = new textarea('feedback_general', 'feedback_general', $set_info['feedback_general']);
$set_table['feedback_general']->set_question('General Feedback');
$set_table['feedback_general']->set_dimensions($input_width, 50, true, 50, 0, 0);

$set_table['feedback_specific'] = new textarea('feedback_specific', 'feedback_specific', $set_info['feedback_specific']);
$set_table['feedback_specific']->set_question('Specific Feedback');
$set_table['feedback_specific']->set_dimensions($input_width, 50, true, 50, 0, 0);

$set_table['feedback_query'] = new textarea('feedback_query', 'feedback_query', $set_info['feedback_query']);
$set_table['feedback_query']->set_question('Feedback Query');
$set_table['feedback_query']->set_dimensions($input_width, 50, true, 50, 0, 0);

$set_table['forward'] = new textarea('forward', 'forward', $set_info['forward']);
$set_table['forward']->set_question('Forward URL');
$set_table['forward']->set_dimensions($input_width, 50, true, 50, 0, 0);

// set up set table
$setTable = new formTable();
$setTable->set_table_id('myInformation');
$setTable->set_title('Set Information');
$setTable->set_action('');
$setTable->set_questionList($set_table);

$q = new myQuery('SELECT id, res_name, status FROM sets ORDER BY id');
$sets = $q->get_assoc();
foreach ($sets as $s) {
    $set_list[] = "<li title='set_{$s['id']}'>{$s['id']}: {$s['res_name']}</li>" . ENDLINE;
}

$q->set_query('SELECT id, res_name, status FROM exp ORDER BY id');
$exps = $q->get_assoc();
$exp_list = array();
foreach ($exps as $s) {
    $exp_list[] = "<li title='exp_{$s['id']}'>{$s['id']}: {$s['res_name']}</li>" . ENDLINE;
}

$q->set_query('SELECT id, res_name, status FROM quest ORDER BY id');
$quests = $q->get_assoc();
$quest_list = array();
foreach ($quests as $s) {
    $quest_list[] = "<li title='quest_{$s['id']}'>{$s['id']}: {$s['res_name']}</li>" . ENDLINE;
}


/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_logo(false);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<div class='toolbar'>
    <button id='save-set'>Save</button>
    <button id='info-set'>Info</button>
    
    <span id="typeChanger">View Items: 
        <input type="radio" id="viewExp" name="typeChanger" checked="checked"><label for="viewExp">Exp</label> 
        <input type="radio" id="viewQuest" name="typeChanger"><label for="viewQuest">Quest</label> 
        <input type="radio" id="viewSets" name="typeChanger"><label for="viewSets">Sets</label> 
    </span>
</div>

<?= $setTable->print_form() ?>

<div class="setlists" id="expView">
    <h2>Experiments</h2>
    <input type='text' class='search' onkeyup='search(this.value, "exp");' />
    <ul id="exp">
        <?= implode('', $exp_list) ?>
    </ul>
</div>

<div class="setlists" id="questView">
    <h2>Questionnaires</h2>
    <input type='text' class='search' onkeyup='search(this.value, "quest");' />
    <ul id="quest">
        <?= implode('', $quest_list) ?>
    </ul>
</div>

<div class="setlists" id="setView"> 
    <h2>Sets</h2>
    <input type='text' class='search' onkeyup='search(this.value, "set");' />   
    <ul id="set">
        <?= implode('', $set_list) ?>
    </ul>
</div>

<div class="setlists">
    <h2>Set List</h2>
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

$j(function() {
    stripeList('#new_set');
    stripeList('#exp');
    stripeList('#quest');
    stripeList('#set');

    $j('#questView').hide();
    $j('#setView').hide();
    
    $j('#typeChanger').buttonset();

    $j('#viewExp').click( function() {
        $j('#expView').show();
        $j('#questView').hide();
        $j('#setView').hide();
    });
    
    $j('#viewQuest').click( function() {
        $j('#expView').hide();
        $j('#questView').show();
        $j('#setView').hide();
    });
    
    $j('#viewSets').click( function() {
        $j('#expView').hide();
        $j('#questView').hide();
        $j('#setView').show();
    });
    
    
    
    $j('.setlists ul li').click(function() { add(this); });
    
    $j('#info-set').button().click( function() { 
        window.location = '/res/set/info?id=' + $j('#set_id').val();
    });
    
    $j('#save-set').button().click( function() { 
        // serialize the new set order
        var setitems = '';
        
        $j('#new_set li').each( function(e) {
            setitems = setitems + ';' + $j(this).attr('title');
        });
        
        var serial =    $j('#myInformation_form').serialize() + '&set_items=' + setitems.substring(1);
    
        $j.ajax({
            url: './builder?save',
            type: 'POST',
            data: serial,
            success: function(data) {
                if (data == "Set Saved") {
                    growl("Set Saved", 500);
                } else {
                    $j('<div />').html(data).dialog();
                }
            }
        });
    });
    
    $j('#new_set').sortable({
        change: function(event, ui) { stripeList('#new_set'); }
    });
    
/*
    $j('#delete-set').button().click( function() {
        $j( "<div/>").html("Do you really want to delete this set?").dialog({
            title: "Delete Set",
            position: ['center', 100],
            modal: true,
            buttons: {
                Cancel: function() {
                    $j( this ).dialog( "close" );
                },
                "Delete": function() {
                    $j( this ).dialog( "close" );
                    $j.ajax({
                        url: '?delete',
                        type: 'POST',
                        data: $j('#set_id').serialize(),
                        success: function(data) {
                            if (data == 'deleted') {
                                window.location = '/res/set/';
                            } else {
                                $j('<div />').html(data).dialog();
                            }
                        }
                    });
                },
            }
        }); 
    });
*/
    
    sizeToViewport();
    
    window.onresize = sizeToViewport;
    
    $j('<div />').hide().insertAfter($j('#chart_id')).attr('id', "graph_container").css({
            'width': "100%",
            'height': "200px",
            'margin': '0',
            'background-color': 'white'
        });
        
        $j('#chart_id').change( function() {
            if ($j(this).val() == 'NULL' || $j(this).val() == '0') {
                $j('#graph_container').hide();
                return;
            }
            $j('#graph_container').html('').show().css('background', 'white url("/images/loaders/loading.gif") center center no-repeat');

            $j.ajax({
                type: 'GET',
                url: '/include/scripts/chart?id=' + $j(this).val(),
                success: function(data) {
                    //alert(JSON.stringify(data));
                    $j('#graph_container').css('background', 'white');
                    chart = new Highcharts.Chart(data);
                },
                dataType: 'json'
            });
        });
        
        if ($j('#chart_id').val() > 0) $j('#chart_id').trigger('change');
});


function sizeToViewport() {
    var ul_height = $j(window).height() - $j('ul#exp').offset().top - $j('#footer').height()-30;
    var ol_height = $j(window).height() - $j('ol#new_set').offset().top - $j('#footer').height()-30;
    $j('.setlists ul').height(ul_height);
    $j('.setlists ol').height(ol_height);
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
    
    $j('#new_set').append(newItem);
    $j('#new_set').sortable({
        change: function(event, ui) { stripeList('#new_set'); }
    });
    
    stripeList('#new_set');
}

function stripeList(list) {
    $j(list + ' > li:visible:odd').addClass("odd").removeClass("even");
    $j(list + ' > li:visible:even').addClass("even").removeClass("odd");
}

function search(find, list) {
    $j('#' + list + ' li').each( function(li) {
        $j(this).hide();
        
        if ($j(this).html().toLowerCase().indexOf(find.toLowerCase()) != -1) {
            $j(this).show();
        }
        
        
    });
    stripeList('#' + list);
}

</script>

<?php

$page->displayFooter();

?>