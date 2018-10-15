<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(1);

// set up experiment
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/exp.php';

$exp_id=$_GET['id'];
$version=$_GET['v'];

$expC = new expChooser($exp_id, $version);

if (!$expC->check_exists()) { header('Location: /'); exit; }

if (!$expC->check_eligible()) { 
	if (in_array($_SESSION['status'], array('student', 'researcher', 'admin'))) {
		$ineligible = "<p class='ui-state-error'>You would not be able to see this study because of your age or sex if you were a non-researcher.</p>";
	} else {
		header('Location: /fb?ineligible&type=exp&id=' . $exp_id); exit;
	}
}

/****************************************************/
/* !Display Page */
/***************************************************/

$title = array();

$styles = array(
);

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

$exp = $expC->get_exp();
echo $exp->get_experiment();

echo $ineligible;

$page->displayFooter();

?>