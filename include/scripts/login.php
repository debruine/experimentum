<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

$clean = my_clean($_GET);
if (empty($clean)) $clean = array();
$username = ifEmpty($clean['username']);
$password = ifEmpty($clean['password']);
$user_id = ifEmpty($clean['u']);
$passcode = ifEmpty($clean['p']);

if (!empty($user_id) && !empty($passcode)) {
	// auto-login
	$query = new myQuery("SELECT '' FROM user  
		WHERE user.user_id='$user_id' AND LEFT(MD5(regdate),10)='$passcode'
		LIMIT 1");
	
	// exit if login is invalid
	if (0 == $query->get_num_rows()) { 
		echo "error:" . loc("Sorry, something went wrong.");
		exit;
	} else {
		// set up user object and login
		$user = new user($user_id);
		$user->login_table();
		$user->set_session_variables();
		
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
			//print_r($_SESSION);
			//echo 'newpage:' . $newpage;
			exit;
		} else {
			echo 'login';
			exit;
		}
	}
} elseif (!empty($username) && !empty($password)) {
	
	// login using username and password
	$user = new user();
	$user->set_username($username);
	$login_status = $user->login($password);
	
	echo $login_status;
	exit;
} elseif (empty($username) || empty($password)) {
	// prompt for username or password if one is missing
	echo "empty:" . loc("Please fill in both the username and the password");
	exit;
} else {
	// no valid information
	echo "error:" . loc("Sorry, something went wrong.");
	exit;
}

?>