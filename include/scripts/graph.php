<?php
	require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
	require_once DOC_ROOT . '/include/classes/graph.php';

	$query = new myQuery($_POST['query'], true);
	// exit if more than 10 rows
	if ($query->get_num_rows() > 10) {
		echo '<h2>Cannot create more than 10 graphs</h2>', ENDLINE;
		exit;
	}
	
	$attributes = array('title', 'y_lowerlimit','y_upperlimit', 'xlabel', 'ylabel', 'barcolor', 'axis_style',
							'bgcolor', 'xcross', 'width', 'height', 'barborders', 'textcolor', 'caption');

	foreach($query->get_assoc() as $d) {
		if (is_array($d)) {
			$g = new bargraph($d);
			foreach ($attributes as $attribute) {
				if (array_key_exists($attribute, $d)) {
					$func = 'set_' . $attribute;
					$att_value = ('barcolor' == $attribute) ? explode(',', $d[$attribute]) : $d[$attribute];
					$g->$func($att_value);
					unset($d[$attribute]);
				}
			}
			$g->set_variables($d); // reset variables now that text values are excluded
	
			echo $g->drawGraph();
		}
	}
	exit;
?>