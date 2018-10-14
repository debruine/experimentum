<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';
auth(array('student', 'researcher', 'admin'));

$title = array(
    '/res/' => 'Researchers',
    '/res/project/' => 'Project',
    '/res/project/builder' => 'Builder'
);

$styles = array(
    '.setlists'                     => 'font-size:85%; float:right; width: 49%;',
    '#projectList'                  => 'float: left;',
    '#iconView li'                  => 'height: 45px; width: 45px; background: '. THEME .' no-repeat center center; float:left; margin: 2px; border: 1px solid white; overflow: hidden; color: rgba(0,0,0,0);',
    '#projectList li'               => 'min-height: 30px; padding-right: 30px; background-position: right center; background-repeat: no-repeat;',
    '.setlists h2'                  => 'padding:0',
    '.setlists ul, .setlists ol'    => 'height:400px; overflow:auto;',
    '.setlists li'                  => 'border:1px solid grey; padding:2px;',
    '.setlists li:hover'            => 'color:hsl(0, 100%, 40%); cursor:default;',
    '.setlists li+li'               => 'border-top:0;',
    '#new_set'                      => '',
    '#new_set li'                   => 'list-style-position: outside; margin-left:30px; cursor: ns-resize;',
    '#labnotes, #intro'             => 'vertical-align: text-top;',
    '.search'                       => 'width: 94%; margin: 0 .5em;',
    '#typeChanger'                  => 'float: right;'
);

/****************************************************
 * Show existing project info
 ***************************************************/

$project_info = array(
    'project_id' => 'NULL',
    'intro' => 'The introduction to the page. This can be just a short paragraph or include images and html.',
    'labnotes' => ''
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
        $q = new myQuery("SELECT id AS project_id, name as 'project_name', res_name, url, intro, labnotes, status FROM project WHERE id='{$id}'");
        
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
            $item_list[] = "<li ondblclick='deleteItem(this)' style='background-image: url({$icon})' icon='{$item['icon']}' title='{$item['type']}_{$item['id']}'>{$item['type']}_{$item['id']}: {$item['res_name']}<br />{$item['name']}</li>";
        }
    }
    
}

/****************************************************/
/* !AJAX Responses */
/***************************************************/
 
if (array_key_exists('save', $_GET)) {
    // save a set
    
    $clean = my_clean($_POST);
    
    // make sure user has permission to edit this project
    if ($_SESSION['status'] == 'student') {
        // student researchers cannot edit anything
        echo 'You may not edit or create projects'; exit; 
    } elseif ($_SESSION['status'] == 'researcher') { 
        // researchers can edit only their own experiments
        if (validID($clean['project_id'])) {
            $myaccess = new myQuery('SELECT user_id, status FROM project LEFT JOIN access USING (id) WHERE access.type="project" AND id='.$clean['project_id']." AND user_id=".$_SESSION['user_id']);
            $checkuser = $myaccess->get_assoc(0);
            $status = $checkuser['status'];
            if ($checkuser['user_id'] != $_SESSION['user_id']) { echo 'You do not have permission to edit this project.'; exit; }
        }
    } elseif ($_SESSION['status'] == 'admin') { 
        if (validID($clean['project_id'])) {
            $myaccess = new myQuery('SELECT status FROM project WHERE id='.$clean['project_id']);
            $status = $myaccess->get_one();
        }
    }
    
    $q = new myQuery(sprintf('REPLACE INTO project (id, name, res_name, status, url, intro, labnotes, create_date) 
                            VALUES (%s, "%s", "%s", "%s", "%s", "%s", "%s", NOW())',
                            $clean['project_id'],
                            $clean['project_name'],
                            $clean['res_name'],
                            ifEmpty($status, 'test'),
                            $clean['url'],
                            $clean['intro'],
                            $clean['labnotes']));
    
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
        $icon = str_replace("theme/", "white/", $icon);
        $item_query[] = "('{$clean['project_id']}', '{$i[0]}', '{$i[1]}', '{$item_n}', {$icon})";
    }
    $q = new myQuery('INSERT INTO project_items (project_id, item_type, item_id, item_n, icon) VALUES ' . implode(',', $item_query));
    
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
 
