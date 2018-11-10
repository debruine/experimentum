<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';	

function expCheck($id) {
	$q = new myQuery('SELECT id, COUNT(*) as trials, exp.name, random_stim,
						sex, lower_age, upper_age, subtype, design
						FROM exp 
						LEFT JOIN trial ON exp.id=exp_id
						WHERE exp_id=' . $id);
	$q->execute_query();
	$qL = $q->get_assoc(0);	

	$tlist = range(1, $qL['trials']);
	$allT = ($qL['random_stim']<$qL['trials']) ? 'endtime' : 't' . implode('+t', $tlist);
	
	$userid = validID($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;	
			
	$e = new myQuery("SELECT COUNT(*) as total_people, 
						COUNT(DISTINCT IF(user_id={$userid}, id, NULL)) as me_done
						FROM exp_{$id}
						WHERE {$allT} IS NOT NULL
						  AND UNIX_TIMESTAMP(endtime)-UNIX_TIMESTAMP(starttime) > 0 
						  AND UNIX_TIMESTAMP(endtime)-UNIX_TIMESTAMP(starttime) < {$qL['trials']} * 60
						GROUP BY NULL");
	$expInfo = $e->get_assoc(0);
						
	// Determine whether to display this experiment
	$display = true;
	
	// check if participant has done experiment
	$done = ($expInfo['me_done'] > 0) ? true : false;
	
	if ($display) {
		// calculate median length
		$time = new myQuery("SELECT t1.val as median_val FROM (
					SELECT @rownum:=@rownum+1 as row_number, (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime)) as val
					  FROM exp_{$id} AS d,  (SELECT @rownum:=0) r
					  WHERE (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))>0 AND (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))<60*60
					  ORDER BY (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))
					) as t1, 
					(
					  SELECT count(*) as total_rows
					  FROM exp_{$id} AS d
					  WHERE (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))>0 AND (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))<60*60
					) as t2
					WHERE t1.row_number=floor(1*total_rows/2)+1;", true);
		$median = $time->get_one();
	
		return sprintf('%s;%s;%s',
			$done ? 1 : 0,
			number_format($median/60,1),
			number_format($expInfo['total_people'])
		);
	} else {
		return 'nodisplay';
	}
}

function questCheck($id) {
	// get info for this questionnaire
	
	$userid = validID($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
	
	$e = new myQuery("SELECT COUNT(*) as total_people, 
						COUNT(DISTINCT IF(user_id={$userid}, id, NULL)) as me_done
						FROM quest_{$id}
						GROUP BY NULL");
	$expInfo = $e->get_assoc(0);
		
	// check if participant has done experiment
	$done = ($expInfo['me_done'] > 0) ? true : false;
	
	// calculate median length
	$time = new myQuery("SELECT t1.val as median_val FROM (
				SELECT @rownum:=@rownum+1 as row_number, (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime)) as val
				  FROM quest_{$id} AS d,  (SELECT @rownum:=0) r
				  WHERE (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))>0 AND (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))<60*60
				  ORDER BY (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))
				) as t1, 
				(
				  SELECT count(*) as total_rows
				  FROM quest_{$id} AS d
				  WHERE (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))>0 AND (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))<60*60
				) as t2
				WHERE t1.row_number=floor(1*total_rows/2)+1;", true);
	$median = $time->get_one();
	
	return sprintf('%s;%s;%s',
		$done ? 1 : 0,
		number_format($median/60,1),
		number_format($expInfo['total_people'])
	);
}

function setCheck($id) {
	$q = new myQuery("SELECT item_type, item_id FROM set_items WHERE set_id={$id}");
	$setItems = $q->get_assoc();
	
	$display = array();
	foreach($setItems as $n => $item) {
		if ($item['item_type'] == 'exp') {
			$done = expCheck($item['item_id']);
			$display[] = ('nodisplay' == $done) ? 0 : substr($done, 0, 1);
		} else if ($item['item_type'] == 'quest') {
			$done = questCheck($item['item_id']);
			$display[] = ('nodisplay' == $done) ? 0 : substr($done, 0, 1);
		} else if ($item['item_type'] == 'set') {
			$display[] = setCheck($item['item_id']);
		}
	}
	
	$display_sum = array_sum($display);
	
	$q = new myQuery("SELECT type FROM sets WHERE id={$id}");
	$setType = $q->get_one();
	
	if (substr($setType, 0, 3) == 'one') {
		return ($display_sum > 0) ? 1 : 0;
	} else {
		return ($display_sum < count($display)) ? 0 : 1;
	}
}

/****************************************************
 * AJAX response
 ***************************************************/

if (array_key_exists('quest', $_GET) && validID($_GET['id'])) {				
	echo questCheck($_GET['id']);
	exit;
} else if (array_key_exists('exp', $_GET) && validID($_GET['id'])) {
	echo expCheck($_GET['id']);
	exit;
} else if (array_key_exists('set', $_GET) && validID($_GET['id'])) {
	echo setCheck($_GET['id']) . ';NULL;NULL';
	exit;
}


?>