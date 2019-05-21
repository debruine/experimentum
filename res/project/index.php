<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
auth($RES_STATUS);

$status_changer = ($_SESSION['status'] == 'admin') ? "statusChanger(5,'project');" : "";

$title = array(
	'/res/' => 'Researchers',
	'/res/project/' => 'Projects'
);

$styles = array(
	
);

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

$my = new myQuery('SELECT CONCAT("<span class=\'fav", 
        IF(d.id IS NOT NULL, " heart", ""), 
        "\' id=\'dash", p.id, "\'>",
        IF(d.id IS NOT NULL, "+", "-"), 
        "</span>") as "Favs",CONCAT("<a href=\'info?id=", p.id, "\'>", p.id, "</a>") as "ID", 
	CONCAT("<a href=\'info?id=", p.id, "\'>", p.res_name, "</a>") AS "Name",
	CONCAT("<a href=\'/project?", p.url, "\'>https://psa.psy.gla.ac.uk/project?", p.url, "</a>") AS "URL",
	COUNT(DISTINCT session.id) AS Sessions,
	CONCAT("<span class=\'labnotes\'>", labnotes, "</span>") as "Labnotes", 
	status, 
	DATE_FORMAT(create_date, "%Y-%m-%d") as "Date Created"
	FROM project as p
	LEFT JOIN access USING (id) 
	LEFT JOIN session ON project_id = p.id
	LEFT JOIN dashboard as d ON d.id = p.id AND d.type="project" AND d.user_id=' . $_SESSION['user_id'] . '
	WHERE access.type="project" AND access.user_id IN(' . $access_user . ')
	GROUP BY p.id ORDER BY d.user_id DESC, p.id DESC');
	
$search = new input('search', 'search');

$user_id = $_SESSION['user_id'];
if ($_SESSION['status'] == 'admin') {
    $ownerquery = "SELECT res.user_id as user_id, 
        CONCAT(lastname, ', ', firstname) as name 
        FROM res 
        LEFT JOIN access USING (user_id)
        WHERE (access.type='project' AND access.user_id IS NOT NULL) 
          OR res.user_id={$user_id} 
        ORDER BY lastname, firstname";
} else if ($_SESSION['status'] == 'res') {
    $ownerquery = "SELECT res.user_id as user_id, 
        CONCAT(lastname, ', ', firstname) as name 
        FROM res 
        LEFT JOIN access USING (user_id)
        LEFT JOIN supervise ON res.user_id=supervisee_id
        WHERE (access.type='project' AND access.user_id IS NOT NULL 
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
    $owner->set_eventHandlers(array('onchange' => 'setOwner(this.value)'));
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
echo '<button id="new_project">New project</button></div>';

echo $my->get_result_as_table(true, true);

?>

<div id="help" title="Project Finder Help">
	<p>Projects are subpages of FaceResearch.org where you can group experiments and create a start page for lab research or special populations online.</p>
	<ul>	
		<li>Type into the search box to narrow down your list. It searches the ID number, name and notes.</li>
		<li>Click on a column title to sort by that column.</li>
		<li>Click on the ID of a project to view or edit it.</li>
		<li>Click on the circle next to a project to save it to your favourites list (on the <a href="/res/">Researchers</a> page.</li>
		<li>Click on the "New project" button at the top to start creating a new project.</li>
	</ul>
	<p>If you are a admin, you can click on the status to see a drop-down menu to change the status of any item.</p>
</div>

<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script>
	
	$(function() {
		$( "#new_project" ).button().click(function() { window.location.href = 'builder'; });
		
		$('#search').keyup( function() { narrowTable('table.query tbody', this.value); } );
		
		dashboard_checkboxes('project'); // function defined in myfunctions.js
		
		<?= $status_changer ?> // function defined in myfunctions.js
	});
	
	function setOwner(owner) {
		window.location.href = "./?owner=" + owner;
	}
</script>

<?php

$page->displayFooter();

?>