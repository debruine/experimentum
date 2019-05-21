<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin', 'res'), "/res/");

$title = array(
	'/res/' => loc('Researchers'),
	'/res/admin/' => loc('Admin')
);

$styles = array(
    '.bigbuttons li a.participant' 	=> 'background-image: url("/images/linearicons/users?c=FFF");',
	'.bigbuttons li a.debug' => 'background-image: url("/images/linearicons/bug?c=FFF");',
	'.bigbuttons li a.access' => 'background-image: url("/images/linearicons/list?c=FFF");',
	'.bigbuttons li a.status' => 'background-image: url("/images/linearicons/list?c=FFF");',
	'.bigbuttons li a.stimuli' => 'background-image: url("/images/linearicons/picture?c=FFF");',
	'.bigbuttons li a.yoke' => 'background-image: url("/images/linearicons/list?c=FFF");',
	'.bigbuttons li a.merge' => 'background-image: url("/images/linearicons/list?c=FFF");',
	'.bigbuttons li a.functions' => 'background-image: url("/images/linearicons/list?c=FFF");',
	'.bigbuttons li a.processlist' => 'background-image: url("/images/linearicons/list?c=FFF");',
	'.bigbuttons li a.usage' => 'background-image: url("/images/linearicons/users?c=FFF");',
	'.bigbuttons li a.supervise' => 'background-image: url("/images/linearicons/0295-group-work?c=FFFFFF");',
);

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();

$links = array(
    'participant' => 'Participant Data',
    	'merge' => 'Merge',
    	'supervise' => 'Supervision',
    'access' => 'Access',
    'stimuli' => 'Update Stimuli',
    'usage' => 'Usage',
	'debug' => 'Debug',
	#'yoke' => 'Yoke',
	#'functions' => 'Periodic Functions',
	'processlist' => 'Active Queries'
);

$classes = array(
    'participant' => 'participant',
	'merge' => 'merge',
	'stimuli' => 'stimuli',
	'supervise' => 'supervise',
	'access' => 'access',
	'debug' => 'debug',
	'upload' => 'upload',
	'yoke' => 'yoke',
	'usage' => 'usage',
	'functions' => 'functions',
	'processlist' => 'processlist'
);

echo linkList($links, 'bigbuttons', 'ul', $classes);

$page->displayFooter();

?>