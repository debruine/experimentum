<?php

// get all trials or all questions from a table

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

$types = array('exp', 'quest', 'econ'); // acceptable types

if (validID($_GET['id']) && in_array($_GET['type'], $types)) {
	$query = new myQuery('DESC ' . $_GET['type'] . '_' . $_GET['id']);
	$fields = $query->get_assoc(false, false, 'Field');
	
	$vars = array('exp' => 't', 'quest' => 'q', 'econ' => 't'); //dv names for each type
	
	foreach ($fields as $i => $f) {
		$n = str_replace($vars[$_GET['type']], '', $f);
		if (!is_numeric($n)) unset($fields[$i]);
	}
	
	if (!empty($_GET['test'])) {
		$delimiter = $_GET['test'] . ') + (';
		echo '(' . implode($delimiter, $fields) . $_GET['test'] . ')';
	} else {
		echo implode(' + ', $fields);
	}
}

?>