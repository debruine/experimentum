<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

// clear sets so you don't get stuck
unset($_SESSION['set_list']);
unset($_SESSION['set_item_number']);
unset($_SESSION['project']);
unset($_SESSION['project_id']);
unset($_SESSION['session_id']);

/****************************************************/
/* !Display Page */
/***************************************************/   

$title = '';

$styles = array();

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();


?>

<p>Experimentum is an online platform for psychology studies.</p>

<p>Try the <a href="project?test">Test Studies</a></p>

<?php

$page->displayFooter();

?>