<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
auth(array('student', 'researcher', 'admin'));

$status_changer = ($_SESSION['status'] == 'admin') ? "statusChanger(6, 'exp');" : "";

$title = array(
    '/res/' => 'Researchers',
    '/res/exp/' => 'Experiments'
);

$styles = array(
    '.labnotes' => 'display: block; max-height: 3.6em; overflow: hidden; font-size: ',
    '.expname' => 'display: block; max-height: 2.4em; overflow: hidden;', 
    '.labnotes:hover, .expname:hover' => 'max-height: 300em;',
);

/****************************************************
 * Get Experiment Data
 ***************************************************/

// set the user whose items to get
$access_user = $_SESSION['user_id'];
if (array_key_exists('owner', $_GET)) { 
    if ($_GET['owner'] == 'all') {
        $access_user = 'access.user_id';
    } elseif (validID($_GET['owner'])) {
        $access_user = $_GET['owner'];
    }
}

$howmany = new myQuery("SELECT COUNT(*) as c FROM exp LEFT JOIN access USING (id) WHERE access.type='exp' AND access.user_id='{$access_user}'");

if ($_GET['status'] == "all" || $howmany->get_one() < 50) {
    $visible_statuses = "'test', active', 'inactive'";
    $_GET['status'] = "all";
} else if (in_array($_GET['status'], array("test", "active", "inactive"))) { 
    $visible_statuses = "'" . $_GET['status'] . "'";
} else {
    $visible_statuses = "'test'";
    $_GET['status'] = "test";
}

$my = new myQuery('SELECT CONCAT("<span class=\'fav", 
        IF(d.id IS NOT NULL, " heart", ""), 
        "\' id=\'dash", exp.id, "\'>",
        IF(d.id IS NOT NULL, "+", "-"), 
        "</span>") as "Favs", 
    CONCAT("<a href=\'info?id=", exp.id, "\'>", exp.id, "</a>") as "ID", 
    CONCAT("<span class=\'expname\'>", "<a href=\'info?id=", exp.id, "\'>", res_name, "</a>", "</span>") as "Name", 
    CONCAT("<span class=\'labnotes\'>", labnotes, "</span>") as "Labnotes", 
    CONCAT(exptype, IF(subtype IN("standard","large_n"), "", CONCAT(" ", subtype))) as "Type",
    status as "Status", 
    DATE_FORMAT(create_date, "%Y-%m-%d") as "Date Created"
    FROM exp 
    LEFT JOIN access USING (id) 
    LEFT JOIN dashboard as d ON d.id = exp.id AND d.type="exp" AND d.user_id=' . $_SESSION['user_id'] . '
    WHERE access.type="exp" 
      AND access.user_id=' . $access_user . '
      AND status IN (' . $visible_statuses. ')
    GROUP BY exp.id ORDER BY d.user_id DESC, exp.id DESC');
    
$search = new input('search', 'search');

$owners = new myQuery('SELECT researcher.user_id as user_id, 
    CONCAT(lastname, ", ", initials) as name 
    FROM researcher 
    LEFT JOIN access USING (user_id)
    WHERE access.type="exp" AND access.user_id IS NOT NULL 
    ORDER BY lastname, initials');
$ownerlist = array('all' => 'All');
foreach ($owners->get_assoc() as $o) {
    $ownerlist[$o['user_id']] = $o['name'];
}
$owner = new select('owner', 'owner', $access_user);
$owner->set_options($ownerlist);
$owner->set_null(false);
$owner->set_eventHandlers(array('onchange' => 'changePage()'));


$status = new select('status', 'status', $_GET['status']);
$status->set_options(array(
    "all" => "all", 
    "test" => "test", 
    "active" => "active", 
    "inactive" => "inactive"
));
$status->set_null(false);
$status->set_eventHandlers(array('onchange' => 'changePage()'));
    
/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_logo(true);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

// search box
echo '<div class="searchline toolbar">Owner: ';
echo $owner->get_element();
echo 'Show: ';
echo $status->get_element();
echo 'Search: ';
echo $search->get_element();
echo '<button id="new_exp">New experiment</button></div>';

echo $my->get_result_as_table(true, true);

$new_exp_buttons = array(
    "builder?exptype=2afc" => "2-Alternative Forced-choice (2AFC)",
    "builder?exptype=jnd" => "2AFC with 8-Button Strength of Choice",
    "builder?exptype=buttons" => "Labelled Buttons",
    "builder?exptype=rating" => "Numeric Rating",
    //"builder?exptype=interactive" => "Interactive",
    "builder?exptype=xafc" => "X-Alternative Forced-choice (XAFC)",
    "builder?exptype=sort" => "Sorting",
    //"builder?exptype=motivation" => "Motivation",
    //"builder?exptype=adaptation" => "Adaptation",
    //"builder?exptype=nback" => "N-back",
    //"builder?exptype=other" => "Custom"
);

?>

<div id="dialog-typechooser" title="Choose an Experiment Type">
    <?= linkList($new_exp_buttons, '', 'ul') ?>
</div>

<div id="help" title="Experiment Finder Help">
    <ul>
        <li>Type into the search box to narrow down your list. It searches the ID number, name and notes, so you can type in the ID to find a specific experiment quickly.</li>
        <li>Click on a column title to sort by that column.</li>
        <li>Click on the ID of an experiment to view its info page.</li>
        <li>Click on the circle next to a chart to save it to your favourites list (on the <a href="/res/">Researchers</a> page.</li>
        <li>Click on the "New experiment" button at the top to start creating a new experiment. You will need to choose which type of experiment you want to build.</li>
    </ul>
    <p>If you are a admin, you can click on the status to see a drop-down menu to change the status of any item.</p>
</div>

<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script>
    
    $j(function() {
        $j( "#new_exp" ).button().click(function() { $j( "#dialog-typechooser" ).dialog( "open" ); });  
        $j( "#dialog-typechooser" ).dialog({
            autoOpen: false,
            show: "scale",
            hide: "scale",
            width: "35em",
            modal: true,
        });
        
        $j('#search').keyup( function() { narrowTable('table.query tbody', this.value); } );
        
        dashboard_checkboxes('exp'); // function defined in myfunctions.js
        
        <?= $status_changer ?> // function defined in myfunctions.js
        
    });
    
    
    function changePage() {
        var owner = $j('#owner').val();
        var status = $j('#status').val();
        window.location.href = "./?owner=" + owner + "&status=" + status;
    }

</script>

<?php

$page->displayFooter();

?>