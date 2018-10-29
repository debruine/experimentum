<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);


$title = array(
	'/res/' => 'Researchers',
	'/res/exp/' => 'Experiments',
	'' => 'Type Chooser'
);

$links = array(
	"builder?exptype=2afc" => "2-Alternative Forced Choice",
	"builder?exptype=jnd" => "8-Button (JND)",
	"builder?exptype=buttons" => "Buttons",
	"builder?exptype=rating" => "Rating",
	'builder?exptype=xafc' => 'X-Alternative Forced Choice',
	'builder?exptype=sort' => 'Sorting',
	'builder?exptype=nback' => 'N-Back',
	'builder?exptype=other' => 'Other'
);
	
/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead();
$page->displayBody();

echo linkList($links, 'bigbuttons');

$page->displayFooter();

?>