$project_id = new hiddenInput('project_id', 'project_id', $project_info['project_id']);

$project_name = new input('project_name', 'project_name', $project_info['project_name']);
$project_name->set_width(400);
$project_name->set_placeholder('Title for Participants');

$res_name = new input('res_name', 'res_name', $project_info['res_name']);
$res_name->set_width(300);
$res_name->set_placeholder('Name for Researchers');

$url = new input('url', 'url', $project_info['url']);
$url->set_width(100);
$url->set_placeholder('shortname');

$intro = new textarea('intro', 'intro', $project_info['intro']);
$intro->set_dimensions(400, 40, true, 40, 300);

$labnotes = new textarea('labnotes', 'labnotes', $project_info['labnotes']);
$labnotes->set_dimensions(400, 40, true, 40, 300);

$q = new myQuery('SELECT id, res_name, name, status FROM sets ORDER BY id');
$sets = $q->get_assoc();
$set_list = array();
foreach ($sets as $s) {
    $set_list[] = "<li title='set_{$s['id']}'>{$s['id']}: {$s['res_name']}<br />{$s['name']}</li>" . ENDLINE;
}

$q->set_query('SELECT id, res_name, name, status FROM exp ORDER BY id');
$exps = $q->get_assoc();
$exp_list = array();
foreach ($exps as $s) {
    $exp_list[] = "<li title='exp_{$s['id']}'>{$s['id']}: {$s['res_name']}<br />{$s['name']}</li>" . ENDLINE;
}

