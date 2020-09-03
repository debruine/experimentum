<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';	

$q = new myQuery("SELECT * FROM credit");
echo $q->get_result_as_table();

?>