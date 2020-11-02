<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin', 'super'), "/res/");



$title = array(
    '/res/' => loc('Researchers'),
    '/res/admin/' => loc('Admin'),
    '' => loc('Downloads')
);

$styles = array();


$q = new myQuery("SELECT type, id, 
                         COUNT(*) as downloads,
                         GROUP_CONCAT(DISTINCT(user_id)) as user_ids
               FROM downloads 
           GROUP BY type, id
           ORDER BY type, id");



/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);

$page->displayBody();

echo $q->get_result_as_table(true, true);

?>


<!--*************************************************
 * Javascripts for this page
 *************************************************-->

<?php

$page->displayFooter();

?>