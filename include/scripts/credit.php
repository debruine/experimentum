<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';	

$q = new myQuery("SELECT * FROM credit");
scriptReturn($q->get_assoc());

?>