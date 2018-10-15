<?php
	require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
	auth(1);

	$id = intval($_POST['id']);
	$trial = intval($_POST['trial']);
	$order = $_POST['order'];
	$response = $_POST['response'];
	$side = $_POST['side'];
	$rt = $_POST['rt'];
	$starttime = $_POST['starttime'];
	$version = intval($_POST['version']);
	$exptype = $_POST['exptype'];
	
	$attribute_order = '(user_id, trial, ';
	$attribute_value = "({$_SESSION['user_id']}, $trial, ";
		
	if ($exptype == 'motivation') {
		// is a motivation type experiment
		$attribute_order .= "up, down, ";
		$attribute_value .= sprintf("%s, %s, ",
			ifEmpty($response['up'], 'NULL', true),
			ifEmpty($response['down'], 'NULL', true)
		);
	} else if ($exptype == 'sort') {
		// is a sort type experiment
		list($moves, $responses) = explode(":", $response);
		$responses = explode(";", $responses);
		$images_per_trial = count($responses);
		
		// set up side
		$side = "'" . implode($side, ',') . "'";
		
		$attribute_order .= "moves, ";
		$attribute_value .= sprintf("%s, ",
			ifEmpty($moves, 'NULL', true)
		);
		
		foreach ($responses as $i => $r) {
			$n = $i + 1;
			$attribute_order .= "dv_{$n}, ";
			$attribute_value .= ifEmpty($r, 'NULL', true) . ", ";
		}
	} else {
		// all other types
		if ($exptype == 'xafc') { $side = "'" . implode($side, ',') . "'"; }
	
		$attribute_order .= "dv, ";
		$attribute_value .= sprintf("%s, ",
			ifEmpty($response, 'NULL', true)
		);
	}
	
	if ($version > 0) {
		$attribute_order .= 'version, ';
		$attribute_value .= "$version, ";
	}
	
	$attribute_order .= 'rt, side, `order`, dt)';
	$attribute_value .= sprintf("%s, %s, %s, '%s')",
		ifEmpty($rt, 'NULL', true),
		ifEmpty($side, 'NULL', true),
		ifEmpty($order, 'NULL', true),
		date('Y-m-d H:i:s')
	);
	
	$query = new myQuery('INSERT INTO exp_' . $id . ' ' . $attribute_order . ' VALUES ' . $attribute_value);
	
	
	
	if (array_key_exists('done', $_POST)) {
		$exp = new myQuery('SELECT MAX(version) as v FROM versions WHERE exp_id=' . $id);
		$maxversion = $exp->get_one();
		
		if ($maxversion > 0 && $version == 0) {
			$version = rand(1, $maxversion);
			echo '/slideshow?id=' . $id . '&v=' . $version; 
		} else {
			echo '/fb?type=exp&id=' . $id;
		}
	} else {
		echo $query->get_query();
	}
	
	exit;
?>