<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
auth(array('student', 'researcher', 'admin'));

$status_changer = ($_SESSION['status'] == 'admin') ? "statusChanger(5, 'sets');" : "";

$title = array(
	'/res/' => 'Researchers',
	'/res/set/' => 'Sets'
);

$styles = array(
	'.item_text' => 'display: none;',
	'td' => 'text-align: left;',
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

$my = new myQuery('SELECT CONCAT("<span class=\'fav", 
        IF(d.id IS NOT NULL, " heart", ""), 
        "\' id=\'dash", s.id, "\'>",
        IF(d.id IS NOT NULL, "+", "-"), 
        "</span>") as "Favs",
    CONCAT("<a href=\'info?id=", s.id, "\'>", s.id, "</a>") as "ID", 
	res_name as "Name",
	CONCAT("<span class=\'labnotes\'>", labnotes, "</span><span class=\'item_text\'>", GROUP_CONCAT(CONCAT(item_type,"_",item_id) SEPARATOR " "), "</span>") as "Labnotes", 
	status, 
	DATE_FORMAT(create_date, "%Y-%m-%d") as "Date Created"
	FROM sets as s
	LEFT JOIN access USING (id) 
	LEFT JOIN set_items ON (set_id=s.id)
	LEFT JOIN dashboard as d ON d.id = s.id AND d.type="set" AND d.user_id=' . $_SESSION['user_id'] . '
	WHERE access.type="sets" AND access.user_id=' . $access_user . '
	GROUP BY s.id ORDER BY d.user_id DESC, s.id DESC');
	
$search = new input('search', 'search');

$owners = new myQuery('SELECT researcher.user_id as user_id, 
	CONCAT(lastname, ", ", initials) as name 
	FROM researcher 
	LEFT JOIN access USING (user_id)
	WHERE access.type="sets" AND access.user_id IS NOT NULL 
	ORDER BY lastname, initials');
$ownerlist = array('all' => 'All');
foreach ($owners->get_assoc() as $o) {
	$ownerlist[$o['user_id']] = $o['name'];
}
$owner = new select('owner', 'owner', $access_user);
$owner->set_options($ownerlist);
$owner->set_null(false);
$owner->set_eventHandlers(array('onchange' => 'setOwner(this.value)'));
	
/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

// search box
echo '<div class="searchline toolbar">Owner: ';
echo $owner->get_element();
echo 'Search: ';
echo $search->get_element();
echo '<button id="new-set">New set</button>';

echo $my->get_result_as_table(true, true);

?>

<div id="help" title="Set Finder Help">
	<ul>
		<li>Type into the search box to narrow down your list. It searches the ID number, name, notes and list of items in the set, so you can type in the name of a table to find sets that include that item (e.g., "exp_72", "quest_130", "econ_2" or "set_1").</li>
		<li>Click on a column title to sort by that column.</li>
		<li>Click on the ID of a set to view or edit it.</li>
		<li>Click on the circle next to a set to save it to your favourites list (on the <a href="/res/">Researchers</a> page.</li>
		<li>Click on the "New set" button at the top to start creating a new set.</li>
	</ul>
	<p>If you are a admin, you can click on the status to see a drop-down menu to change the status of any item.</p>
</div>

<!--**************************************************-->
<!-- !Javascripts for this page -->
<!--**************************************************-->

<script>
	$j(function() {
		$j('#new-set').button().click( function() { window.location.href='/res/set/builder'; });
		
		$j('#search').keyup( function() { narrowTable('table.query tbody', this.value); } );
		
		dashboard_checkboxes('set'); // function defined in myfunctions.js
		
		<?= $status_changer ?> // function defined in myfunctions.js
	});
	
	function setOwner(owner) {
		window.location.href = "./?owner=" + owner;
	}
</script>

<?php

$page->displayFooter();

?>