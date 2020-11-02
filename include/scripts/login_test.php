<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

$return = array(
    'login' => false,
    'error' => ''
);

if (array_key_exists('user_id', $_SESSION)) {
    // record logout time but don't clear php session
    $query = new myQuery("UPDATE login SET logoutime=NOW() 
    	WHERE user_id='{$_SESSION['user_id']}' 
        	AND logoutime IS NULL 
        	ORDER BY id DESC LIMIT 1");
    
    $_SESSION = array(); //clear session from globals
}

// set up user object and login
$user = new user();
$user->set_username('Test');
$user->set_status('test');
$user->set_sex($_POST['sex']);
if ($_POST['age']) {
    $year = date('Y') - intval($_POST['age']);
    $user->set_birthday($year, date('m'), date('d'));
}
$user->register("");
$user->login_table();
$user->get_info();
$user->set_session_variables();

$return['login'] = true;
$return['status'] = $user->get_status();
$return['sex'] = $user->get_sex();
$return['age'] = $user->get_age();

scriptReturn($return);

?>
