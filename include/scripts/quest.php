<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

$clean = $_POST;
if (empty($clean)) $clean = array();

print_r($clean);

exit();

$query = sprintf(
	"INSERT INTO quest_%d (user_id, starttime, endtime) 
	VALUES ('%s', '%s', NOW())",
	$clean['quest_id'],
	$_SESSION['user_id'],
	$clean['starttime']
);
($result = @mysql_query($query, $db)) || myerror();

session_write_close();


echo $msg;

?>