$q->set_query('SELECT id, res_name, name, status FROM quest ORDER BY id');
$quests = $q->get_assoc();
$quest_list = array();
foreach ($quests as $s) {
    $quest_list[] = "<li title='quest_{$s['id']}'>{$s['id']}: {$s['res_name']}<br />{$s['name']}</li>" . ENDLINE;
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
    
    foreach ($images as $name => $src) {
        $icon_list[] .= "   <li style='background-image: url(\"$src?c=FFF\");' title='$src'>$name</li>\n";
    }
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
    <div class='toolbar-line'>
        Project Name: <?= $project_name->get_element() ?> 
        <?= $project_id->print_formLine() ?>
        <button id='save-project'>Save</button>
        <button id='delete-project'>Delete</button>
        <button id='go-project'>Go</button>
        
        <span id="typeChanger">View Items: 
            <input type="radio" id="viewExp" name="typeChanger" checked="checked"><label for="viewExp">Exp</label> 
            <input type="radio" id="viewQuest" name="typeChanger"><label for="viewQuest">Quest</label> 
            <input type="radio" id="viewSets" name="typeChanger"><label for="viewSets">Sets</label> 
            <input type="radio" id="viewIcons" name="typeChanger"><label for="viewIcons">Icons</label>
        </span>
    </div>
    
    <div>
        Researcher Name: <?= $res_name->get_element() ?>
        URL: <?= $url->get_element() ?> 
    </div>
    
    <div class='toolbar-line'>
        Intro: <?= $intro->get_element() ?> 
        Labnotes: <?= $labnotes->get_element() ?>
    </div>
</div>

<div class="setlists" id="projectList">
    <h2>Project List</h2>
    <ol id="new_set">
        <?= implode('', $item_list) ?>
    </ol>
</div>

<div class="setlists" id="iconView">
    <h2>Icons</h2>
    <input type='text' class='search' onkeyup='search(this.value, "icons");' />
    <ul id="icons">
        <?= implode('', $icon_list) ?>
    </ul>
</div>

<div class="setlists"  id="expView">
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


<div id="help" title="Project Builder Help">
    <ul>
        <li>Type into the search box to narrow down your list. It searches the ID number and name of items in the list, so you can type in the ID to find an item quickly.</li>
        <li>Click on items to add them to your project list.</li>
        <li>Double-click on items in your project list to delete them.</li>
        <li>Drag items in your project list to reorder them.</li>
        <li>Make sure you include a readable URL, since you will be able to advertise your project at <i>http://faceresearch.org/project?shortname</i>.</li>
    </ul>
</div>

<!--*************************************************
 * Javascripts for this page
 *************************************************-->

<script>

$j(function() {

    stripeList('#new_set');
    stripeList('#exp');
    stripeList('#quest');
    stripeList('#set');
    
    $j('#questView').hide();
    $j('#setView').hide();
    $j('#iconView').hide();
    
    $j('#typeChanger').buttonset();

    $j('#viewExp').click( function() {
        $j('#expView').show();
        $j('#questView').hide();
        $j('#setView').hide();
        $j('#iconView').hide();
    });
    
    $j('#viewQuest').click( function() {
        $j('#expView').hide();
        $j('#questView').show();
        $j('#setView').hide();
        $j('#iconView').hide();
    });
    
    $j('#viewSets').click( function() {
        $j('#expView').hide();
        $j('#questView').hide();
        $j('#setView').show();
        $j('#iconView').hide();
    });
    
    $j('#viewIcons').click( function() {
        $j('#expView').hide();
        $j('#questView').hide();
        $j('#setView').hide();
        $j('#iconView').show();
    });

    $j('.setlists ul li').click(function() { add(this); });
    
    $j('#url').change( function() {
        var nonWord = $j('#url').val().replace(/^\w+$/, '');
    
        if (nonWord != '') {
            $j('<div />').html('<i>' + $j('#url').val() + '</i> is not a valid URL. Please make sure there are no spaces or symbols.').dialog({modal:true});
        } else {
            // check if short url is unique
            var url = '/res/project/builder?checkurl=' + $j('#url').val() + '&id=' + $j('#project_id').val();
            $j.get(url, function(data) {
                if (data != '') { $j('<div />').html(data).dialog({modal:true}); }
            });
        }
    });
    
    $j('#go-project').button().click( function() { 
        if ($j('#url').val() != '') { window.location='/project?' + $j('#url').val(); }
    });
    
    $j('#save-project').button().click( function() { 
        // serialize the new project order
        var setitems = '';
        var icons = '';
        
        $j('#new_set li').each( function(e) {
            setitems = setitems + ';' + $j(this).attr('title');
            icons = icons + ';' + $j(this).attr('icon');
        });
        
        var serial =    $j('#project_id').serialize() + '&' +
                        $j('#project_name').serialize() + '&' +
                        $j('#res_name').serialize() + '&' +
                        $j('#url').serialize() + '&' +
                        $j('#intro').serialize() + '&' +
                        $j('#labnotes').serialize() + '&' +
                        'project_items=' + setitems.substring(1) + '&' +
                        'project_icons=' + icons.substring(1);
        
        $j.ajax({
            url: './builder?save',
            type: 'POST',
            data: serial,
            success: function(data) {
                var resp = data.split(':');
                if (resp[0] == 'saved') {
                    growl('Project Saved', 500);
                    $j('#project_id').val(resp[1]);
                } else {
                    $j('<div />').html(data).dialog();
                }
            }
        });
    });
    
    $j('#delete-project').button().click( function() {
        $j( "<div/>").html("Do you really want to delete this project?").dialog({
            title: "Delete Project",
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
                        data: $j('#project_id').serialize(),
                        success: function(data) {
                            if (data == 'deleted') {
                                window.location = '/res/project';
                            } else {
                                $j('<div />').html(data).dialog();
                            }
                        }
                    });
                },
            }
        }); 
    });
    
    $j('#new_set').sortable({
        change: function(event, ui) { stripeList('#new_set'); }
    });
    
    $j('#iconView li').draggable({
        helper: "clone",
        cursorAt: { top: 15, left: 15 }
    });
    
    $j('#new_set li').droppable({
        tolerance: "pointer",
        //hoverClass: "drop_hover",
        drop: function( event, ui ) {
            $j(this).css('background-image', 'url(' + $j(ui.draggable).attr('title').replace('white/','theme/') + ')');
            $j(this).attr('icon', $j(ui.draggable).attr('title'));
        }
    });
    
    sizeToViewport();
    
    window.onresize = sizeToViewport;
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
    
    $j('#new_set li').droppable({
        tolerance: "pointer",
        //hoverClass: "drop_hover",
        drop: function( event, ui ) {
            $j(this).css('background-image', 'url(' + $j(ui.draggable).attr('title') + ')');
            $j(this).attr('icon', $j(ui.draggable).attr('title'));
        }
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