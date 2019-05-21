<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

// set up user object and login
$user = new user();
$user->set_username('Guest');
$user->set_status('guest');
$user->register("");
$user->login_table();
$user->get_info();
$user->set_session_variables();
#print_r($_SESSION);
echo "login";
exit;

// send to new page
$newpage = '';
if (array_key_exists('url', $clean)) {
	$newpage = urldecode($clean['url']);
} else if (array_key_exists('exp', $clean)) {
	$newpage = '/exp?id=' . $clean['exp'];
} else if (array_key_exists('quest', $clean)) {
	$newpage = '/quest?id=' . $clean['quest'];
} else if (array_key_exists('set', $clean)) {
	$newpage = '/include/scripts/set?id=' . $clean['set'];
} else if (array_key_exists('project', $clean)) {
	$newpage = '/project?' . $clean['project'];
}

if (!empty($newpage)) {
	header('Location: ' . $newpage);
	exit;
} else {
	echo 'login';
	exit;
}
