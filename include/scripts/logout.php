<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

// record logout time
$query = new myQuery("UPDATE login SET logoutime=NOW() 
	WHERE user_id='{$_SESSION['user_id']}' 
	AND logoutime IS NULL 
	ORDER BY id DESC LIMIT 1");
	
session_destroy();
	
?>