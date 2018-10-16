<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
auth(array('admin'), "/res/");

$title = array(
	'/res/' => 'Researchers',
	'/res/data/' => 'Data',
	'' => 'My Queries'
);

$styles = array(
	'.query_text' => 'display:none;',
	'td' => 'text-align: left;',
);

if (MOBILE) {
	$styles['table.query td+td+td+td, table.query th+th+th+th'] = 'display:none;';
	$styles['.searchline'] = 'text-align: left;';
	$styles['table.query'] = 'font-size: 120%;';
}

/****************************************************/
/* !Get Query Data */
/***************************************************/

// set the user whose items to get
$access_user = $_SESSION['user_id'];
if (array_key_exists('owner', $_GET) && $_SESSION['status'] == 'admin') { 
	if ($_GET['owner'] == 'all') {
		$access_user = 'q.user_id';
	} elseif (validID($_GET['owner'])) {
		$access_user = $_GET['owner'];
	}
}

$my = new myQuery('SELECT 
	CONCAT("<input type=\'checkbox\' class=\'fav\' id=\'dash", q.id, "\' ", IF(d.id IS NOT NULL, "checked=\'checked\' ", ""), "/>",
	"<label for=\'dash", q.id, "\'>", IF(d.id IS NOT NULL, "+", "-"),"</label>") as "Favs", 
	CONCAT("<a href=\'index?id=", q.id, "\'>", q.id, "</a>") as "ID", 
	q.name as "Name",
	CONCAT("<span class=\'labnotes\'>", notes, "</span><span class=\'query_text\'>", query_text, "</span>") as "Notes",
	CONCAT("<span class=\'labnotes\'>", lastname, ", ", initials, "</span>") as Owner
	FROM queries as q
	LEFT JOIN researcher USING (user_id)
	LEFT JOIN dashboard as d ON d.id = q.id AND d.type="query" AND d.user_id=' . $_SESSION['user_id'] . '
	WHERE q.user_id=' . $access_user . ' ORDER BY d.user_id DESC, q.id DESC');
	
$search = new input('search', 'search');
$search->set_placeholder('search');

$owners = new myQuery('SELECT researcher.user_id as user_id, 
	CONCAT(lastname, ", ", initials) as name 
	FROM queries
	LEFT JOIN researcher USING (user_id)
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
echo '<div class="searchline toolbar">';
if ($_SESSION['status'] == 'admin') echo 'Owner: ' . $owner->get_element(); 
if (MOBILE) {
	echo '<br />';
} else {
	echo 'Search: ';
}
echo $search->get_element();
echo '<button id="newQuery">New query</button>';

echo $my->get_result_as_table(true, true);

?>

<div id="help" title="Query Finder Help">
	<ul>
		<li>Type into the search box to narrow down your list. It searches the title, notes and full text of the query, so you can type in the name of a table to find queries that use the table (e.g., "exp_72").</li>
		<li>Click on a column title to sort by that column.</li>
		<li>Click on the ID of a query to view or edit it.</li>
		<li>Click on the circle next to a query to save it to your favourites list (on the <a href="/res/">Researchers</a> page.</li>
	</ul>
</div>

<script>
	$(function() {
		// set up main button functions
		$( "#newQuery" ).button().click(function() {
			window.location.href='/res/data/index?id=0';
		});
		
		$('table.query td a').each( function() {
			var row = $(this).closest('tr').find('td+td+td');
			var anchor = $(this).attr('href');
			row.click( function() {
				window.location = anchor;
			});
		});
		
		$('#search').keyup( function() { narrowTable('table.query tbody', this.value); } );
		
		dashboard_checkboxes('query'); // function defined in myfunctions.js
	});
	
	function setOwner(owner) {
		window.location.href = "./querychooser?owner=" + owner;
	}
</script>

<?php

$page->displayFooter();

?>