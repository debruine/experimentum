<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
auth($RES_STATUS);

$title = array(
	'/res/' => 'Researchers',
	'/res/lab/' => 'Labs'
);

$styles = array(
	
);

/*


    
*/


$my = new myQuery('SELECT 
    CONCAT("<a href=\'builder?code=", p.code, "\'>", p.code, "</a>") AS "Lab Code",
	p.name AS "Lab Name",
	CONCAT("<a href=\'mailto:", p.email, "\'>", p.contact, "</a>") AS "Contact",
	p.language as "Language",
	c.country AS "Country"
	FROM lab as p
	LEFT JOIN countries AS c ON c.id = p.country
	ORDER BY p.code DESC');
	
$search = new input('search', 'search');
	
/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

// search box
echo '<div class="searchline toolbar">';
echo 'Search: ';
echo $search->get_element();
echo '<button id="new_lab">New lab</button></div>';

echo $my->get_result_as_table(true, true);

?>

<div id="help" title="Lab Finder Help">
</div>

<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script>
	
	$(function() {
		$( "#new_lab" ).button().click(function() { window.location.href = 'builder'; });
		
		$('#search').keyup( function() { narrowTable('table.query tbody', this.value); } );
	});
</script>

<?php

$page->displayFooter();

?>