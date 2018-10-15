<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin'), "/res/");

$title = array(
	'/res/' => loc('Researchers'),
	'/res/admin/' => loc('Admin')
);

$styles = array(
    '.bigbuttons li a.participant' 	=> 'background-image: url("/images/linearicons/users?c=FFF");',
	'.bigbuttons li a.debug' => 'background-image: url("/images/linearicons/bug?c=FFF");',
	'.bigbuttons li a.access' => 'background-image: url("/images/linearicons/list?c=FFF");',
	'.bigbuttons li a.frontpage' => 'background-image: url("/images/linearicons/list?c=FFF");',
	'.bigbuttons li a.status' => 'background-image: url("/images/linearicons/list?c=FFF");',
	'.bigbuttons li a.stimuli' => 'background-image: url("/images/linearicons/picture?c=FFF");',
	'.bigbuttons li a.yoke' => 'background-image: url("/images/linearicons/list?c=FFF");',
	'.bigbuttons li a.merge' => 'background-image: url("/images/linearicons/list?c=FFF");',
	'.bigbuttons li a.functions' => 'background-image: url("/images/linearicons/list?c=FFF");',
	'.bigbuttons li a.processlist' => 'background-image: url("/images/linearicons/list?c=FFF");',
);

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();

$links = array(
	'/res/project/builder?id=1' => 'Front Page',
    'participant' => 'Participant Data',
	'stimuli' => 'Update Stimuli',
	//'status' => 'Change Status',
	'merge' => 'Merge',
	'access' => 'Access',
	'/debug' => 'Debug',
	'yoke' => 'Yoke',
	'functions' => 'Periodic Functions',
	'processlist' => 'Active Queries'
);

$classes = array(
    'participant' => 'participant',
	'/res/project/builder?id=1' => 'frontpage',
	'status' => 'status',
	'merge' => 'merge',
	'stimuli' => 'stimuli',
	'access' => 'access',
	'/debug' => 'debug',
	'upload' => 'upload',
	'yoke' => 'yoke',
	'functions' => 'functions',
	'processlist' => 'processlist'
);

echo linkList($links, 'bigbuttons', 'ul', $classes);

$page->displayFooter();

?>