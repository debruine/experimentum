<?php
    // update to record to long format exp_data table
    
	require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
	auth(1);

	$id = intval($_POST['id']);
	$order = $_POST['order'];
	$response = $_POST['response'];
	$side = $_POST['side'];
	$rt = $_POST['rt'];
	$starttime = $_POST['starttime'];
	$version = intval($_POST['version']);
	$exptype = $_POST['exptype'];
	
	$attribute_order = '(id, user_id, ';
	$attribute_value = "(NULL, {$_SESSION['user_id']}, ";
		
	$n = count($response);
	if ($exptype == 'motivation') {
		// is a motivation type experiment
		foreach ($order as $n => $trial) {
			if ($n > 0) {
				$attribute_order .= "up$trial, down$trial, rt$trial, side$trial, order$trial, ";
				$attribute_value .= sprintf("%s, %s, %s, %s, %s, ",
					ifEmpty($response[$trial]['up'], 'NULL', true),
					ifEmpty($response[$trial]['down'], 'NULL', true),
					ifEmpty($rt[$trial], 'NULL', true),
					ifEmpty($side[$trial], 'NULL', true),
					$n
				);
			}
		}
	} else if ($exptype == 'sort') {
		// is a sort type experiment
		foreach ($order as $n => $trial) {
			if ($n > 0) {
				list($moves, $responses) = explode(":", $response[$trial]);
				$responses = explode(";", $responses);
				$images_per_trial = count($responses);
				
				// set up side
				$s = "'" . implode($side[$trial], ',') . "'";
				
				$attribute_order .= "moves$trial, rt$trial, side$trial, order$trial, ";
				$attribute_value .= sprintf("%s, %s, %s, %s, ",
					ifEmpty($moves, 'NULL', true),
					ifEmpty($rt[$trial], 'NULL', true),
					ifEmpty($s, 'NULL', true),
					$n
				);
				
				foreach ($responses as $i => $r) {
					$n = $i + 1;
					$attribute_order .= "t{$trial}_{$n}, ";
					$attribute_value .= ifEmpty($r, 'NULL', true) . ", ";
				}
			}
		}
	} else {
		// all other types
		
		foreach ($order as $n => $trial) {
			if ($n > 0) {
				if ($exptype == 'xafc') { $side[$trial] = "'" . implode($side[$trial], ',') . "'"; }
			
				$attribute_order .= "t$trial, rt$trial, side$trial, order$trial, ";
				$attribute_value .= sprintf("%s, %s, %s, %s, ",
					ifEmpty($response[$trial], 'NULL', true),
					ifEmpty($rt[$trial], 'NULL', true),
					ifEmpty($side[$trial], 'NULL', true),
					$n
				);
			}
		}
	}
	
	if ($version > 0) {
		$attribute_order .= 'version, ';
		$attribute_value .= "$version, ";
	}
	
	$attribute_order .= 'starttime, endtime)';
	$endtime = date('Y-m-d H:i:s');
	$attribute_value .= "'$starttime', '$endtime')";
	
	$query = new myQuery('INSERT INTO exp_' . $id . ' ' . $attribute_order . ' VALUES ' . $attribute_value);
	
	$exp = new myQuery('SELECT MAX(version) as v FROM versions WHERE exp_id=' . $id);
	$maxversion = $exp->get_one();
	
	if ($maxversion > 0 && $version == 0) {
		$version = rand(1, $maxversion);
		echo '/exp/slideshow?id=' . $id . '&v=' . $version; 
	} else {
		echo '/feedback/fb?type=exp&id=' . $id;
	}
	
	exit;
?>