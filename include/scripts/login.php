<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

$return = array(
    'login' => false,
    'url' => '/',
    'error' => ''
);

$clean = my_clean($_POST);
$clean2 = my_clean($_GET);
if (empty($clean)) $clean = array();
$username = ifEmpty($clean['username']);
$password = ifEmpty($clean['password']);
$user_id = ifEmpty(intval($clean2['u']));
$passcode = ifEmpty($clean2['p']);

if (!empty($user_id) && !empty($passcode)) {
    // auto-login
    $query = new myQuery("SELECT user_id FROM user  
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
        if (array_key_exists('url', $clean2)) {
            $newpage = urldecode($clean2['url']);
        } else if (array_key_exists('exp', $clean2)) {
            $newpage = '/exp?id=' . $clean2['exp'];
        } else if (array_key_exists('quest', $clean2)) {
            $newpage = '/quest?id=' . $clean2['quest'];
        } else if (array_key_exists('set', $clean)) {
            $newpage = '/include/scripts/set?id=' . $clean2['set'];
        } else if (array_key_exists('project', $clean2)) {
            $newpage = '/project?' . $clean2['project'];
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