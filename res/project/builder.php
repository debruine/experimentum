<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';
auth($RES_STATUS);

if (validID($_GET['id']) && !permit('project', $_GET['id'])) header('Location: /res/');

$title = array(
    '/res/' => 'Researchers',
    '/res/project/' => 'Project',
    '/res/project/builder' => 'Builder'
);

$styles = array(
    '#myInformation_form'           => 'width: 100%;',
    '#myInformation_form td.question'  => 'max-width: 20em;',
    '.setlists'                     => 'font-size:85%; float:left; width: 45%; margin:1em 0 1em 1em;',
    '#iconView li'                  => 'height: 45px; width: 45px; background: '. THEME .' no-repeat center center; float:left; margin: 2px; border: 1px solid white; overflow: hidden; color: rgba(0,0,0,0);',
    '#projectList li'               => 'min-height: 30px; padding-right: 30px; background-position: right center; background-repeat: no-repeat;',
    '.setlists h2, .setlists h3'    => 'padding:0',
    '.setlists ul, .setlists ol'    => 'height:400px; overflow:auto;',
    '.setlists li'                  => 'border:1px solid grey; padding:2px;',
    '.setlists li .status'          => 'display: inline-block; width:1.2em; height:1.2em; 
                                        border-radius: 0 .5em; float: right; 
                                        text-align: center; line-height: 1.2em; font-size:100%',
    '.setlists li.active .status'   => 'background-color: hsla(120,100%,50%, 50%);',
    '.setlists li.archive .status' => 'background-color: hsla(120,0%,50%, 50%);',
    '.setlists li.test .status'     => 'background-color: hsla(0,100%,50%, 50%);',
    '.setlists li:hover'            => 'color: white;background-color:hsl(0, 0%, 40%); cursor:default;',
    '.setlists li+li'               => 'border-top:0;',
    '#new_set'                      => '',
    '#new_set li'                   => 'list-style-position: outside; margin-left:30px; cursor: ns-resize;',
    '#labnotes, #intro'             => 'vertical-align: text-top;',
    '.search'                       => 'width: 94%; margin: 0 .5em;'
);

/****************************************************
 * Show existing project info
 ***************************************************/

$project_info = array(
    'project_id' => 'NULL',
    'intro' => 'The introduction to the page. This can be just a short paragraph or include images and html.',
    'labnotes' => '',
    'blurb' => 'A short 1-line description of the project'
);

$item_list = array();

