<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

// record logout time
$query = new myQuery("UPDATE login SET logoutime=NOW() 
	WHERE user_id='{$_SESSION['user_id']}' 
	AND logoutime IS NULL 
	ORDER BY id DESC LIMIT 1");
	
//remove PHPSESSID from browser
if ( isset( $_COOKIE[session_name()] ) ) {
    setcookie( session_name(), "", time()-3600, "/" );
}

$_SESSION = array(); //clear session from globals
session_destroy(); //clear session from disk
	
?>