<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

$title = array(
	'/res/' => loc('Researchers'),
	'/res/stimuli/' => loc('Stimuli')
);

$styles = array(
	'.bigbuttons li a.search' 	=> 'background-image: url("/images/linearicons/magnifier?c=FFF");',
	'.bigbuttons li a.icons' 	=> 'background-image: url("/images/linearicons/diamond?c=FFF");',
	'.bigbuttons li a.browse' 	=> 'background-image: url("/images/linearicons/eye?c=FFF");',
	'.bigbuttons li a.upload' 	=> 'background-image: url("/images/linearicons/download?c=FFF");',
);

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();

$links = array();

if (in_array($_SESSION['status'], array('res', 'admin'))) {
	//$links['search'] = 'Search';
	$links['browse'] = 'Browse';
	$links['upload'] = 'Upload';
}

$links['icons'] = 'Icons';

$classes = array(
	'search' => 'search',
	'browse' => 'browse',
	'upload' => 'upload',
	'icons' => 'icons'
);

echo linkList($links, 'bigbuttons', 'ul', $classes);

$page->displayFooter();

?>