if (isset($_GET['checkurl'])) {
    $checkurl = my_clean($_GET['checkurl']);
    $q = new myQuery("SELECT id FROM project WHERE url='$checkurl'");
    if ($q->get_num_rows() > 0 && $q->get_one() != $_GET['id']) {
        echo 'Your URL is already taken.';
    }
    
    exit;
} else if (isset($_GET['id'])) {
    // get a set's information
    
    $id = my_clean($_GET['id']);
    if (is_numeric($id) & $id>0) {
        $q = new myQuery("SELECT id AS project_id, name as 'project_name', 
                          res_name, url, intro, sex, lower_age, upper_age, 
                          blurb, labnotes, status 
                          FROM project WHERE id='{$id}'");
        
        if ($q->get_num_rows() == 0) { header('Location: /res/project/'); }
        $project_info = $q->get_assoc(0);
        
        $q->set_query("SELECT item_type as type, item_id as id, icon, 
            IF(item_type='exp', exp.res_name, 
                IF(item_type='quest', quest.res_name, 
                    IF(item_type='set', sets.res_name,'No such item'))) as res_name,
            IF(item_type='exp', exp.name, 
                IF(item_type='quest', quest.name, 
                    IF(item_type='set', sets.name,'No such item'))) as name 
            FROM project_items as p
            LEFT JOIN exp ON item_type='exp' AND exp.id=item_id
            LEFT JOIN quest ON item_type='quest' AND quest.id=item_id
            LEFT JOIN sets ON item_type='set' AND sets.id=item_id
            WHERE p.project_id='$id' ORDER BY item_n");
        foreach ($q->get_assoc() as $item) {
            $icon = $item['icon'];
            $item_list[] = "<li ondblclick='deleteItem(this)' 
                                style='background-image: url({$icon})' 
                                icon='{$item['icon']}' 
                                title='{$item['type']}_{$item['id']}'>
                                {$item['type']}_{$item['id']}: 
                                {$item['res_name']}</li>";
        }
    }
    
}

/****************************************************/
/* !AJAX Responses */
/***************************************************/
 
if (array_key_exists('save', $_GET)) {
    // save a set
    $clean = my_clean($_POST);
    
    $proj_query = sprintf('REPLACE INTO project (id, name, res_name, url, 
                              intro, labnotes, sex, lower_age, upper_age, blurb, create_date) 
                            VALUES (%s, "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", NOW())',
                            $clean['project_id'],
                            $clean['project_name'],
                            $clean['res_name'],
                            $clean['url'],
                            $clean['intro'],
                            $clean['labnotes'],
                            $clean['sex'],
                            $clean['lower_age'],
                            $clean['upper_age'],
                            $clean['blurb']);
    
    $proj_query = str_replace('""', 'NULL', $proj_query);
    $proj_query = str_replace('"NULL"', 'NULL', $proj_query);
    
    $q = new myQuery($proj_query);
    
    
    // get new set ID if a new set
    if ('NULL' == $clean['project_id']) $clean['project_id'] = $q->get_insert_id();
    
    // delete old items from set list
    $q = new myQuery('DELETE FROM project_items WHERE project_id=' . $clean['project_id']);
    
    // add set items to set list
    $project_items = explode(';', $clean['project_items']);
    $project_icons = explode(';', $clean['project_icons']);
    
    $item_query = array();
    foreach ($project_items as $n => $item) {
        $item_n = $n+1;
        $i = explode('_', $item);
        $icon = ($project_icons[$n] == 'undefined') ? 'NULL' : "'{$project_icons[$n]}'";
        $item_query[] = "('{$clean['project_id']}', '{$i[0]}', '{$i[1]}', '{$item_n}', {$icon})";
    }
    
    if (count($item_query)) {
        $q = new myQuery('INSERT INTO project_items (project_id, item_type, item_id, item_n, icon) VALUES ' . implode(',', $item_query));
    }
    
    // add to access list
    $q = new myQuery("REPLACE INTO access (type, id, user_id) VALUES ('project', {$clean['project_id']}, {$_SESSION['user_id']})");
    
    echo 'saved:' . $clean['project_id']; exit;
} else if (array_key_exists('delete', $_GET) && validID($_POST['project_id'])) {
    // delete the set
    $q = new myQuery('DELETE FROM project WHERE id=' . $_POST['project_id']);
    $q = new myQuery('DELETE FROM project_items WHERE project_id=' . $_POST['project_id']);
    $q = new myQuery('DELETE FROM access WHERE type="project" AND id=' . $_POST['project_id']);
    $q = new myQuery('DELETE FROM dashboard WHERE type="project" AND id=' . $_POST['project_id']);
    
    echo 'deleted';
    exit;
}

/****************************************************
 * Project Table
 ***************************************************/
 
$input_width = 550;

$table_setup = array();
 
$table_setup['project_id'] = new hiddenInput('project_id', 'project_id', $project_info['project_id']);

$table_setup['project_name'] = new input('project_name', 'project_name', $project_info['project_name']);
$table_setup['project_name']->set_width($input_width);
$table_setup['project_name']->set_placeholder('Name for Participants');
$table_setup['project_name']->set_question('Name for Participants');

$table_setup['res_name'] = new input('res_name', 'res_name', $project_info['res_name']);
$table_setup['res_name']->set_width($input_width);
$table_setup['res_name']->set_placeholder('Name for Researchers');
$table_setup['res_name']->set_question('Name for Researchers');

$table_setup['url'] = new input('url', 'url', $project_info['url']);
$table_setup['url']->set_width($input_width);
$table_setup['url']->set_placeholder('shortname');
$table_setup['url']->set_question('URL');

$table_setup['blurb'] = new textarea('blurb', 'blurb', $project_info['blurb']);
$table_setup['blurb']->set_dimensions($input_width, 40, true, 40, 300);
$table_setup['blurb']->set_question('Blurb');

$table_setup['intro'] = new textarea('intro', 'intro', $project_info['intro']);
$table_setup['intro']->set_dimensions($input_width, 40, true, 40, 300);
$table_setup['intro']->set_question('Intro');

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

$table_setup['labnotes'] = new textarea('labnotes', 'labnotes', $project_info['labnotes']);
$table_setup['labnotes']->set_dimensions($input_width, 40, true, 40, 300);
$table_setup['labnotes']->set_question('Labnotes');

// set up table
$form_table = new formTable();
$form_table->set_table_id('myInformation');
$form_table->set_title('Project Information');
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
$set_list = array();
foreach ($sets as $s) {
    $abr = ucwords(substr($s['status'], 0,1));
    $set_list[] = "<li title='set_{$s['id']}' class='{$s['status']}'>
                        {$s['id']}: {$s['res_name']}<span class='status'>{$abr}</span></li>" . ENDLINE;
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
    $exp_list[] = "<li title='exp_{$s['id']}' class='{$s['status']}'>
                        {$s['id']}: {$s['res_name']}<span class='status'>{$abr}</span></li>" . ENDLINE;
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
    $quest_list[] = "<li title='quest_{$s['id']}' class='{$s['status']}'>
                        {$s['id']}: {$s['res_name']}<span class='status'>{$abr}</span></li>" . ENDLINE;
}

$basedirs = array(
    "linearicons" => "/images/linearicons/"
);
$icon_list = array();
foreach ($basedirs as $section => $basedir) {
    $d = dir($_SERVER['DOCUMENT_ROOT'] . $basedir);
    $images = array();
    
    while (false !== ($f = $d->read())) {
        if (substr($f, -4) == ".php") {
            $name = str_replace('.php', '', $f);
            $images[$name] = $basedir . $name;
        }
    }
    ksort($images);
    
    foreach ($images as $name => $src) {
        $icon_list[] .= "   <li style='background-image: url(\"$src?c=FFF\");' title='$src'>$name</li>\n";
    }
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
    <button id='save-project'>Save</button>
    <button id='info-project'>Info</button>
</div>

<?= $form_table->print_form() ?>

<div class='toolbar'>
    <span id="typeChanger">View Items: 
        <input type="radio" id="viewExp" name="typeChanger" checked="checked"><label for="viewExp">Exp</label><input 
        type="radio" id="viewQuest" name="typeChanger"><label for="viewQuest">Quest</label><input 
        type="radio" id="viewSets" name="typeChanger"><label for="viewSets">Sets</label><input 
        type="radio" id="viewIcons" name="typeChanger"><label for="viewIcons">Icons</label>
    </span>
</div>

<div class="setlists" id="iconView">
    <h2>Icons</h2>
    <h3 class="note">Drag to an item in the Project List</h3>
    <input type='text' class='search' onkeyup='search(this.value, "icons");' placeholder="search" />
    <ul id="icons">
        <?= implode('', $icon_list) ?>
    </ul>
</div>

<div class="setlists"  id="expView">
    <h2>Available Experiments</h2>
    <h3 class="note">Click to add to Project List</h3>
    <input type='text' class='search' onkeyup='search(this.value, "exp");' placeholder="search" />
    <ul id="exp">
        <?= implode('', $exp_list) ?>
    </ul>
</div>

<div class="setlists" id="questView">
    <h2>Available Questionnaires</h2> 
    <h3 class="note">Click to add to Project List</h3>
    <input type='text' class='search' onkeyup='search(this.value, "quest");' placeholder="search" />
    <ul id="quest">
        <?= implode('', $quest_list) ?>
    </ul>
</div>

<div class="setlists" id="setView"> 
    <h2>Available Sets</h2>
    <h3 class="note">Click to add to Project List</h3>
    <input type='text' class='search' onkeyup='search(this.value, "set");' placeholder="search" />   
    <ul id="set">
        <?= implode('', $set_list) ?>
    </ul>
</div>

<div class="setlists" id="projectList">
    <h2>Project List</h2>
    <h3 class="note">Double-click to remove</h3>
    <ol id="new_set">
        <?= implode('', $item_list) ?>
    </ol>
</div>


<div id="help" title="Project Builder Help">
    <ul>
        <li>Type into the search box to narrow down your list. It searches the ID number and name of items in the list, so you can type in the ID to find an item quickly.</li>
        <li>Click on items to add them to your project list.</li>
        <li>Double-click on items in your project list to delete them.</li>
        <li>Drag items in your project list to reorder them.</li>
        <li>Make sure you include a readable URL, since you will be able to advertise your project at <i>https://exp.psy.gla.ac.uk/project?shortname</i>.</li>
    </ul>
</div>

<!--*************************************************
 * Javascripts for this page
 *************************************************-->

<script>

$(function() {

    stripeList('#new_set');
    stripeList('#exp');
    stripeList('#quest');
    stripeList('#set');
    
    $('#questView').hide();
    $('#setView').hide();
    $('#iconView').hide();
    
    $('#typeChanger').buttonset();

    $('#viewExp').click( function() {
        $('#expView').show();
        $('#questView').hide();
        $('#setView').hide();
        $('#iconView').hide();
    });
    
    $('#viewQuest').click( function() {
        $('#expView').hide();
        $('#questView').show();
        $('#setView').hide();
        $('#iconView').hide();
    });
    
    $('#viewSets').click( function() {
        $('#expView').hide();
        $('#questView').hide();
        $('#setView').show();
        $('#iconView').hide();
    });
    
    $('#viewIcons').click( function() {
        $('#expView').hide();
        $('#questView').hide();
        $('#setView').hide();
        $('#iconView').show();
    });

    $('.setlists ul li').click(function() { add(this); });
    
    $('#url').change( function() {
        var nonWord = $('#url').val().replace(/^\w+$/, '');
        
        $('#url').removeClass('error');
    
        if (nonWord != '') {
            $('<div />').html('<i>' + $('#url').val() + 
                              '</i> is not a valid URL. Please make sure there are no spaces or symbols.')
                         .dialog({modal:true});
            $('#url').addClass('error');
        } else {
            // check if short url is unique
            var url = '/res/project/builder?checkurl=' + $('#url').val() + '&id=' + $('#project_id').val();
            $.get(url, function(data) {
                if (data != '') { 
                    $('<div />').html(data).dialog({modal:true}); 
                    $('#url').addClass('error');
                }
            });
        }
    });
    
    $('#go-project').button().click( function() { 
        if ($('#url').val() != '') { window.location='/project?' + $('#url').val(); }
    });
    
    $('#info-project').button().click( function() { 
        if ($('#project_id').val() != '') { window.location='/res/project/info?id=' + $('#project_id').val(); }
    });
    
    $('#save-project').button().click( function() { 
        // serialize the new project order
        var setitems = '';
        var icons = '';
        
        $('#new_set li').each( function(e) {
            setitems = setitems + ';' + $(this).attr('title');
            icons = icons + ';' + $(this).attr('icon');
        });
                        
        var serial =    $('#myInformation_form').serialize() + 
                        '&project_items=' + setitems.substring(1) + '&' +
                        'project_icons=' + icons.substring(1);
        
        $.ajax({
            url: './builder?save',
            type: 'POST',
            data: serial,
            success: function(data) {
                var resp = data.split(':');
                if (resp[0] == 'saved') {
                    growl('Project Saved', 500);
                    $('#project_id').val(resp[1]);
                } else {
                    $('<div />').html(data).dialog();
                }
            }
        });
    });
    
    $('#delete-project').button().click( function() {
        $( "<div/>").html("Do you really want to delete this project?").dialog({
            title: "Delete Project",
            position: ['center', 100],
            modal: true,
            buttons: {
                Cancel: function() {
                    $( this ).dialog( "close" );
                },
                "Delete": function() {
                    $( this ).dialog( "close" );
                    $.ajax({
                        url: '?delete',
                        type: 'POST',
                        data: $('#project_id').serialize(),
                        success: function(data) {
                            if (data == 'deleted') {
                                window.location = '/res/project';
                            } else {
                                $('<div />').html(data).dialog();
                            }
                        }
                    });
                },
            }
        }); 
    });
    
    $('#new_set').sortable({
        change: function(event, ui) { stripeList('#new_set'); }
    });
    
    $('#iconView li').draggable({
        helper: "clone",
        cursorAt: { top: 15, left: 15 }
    });
    
    $('#new_set li').droppable({
        tolerance: "pointer",
        //hoverClass: "drop_hover",
        drop: function( event, ui ) {
            $(this).css('background-image', 'url(' + $(ui.draggable).attr('title') + ')');
            $(this).attr('icon', $(ui.draggable).attr('title'));
        }
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
    newItem.className = item.className;
    
    $('#new_set').append(newItem);
    $('#new_set').sortable({
        change: function(event, ui) { stripeList('#new_set'); }
    });
    
    $('#new_set li').droppable({
        tolerance: "pointer",
        //hoverClass: "drop_hover",
        drop: function( event, ui ) {
            $(this).css('background-image', 'url(' + $(ui.draggable).attr('title') + ')');
            $(this).attr('icon', $(ui.draggable).attr('title'));
        }
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