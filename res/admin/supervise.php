<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('res','admin'), "/res/");

/****************************************************/
/* !Display Page */
/***************************************************/

$title = array(
    '/res/' => loc('Researchers'),
    '/res/admin/' => loc('Admin'),
    '' => loc('Supervision')
);

/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);

$page->displayBody();

$query = new myQuery("SELECT CONCAT(zor.lastname, ', ', zor.firstname) as Supervisor,
                             CONCAT(zee.lastname, ', ', zee.firstname) as Supervisee
                        FROM supervise
                   LEFT JOIN res AS zor ON (zor.user_id = supervisor_id)
                   LEFT JOIN res AS zee ON (zee.user_id = supervisee_id)
                   ORDER BY Supervisor, Supervisee");
                          
echo $query->get_result_as_table(true, true);

?>

<!--*************************************************
 * Javascripts for this page
 *************************************************-->


<script src="/include/js/sorttable.js"></script>

<?php

$page->displayFooter();

?>