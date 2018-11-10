<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
auth($RES_STATUS);

$status_changer = ($_SESSION['status'] == "admin") ? "statusChanger(5,'quest');" : "";

$title = array(
    '/res/' => 'Researchers',
    '/res/quest/' => 'Questionnaires'
);

$styles = array();


/****************************************************
 * Get Questionnaire Data
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

// set the user whose items to get
$aq = 'CONCAT("<span class=\'fav", 
        IF(d.id IS NOT NULL, " heart", ""), 
        "\' id=\'dash", quest.id, "\'>",
        IF(d.id IS NOT NULL, "+", "-"), 
        "</span>") as "Favs", 
    CONCAT("<a href=\'info?id=", quest.id, "\'>", quest.id, "</a>") as "ID", 
    CONCAT("<span class=\'expname\'>", "<a href=\'info?id=", quest.id, "\'>", res_name, "</a>", "</span>") as "Name", 
    CONCAT("<span class=\'labnotes\'>", labnotes, "</span>") as "Labnotes", 
    status as "Status", 
    DATE_FORMAT(create_date, "%Y-%m-%d") as "Date Created"';
$accessquery = "SELECT {$aq} 
                        FROM quest 
                        LEFT JOIN access USING (id) 
                        LEFT JOIN dashboard as d ON d.id = quest.id AND d.type='quest' AND d.user_id={$_SESSION['user_id']}
                        WHERE access.type='quest' 
                        AND access.user_id IN({$access_user})
                        GROUP BY quest.id ORDER BY d.user_id DESC, quest.id DESC";

$my = new myQuery($accessquery);
    
$search = new input('search', 'search');

$user_id = $_SESSION['user_id'];
if ($_SESSION['status'] == 'admin') {
    $ownerquery = "SELECT res.user_id as user_id, 
        CONCAT(lastname, ', ', firstname) as name 
        FROM res 
        LEFT JOIN access USING (user_id)
        WHERE (access.type='quest' AND access.user_id IS NOT NULL) 
          OR res.user_id={$user_id} 
        ORDER BY lastname, firstname";
} else if ($_SESSION['status'] == 'res') {
    $ownerquery = "SELECT res.user_id as user_id, 
        CONCAT(lastname, ', ', firstname) as name 
        FROM res 
        LEFT JOIN access USING (user_id)
        LEFT JOIN supervise ON res.user_id=supervisee_id
        WHERE (access.type='quest' AND access.user_id IS NOT NULL 
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
echo '<button id="new_quest">New Questionnaire</button></div>';

echo $my->get_result_as_table(true, true);

$new_quest_buttons = array(
    "builder" => "Mixed (different question types)",
    "builder?radiopage" => "Radiopage (response options across top)",
    "builder?ranking" => "Ranking (order a list of items)"
);

?>

<div id="dialog-typechooser" title="Choose a Questionnaire Type">
    <?= linkList($new_quest_buttons, '', 'ul') ?>
</div>

<div id="help" title="Questionnaire Finder Help">
    <ul>
        <li>Type into the search box to narrow down your list. It searches the ID number, name and notes, so you can type in the ID to find a specific questionnaire quickly.</li>
        <li>Click on a column title to sort by that column.</li>
        <li>Click on the ID of a questionnaire to view its info page.</li>
        <li>Click on the circle next to a chart to save it to your favourites list (on the <a href="/res/">Researchers</a> page.</li>
        <li>Click on the "New questionnaire" button at the top to start creating a new questionnaire. You will need to choose which type of questionnaire you want to build.<ul>
            <li>&ldquo;Mixed&rdquo; questionnaires allow you to choose a different style for each question, such as a drop-down menu or a text box.</li>
            <li>&ldquo;Ranking&rdquo; questionnaires consist of a list of items that the participant can re-order.</li>
            <li>&ldquo;Radiopage&rdquo; questionnaires show a list of questions down the left column and options across the top row. Participants can click buttons to choose the option for each question.</li>
        </ul></li>
    </ul>
    <p>If you are a admin, you can click on the status to see a drop-down menu to change the status of any item.</p>
</div>

<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script>
    $(function() {
        $( "#new_quest" ).button().click(function() { $( "#dialog-typechooser" ).dialog( "open" ); });  
        $( "#dialog-typechooser" ).dialog({
            autoOpen: false,
            show: "scale",
            hide: "scale",
            width: "35em",
            modal: true,
        });
        
        $('#search').keyup( function() { narrowTable('table.query tbody', this.value); } );
        
        dashboard_checkboxes('quest'); // function defined in myfunctions.js
        
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