<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';	

/****************************************************/
/* !Display Page */
/***************************************************/

$title = loc('Feedback');
$page = new page($title);
$page->set_logo(true);
$page->set_menu(true);

$page->displayHead();
$page->displayBody();

$page->displayFooter();

?>