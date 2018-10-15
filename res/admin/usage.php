<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin'), "/res/");

/****************************************************
 * AJAX Responses: Get Data
 ***************************************************/
 
if (array_key_exists('show', $_GET)) {
 
	switch ($_GET['interval']) {
		case 'Year';
			$group_by = 'year'; break;
		case 'Quarter';
			$group_by = 'year, quarter';
			break;
		case 'Month';
			$group_by = 'year, month';
			break;
		case 'Week':
			$group_by = 'week';
			break;
		default:
			$group_by = 'year, month, day';
	}
	
	switch ($_GET['past']) {
		case 'Year';
			$past = 365;
			break;
		case 'Quarter';
			$past = 91;
			break;
		case 'Month';
			$past = 30;
			break;
		case 'Week':
			$past = 7;
			break;
		default:
			$past = 3650; // up to 10 years
	}
	
	if ($_GET['show'] == 'Unique Logins') {
		$query = new myQuery('SELECT DAY(logintime) as day,
								MONTH(logintime)-1 as month,
								YEAR(logintime) as year,
								YEARWEEK(logintime) as week,
								CEIL(MONTH(logintime)/4) as quarter,
								COUNT(DISTINCT user_id) as c 
								FROM login 
								WHERE DATEDIFF(NOW(), logintime) < ' . $past . '
								GROUP BY ' . $group_by . ' 
								ORDER BY logintime'); 
	} else if ($_GET['show'] == 'Registrations') {
		$query = new myQuery('SELECT DAY(regdate) as day,
								MONTH(regdate)-1 as month,
								YEAR(regdate) as year,
								YEARWEEK(regdate) as week,
								CEIL(MONTH(regdate)/4) as quarter,
								COUNT(*) as c 
								FROM user 
								WHERE DATEDIFF(NOW(), regdate) < ' . $past . '
								GROUP BY ' . $group_by . '
								ORDER BY regdate'); 
	} else {
		$query = new myQuery('SELECT DAY(logintime) as day,
								MONTH(logintime)-1 as month,
								YEAR(logintime) as year,
								YEARWEEK(logintime) as week,
								CEIL(MONTH(logintime)/4) as quarter,
								COUNT(*) as c 
								FROM login 
								WHERE DATEDIFF(NOW(), logintime) < ' . $past . '
								GROUP BY ' . $group_by . '
								ORDER BY logintime'); 
	}
	
	$data_assoc = $query->get_assoc();
	$data = array();
	foreach($data_assoc as $d) {
		if (!empty($d['year'])) {
			$data[] = $d['year'] . '-' . $d['month'] . '-' . $d['day'] . '-' . $d['c'];
		}
	}
	$comma_sep_data = implode(',', $data);
	echo ($comma_sep_data);
	exit;
}


/****************************************************/
/* !Display Page */
/***************************************************/

$title = array(
	'/res/' => loc('Researchers'),
	'/res/data/' => loc('Data'),
	'' => loc('Usage Stats')
);

$styles = array(
	'#graph_container' 	=> 'width: 90%; 
							height: 400px; 
							margin: 1em;
							border-radius: 1em;',
	'#usage_chooser'	=> 'width: 90%; margin: 1em auto;'
);

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);

$page->displayBody();

?>

<div id="graph_container"></div>

<div id="usage_chooser" class="toolbar">
	<div class="toolbar-line">Display: <span id="display_chooser">
		<input type='radio' name='display' id='display-logins' value='Logins' checked="checked" /><label for='display-logins'>Logins</label>
		<input type='radio' name='display' id='display-unique' value='Unique Logins' /><label for='display-unique'>Unique Logins</label>
		<input type='radio' name='display' id='display-reg' value='Registrations' /><label for='display-reg'>Registrations</label>
	</span></div>
	
	<div class="toolbar-line">Per: <span id="interval_chooser">
		<input type='radio' name='interval' id='interval-year' value='Year' /><label for='interval-year'>Year</label>
		<input type='radio' name='interval' id='interval-quarter' value='Quarter' /><label for='interval-quarter'>Quarter</label>
		<input type='radio' name='interval' id='interval-month' value='Month' /><label for='interval-month'>Month</label>
		<input type='radio' name='interval' id='interval-week' value='Week' checked="checked" /><label for='interval-week'>Week</label>
		<input type='radio' name='interval' id='interval-day' value='Day' /><label for='interval-day'>Day</label>
	</span></div>
	
	<div class="toolbar-line">For the Past: <span id="past_chooser">
		<input type='radio' name='past' id='past-all' value='10 Years' /><label for='past-all'>10 Years</label>
		<input type='radio' name='past' id='past-year' value='Year' checked="checked" /><label for='past-year'>Year</label>
		<input type='radio' name='past' id='past-quarter' value='Quarter' /><label for='past-quarter'>Quarter</label>
		<input type='radio' name='past' id='past-month' value='Month' /><label for='past-month'>Month</label>
		<input type='radio' name='past' id='past-week' value='Week' /><label for='past-week'>Week</label>
		
	</span></div>
</div>

<script src="/include/js/highcharts/highcharts-<?= HIGHCHARTS ?>.js"></script>
<script src="/include/js/highcharts/<?= (MOBILE) ? 'mobile_' : '' ?>theme.js"></script>

<script>

	var show = 'Logins';
	var interval = 'Week';
	var past = 'Year';
	var chart;
	
	$j(function() {
	
		$j('#interval_chooser').buttonset().change( function() {
			getData(show, $j('input[name="interval"]:checked').val(), past);
		});
		
		$j('#display_chooser').buttonset().change( function() {
			getData($j('input[name="display"]:checked').val(), interval, past);
		});
		
		$j('#past_chooser').buttonset().change( function() {
			getData(show, interval, $j('input[name="past"]:checked').val());
		});
		
		
		
		getData(show, interval, past);
	});
	
	function showChart(theData) {

		chart = new Highcharts.Chart({
			chart: {
				renderTo: 'graph_container',
				zoomType: 'x',
				backgroundColor: 'rgba(255,255,255,.5)'
			},
		    title: {
				text: show + ' per ' + interval + ' for the past ' + past
			},
		    subtitle: {
				text: document.ontouchstart === undefined ?
					'Click and drag in the plot area to zoom in' :
					'Drag your finger over the plot to zoom in'
			},
			series: [{
				name: show,
				data: theData
			}],
			xAxis: {
				type: 'datetime',
				gridLineWidth: 0.5,
				title: {
					text: null
				}
			},
			yAxis: {
				title: {
					text: show
				},
				min: 0,
				startOnTick: true,
				endOnTick: true,
				gridLineWidth: 0.5,
				showFirstLabel: false
			},
			tooltip: {
				shared: true					
			},
			legend: {
				enabled: false
			},
			plotOptions: {
				series: {
					fillColor: 'hsl(200,20%,80%)',
					lineWidth: 1,
					marker: {
						enabled: false
					},
					shadow: true,
					enableMouseTracking: false
				}
			}
		});
	}
	
	function getData(s, interv, p) {
		show = s;
		interval = interv;
		past = p;
		$j('#graph_container').html('').css('background', 'url(/images/stuff/loading.gif) no-repeat center center');
		$j.get('./usage?show=' + show + '&interval=' + interval + '&past=' + past, function(response) {
			data = response.split(",");
			allData = [];
			for (i=0; i<data.length; i++) {
				subData = data[i].split("-");
				allData[i] = [Date.UTC(subData[0], subData[1], subData[2]), parseInt(subData[3])];
			}
			showChart(allData);
		});
	}

</script>

<?php

$page->displayFooter();

?>