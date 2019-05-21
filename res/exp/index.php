<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
auth($RES_STATUS);

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
        if ($_SESSION['status'] == "admin") {
            $access_user = 'access.user_id';
        } else {
            $access_user = 'SELECT ' . $_SESSION['user_id'] . ' AS supervisee_id UNION
            SELECT supervisee_id FROM supervise WHERE supervisor_id=' . $_SESSION['user_id'];
        }
	} elseif (validID($_GET['owner'])) {
		$access_user = $_GET['owner'];
	}
}

$aq = 'CONCAT("<span class=\'fav", 
        IF(d.id IS NOT NULL, " heart", ""), 
        "\' id=\'dash", exp.id, "\'>",
        IF(d.id IS NOT NULL, "+", "-"), 
        "</span>") as "Favs", 
    CONCAT("<a href=\'info?id=", exp.id, "\'>", exp.id, "</a>") as "ID", 
    CONCAT("<span class=\'expname\'>", "<a href=\'info?id=", exp.id, "\'>", res_name, "</a>", "</span>") as "Name", 
    CONCAT("<span class=\'labnotes\'>", labnotes, "</span>") as "Labnotes", 
    CONCAT(exptype, IF(subtype IN("standard","large_n"), "", CONCAT(" ", subtype))) as "Type",
    status as "Status", 
    DATE_FORMAT(create_date, "%Y-%m-%d") as "Date Created"';
$accessquery = "SELECT {$aq} 
                        FROM exp 
                        LEFT JOIN access USING (id) 
                        LEFT JOIN dashboard as d ON d.id = exp.id AND d.type='exp' AND d.user_id={$_SESSION['user_id']}
                        WHERE access.type='exp' 
                        AND access.user_id IN({$access_user})
                        GROUP BY exp.id ORDER BY d.user_id DESC, exp.id DESC";

$my = new myQuery($accessquery);
    
$search = new input('search', 'search');

$user_id = $_SESSION['user_id'];
if ($_SESSION['status'] == 'admin') {
    $ownerquery = "SELECT res.user_id as user_id, 
        CONCAT(lastname, ', ', firstname) as name 
        FROM res 
        LEFT JOIN access USING (user_id)
        WHERE (access.type='exp' AND access.user_id IS NOT NULL) 
          OR res.user_id={$user_id} 
        ORDER BY lastname, firstname";
} else if ($_SESSION['status'] == 'res') {
    $ownerquery = "SELECT res.user_id as user_id, 
        CONCAT(lastname, ', ', firstname) as name 
        FROM res 
        LEFT JOIN access USING (user_id)
        LEFT JOIN supervise ON res.user_id=supervisee_id
        WHERE (access.type='exp' AND access.user_id IS NOT NULL 
        AND (supervisor_id={$user_id} OR access.user_id={$user_id})) 
        OR res.user_id={$user_id}
        ORDER BY lastname, firstname";
}

if (!empty($ownerquery)) { 
    $owners = new myQuery($ownerquery);
    
    $ownerlist = array('all' => 'All');
    foreach ($owners->get_assoc() as $o) {
        $ownerlist[$o['user_id']] = $o['name'];
    }
    $owner = new select('owner', 'owner', ifEmpty($_GET['owner'], $_SESSION['user_id']));
    $owner->set_options($ownerlist);
    $owner->set_null(false);
    $owner->set_eventHandlers(array('onchange' => 'changePage()'));
}
    
/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

// search box
echo '<div class="searchline toolbar">';
if (!empty($ownerquery)) { 
    echo 'Owner: ';
    echo $owner->get_element();
}
echo 'Search: ';
echo $search->get_element();
echo '<button id="new_exp">New experiment</button></div>';

echo $my->get_result_as_table(true, true);

$new_exp_buttons = array(
    "builder?exptype=2afc" => "2-Alternative Forced-choice (2AFC)",
    "builder?exptype=jnd" => "2AFC with 8-Button Strength of Choice",
    "builder?exptype=buttons" => "Labelled Buttons",
    "builder?exptype=slider" => "Slider",
    //"builder?exptype=rating" => "Numeric Rating",
    //"builder?exptype=interactive" => "Interactive",
    "builder?exptype=xafc" => "X-Alternative Forced-choice (XAFC)",
    "builder?exptype=sort" => "Sorting",
    //"builder?exptype=motivation" => "Motivation",
    "builder?exptype=slideshow" => "Slideshow",
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
    
    $(function() {
        $( "#new_exp" ).button().click(function() { $( "#dialog-typechooser" ).dialog( "open" ); });  
        $( "#dialog-typechooser" ).dialog({
            autoOpen: false,
            show: "scale",
            hide: "scale",
            width: "35em",
            modal: true,
        });
        
        $('#search').keyup( function() { narrowTable('table.query tbody', this.value); } );
        
        dashboard_checkboxes('exp'); // function defined in myfunctions.js
        
        <?= $status_changer ?> // function defined in myfunctions.js
        
    });
    
    
    function changePage() {
        var owner = $('#owner').val();
        window.location.href = "./?owner=" + owner;
    }

</script>

<?php

$page->displayFooter();

?>