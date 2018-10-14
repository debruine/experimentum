<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

$title = loc('Page Not Found');
$page = new page($title);
$page->set_logo(true);
$page->set_menu(true);

$page->displayHead();
$page->displayBody();

echo tag("Sorry, we can't find the page you're looking for.");

$page->displayFooter();

?>