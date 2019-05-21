<?php
	require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
	
	 $chart_types = array(
		'column' => 'column',
		'line' => 'line', 
		'spline' => 'spline',
		'pie' => 'pie', 
		'area' => 'area', 
		'areaspline' => 'areaspline', 
		'bar' => 'horizontal bar', 
		'scatter' => 'scatter',
	);
	
	// get data from a query and create the json for a Highchart
	
	if (validID($_GET['id'])) {
		$query = new myQuery('SELECT * FROM charts WHERE id=' . $_GET['id']);
		$chart_data = $query->get_one_array();
		
		$query_text = $chart_data['query_text'];
		/*$chartTitle = $chart_data['title'];
		$chartType = check_null($chart_data['chart_type'],  $chart_types);
		$xLabel = $chart_data['xlabel'];
		$yLabel = $chart_data['ylabel'];
		$xmin = is_numeric($chart_data['xmin']) ? $chart_data['xmin'] : null;
		$xmax = is_numeric($chart_data['xmax']) ? $chart_data['xmax'] : null;
		$ymin = is_numeric($chart_data['ymin']) ? $chart_data['ymin'] : null;
		$ymax = is_numeric($chart_data['ymax']) ? $chart_data['ymax'] : null;
		$yTicks = is_numeric($chart_data['yticks']) ? $chart_data['yticks'] : null;
		$container = $chart_data['container'];*/
	}
	
	// override setting above with POST data
	if (!empty($_POST['query_text'])) $query_text = $_POST['query_text'];
	
	$mydata = new myQuery($query_text, true);
	$data = $mydata->get_assoc();
	
	// use setting in query if not set above
	if (!empty($data)) {
		// set table data from first 
		$chartTitle	= ifEmpty($data[0]['title'], 'Title Missing');
		$chartType	= check_null($data[0]['chart_type'],  $chart_types);
		$xLabel		= ifEmpty($data[0]['xlabel'], 'X-Label Missing');
		$yLabel		= ifEmpty($data[0]['ylabel'], 'Y-Label Missing');
		$xmin		= is_numeric($data[0]['xmin']) ? $data[0]['xmin'] : null;
		$xmax		= is_numeric($data[0]['xmax']) ? $data[0]['xmax'] : null;
		$ymin		= is_numeric($data[0]['ymin']) ? $data[0]['ymin'] : null;
		$ymax		= is_numeric($data[0]['ymax']) ? $data[0]['ymax'] : null;
		$yTicks		= is_numeric($data[0]['yticks']) ? $data[0]['yticks'] : null;
		$xTicks		= is_numeric($data[0]['xticks']) ? $data[0]['xticks'] : null;
		$container	= ifEmpty($data[0]['container'], '#graph_container');
		$reverse	= ifEmpty($data[0]['reverse'], false);
	}
	
	$xcategories = array();
	$theData = array();
	$i = 0;
	
	foreach ($data as $d) {
		$theData[$i]['name'] = $d['xcat'];
		foreach ($d as $key => $value) {
			switch ($key) {
				case 'title':
				case 'chart_type':
				case 'xlabel':
				case 'ylabel':
				case 'xmin':
				case 'xmax':
				case 'ymin':
				case 'ymax':
				case 'yticks':
				case 'xticks':
				case 'container':
				case 'xcat':
				case 'reverse':
					break;
				case 'x':
					$x[] = $key;
					break;
				default:
					$theData[$i]['data'][] = array('name' => $key, 'y' => $value);
					$xcategories[$key] = $key;
			}	
		}
		$i++;
	}
	
	if ($reverse) {
		// reverse rows and columns
		
		// get 
		$i=0;
		foreach ($xcategories as $c ) {
			$revData[$i]['name'] = $c;
			$i++;
		}
		
		foreach ( $theData as $d) {
			$revCategories[$d['name']] = $d['name'];
			
			foreach($d['data'] as $i => $datapoint) {
			
				$namevar = (is_numeric($d['name'])) ? 'x' : 'name';
			
				$revData[$i]['data'][] = array(
					$namevar => $d['name'],
					'y' => $datapoint['y']
				);
			}
		}
		
		// replace old data with reversed
		$theData = $revData;
		$xcategories = $revCategories;	
	}
	
	// create list of xcategories and make null if all numeric
	$xCats = array_keys($xcategories);
	$all_numeric = true;
	foreach ($xCats as $v) { if (!is_numeric($v)) $all_numeric = false; }
	if ($all_numeric) $xCats = null;
	
	$json = array(
		'chart' => array(
			'renderTo' =>$container,
			'type' => $chartType,
		),
		'title' => array( 'text' => $chartTitle ),
		'series' => $theData,
		'xAxis' => array(
			'title' => array('text' => $xLabel ),
			'min' => $xmin,
			'max' => $xmax,
			'tickInterval' => $xTicks,
			'categories' => $xCats,
		),
		'yAxis' => array(
			'title' => array( 'text' => $yLabel ),
			'min' => $ymin,
			'max' => $ymax,
			'tickInterval' => $yTicks,
		),
		'legend' => array(
			'enabled' => ((count($theData) == 1) ? false : true)
		)
	);
		
	echo json_encode($json, JSON_NUMERIC_CHECK);
	
	exit;
?>

