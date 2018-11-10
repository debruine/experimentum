<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

$return = array(
    'login' => false,
    'url' => '/',
    'error' => ''
);

$clean = my_clean($_POST);
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
        $return['error'] = loc("Sorry, something went wrong.");
    } else {
        // set up user object and login
        $user = new user($user_id);
        $user->login_table();
        $user->set_session_variables();
        
        // send to new page
        $newpage = '/';
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
            $return['login'] = 'login';
        }
    }
} elseif (!empty($username) && !empty($password)) {
    
    // login using username and password
    $user = new user();
    $user->set_username($username);
    $login_status = $user->login($password);
    
    if ($login_status == "login") {
        $return['login'] = "login";
    } else {
        $return['error'] = $login_status;
    }
    
    if (array_key_exists('return_to', $_SESSION)) {
        $return['url'] = $_SESSION['return_to'];
    } else if (in_array($user->get_status(), $RES_STATUS)) {
        $return['url'] = "/res/";
    }
} elseif (empty($username) || empty($password)) {
    // prompt for username or password if one is missing
    $return['error'] = loc("Please fill in both the username and the password");
} else {
    // no valid information
    $return['error'] = loc("Sorry, something went wrong.");
}

scriptReturn($return);

?>