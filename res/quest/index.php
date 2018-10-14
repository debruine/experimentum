<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
auth(array('student', 'researcher', 'admin'));

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
		$access_user = 'access.user_id';
	} elseif (validID($_GET['owner'])) {
		$access_user = $_GET['owner'];
	}
}

$howmany = new myQuery("SELECT COUNT(*) as c FROM quest LEFT JOIN access USING (id) WHERE access.type='quest' AND access.user_id='{$access_user}'");

if ($_GET['status'] == "all" || $howmany->get_one() < 50) {
	$visible_statuses = "'test', 'active', 'inactive'";
	$_GET['status'] = "all";
} else if (in_array($_GET['status'], array("test", "active", "inactive"))) { 
	$visible_statuses = "'" . $_GET['status'] . "'";
} else {
	$visible_statuses = "'test'";
	$_GET['status'] = "test";
}

$my = new myQuery('SELECT CONCAT("<span class=\'fav", 
        IF(d.id IS NOT NULL, " heart", ""), 
        "\' id=\'dash", q.id, "\'>",
        IF(d.id IS NOT NULL, "+", "-"), 
        "</span>") as "Favs", 
	CONCAT("<a href=\'info?id=", q.id, "\'>", q.id, "</a>") as "ID", 
	res_name as "Name",
	CONCAT("<span class=\'labnotes\'>", labnotes, "</span") as "Labnotes", 
	status, 
	DATE_FORMAT(create_date, "%Y-%m-%d") as "Date Created"
	FROM quest as q
	LEFT JOIN access USING (id) 
	LEFT JOIN dashboard as d ON d.id = q.id AND d.type="quest" AND d.user_id=' . $_SESSION['user_id'] . '
	WHERE access.type="quest" 
	  AND access.user_id=' . $access_user . '
	  AND status IN (' . $visible_statuses. ')
	GROUP BY q.id ORDER BY d.user_id DESC, q.id DESC');
	
$search = new input('search', 'search');

$owners = new myQuery('SELECT researcher.user_id as user_id, 
	CONCAT(lastname, ", ", initials) as name 
	FROM researcher 
	LEFT JOIN access USING (user_id)
	WHERE access.type="quest" AND access.user_id IS NOT NULL 
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
echo '<button id="new_quest">New Questionnaire</button></div>';

echo $my->get_result_as_table(true, true);

$new_quest_buttons = array(
	"builder" => "Mixed",
	"builder?ranking" => "Ranking",
	"builder?radiopage" => "Radiopage",
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
		<li>Click on the "New questionnaire" button at the top to start creating a new experiment. You will need to choose which type of questionnaire you want to build.<ul>
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
	$j(function() {
		$j( "#new_quest" ).button().click(function() { $j( "#dialog-typechooser" ).dialog( "open" ); });	
		$j( "#dialog-typechooser" ).dialog({
			autoOpen: false,
			show: "scale",
			hide: "scale",
			width: "25em",
			modal: true,
		});
		
		$j('#search').keyup( function() { narrowTable('table.query tbody', this.value); } );
		
		dashboard_checkboxes('quest'); // function defined in myfunctions.js
		
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