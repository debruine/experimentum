<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin'), "/res/");

/****************************************************
 * Get Data for Past $interval Days
 ***************************************************/

if (isset($_GET['interval']) && is_numeric($_GET['interval'])) {
	$interval = intval($_GET['interval']);
	//$chart_title = sprintf(loc('Browser Use in the Past %s Days'), $interval);
	
	$query = new myQuery("SELECT 
		IF(LOCATE('FireFox', browser), 'FireFox', 
		IF(LOCATE('Safari', browser), IF(LOCATE('Chrome', browser), 'Chrome', 'Safari'),
			  IF(LOCATE('MSIE 9', browser), 'MSIE 9', 
			  IF(LOCATE('MSIE 8', browser), 'MSIE 8', 
			  IF(LOCATE('MSIE 7', browser), 'MSIE 7',
			  IF(LOCATE('MSIE 6', browser), 'MSIE 6', 'Other')))))) as type,
		COUNT(*) as total
		FROM login 
		WHERE browser IS NOT NULL
		AND DATE_ADD(logintime, INTERVAL $interval DAY) > NOW()
		GROUP BY type
		ORDER BY total DESC"
	);
	
	$data_assoc = $query->get_assoc();
	$data = array();
	$allPercent = 0;
	foreach ($data_assoc as $d) {
		$data[$d['type']] = $d['total'];
		$allPercent += $d['total'];
	}
	
	//$subtitle = sprintf(loc('Total: %s Logins'), $allPercent);
	
	$dataset = array(0 => $allPercent);
	foreach ($data as $browser => $count) {
		//$dataset[] = "{ name: '$browser', y: " . round($count / $allPercent * 100, 1) . '}';
		$dataset[] = $browser . ';' . round($count / $allPercent * 100, 1);
	}
	
	echo implode(',', $dataset);
	exit;
}

/****************************************************/
/* !Display Page */
/***************************************************/

$title = array(
	'/res/' => loc('Researchers'),
	'/res/data/' => loc('Data'),
	'' => loc('Browser Use')
);

$styles = array(
	'#graph_container' 	=> 	'width: 90%; 
							height: 500px; 
							margin: 1em auto;'
);

$page = new page($title);
$page->set_logo(true);
$page->set_menu(true);

$page->displayHead($styles);

$page->displayBody();

?>

<p>Interval: <span id="interval_chooser">
	<input type='radio' name='interval' id='interval-year' value='365' /><label for='interval-year'>Year</label>
	<input type='radio' name='interval' id='interval-quarter' value='90' /><label for='interval-quarter'>Quarter</label>
	<input type='radio' name='interval' id='interval-month' value='30' /><label for='interval-month'>Month</label>
	<input type='radio' name='interval' id='interval-week' value='7' checked="checked" /><label for='interval-week'>Week</label>
	<input type='radio' name='interval' id='interval-day' value='1' /><label for='interval-day'>Day</label>
</span></p>

<div id="graph_container"></div>

<script src="/include/js/highcharts/highcharts-<?= HIGHCHARTS ?>.js"></script>
<script src="/include/js/highcharts/<?= (MOBILE) ? 'mobile_' : '' ?>theme.js"></script>

<script>
	var chart;
	var interval = 7;
	
	$j(function() {
		$j('#interval_chooser').buttonset().change( function() {
			getData($j('input[name="interval"]:checked').val());
		});
		
		getData(interval);
	});

	function showChart(theData, total) {
	   chart = new Highcharts.Chart({
	      chart: {
				renderTo: 'graph_container',
		  },
	      title: {
	         text: 'Browser Use in the Past ' + interval + ' Days'
	      },
	      subtitle: {
	         text: 'Total: ' + total + ' Logins'
	      },
	      legend: {
	      	 enabled: false,
	         layout: 'vertical',
	         backgroundColor: Highcharts.theme.legendBackgroundColor || '#FFFFFF',
	         align: 'right',
	         verticalAlign: 'top',
	         x: -20,
	         y: 60,
	         floating: true,
	         shadow: true
	      },
	      tooltip: {
				formatter: function() {
					return '<b>'+ this.point.name +'</b>: '+ this.y +' %';
				}
			},
	      plotOptions: {
				pie: {
					allowPointSelect: false,
					cursor: 'pointer',
					dataLabels: {
						enabled: true,
						color: '#000000',
						connectorColor: '#000000',
						formatter: function() {
							return '<b>'+ this.point.name +'</b>: '+ this.y +' %';
						}
					},
					showInLegend: false
				},
				series: {
		            cursor: 'pointer',
		            shadow: true
		        }
		  },
	      series: [{
				type: 'pie',
				name: 'Browser Use in the Past ' + interval + ' Days',
				data: theData
			}]
	   }); 
	}
	
	function getData(interv) {
		interval = interv;
		//$j('#graph_container').css('background', 'url(/images/stuff/loading.gif) no-repeat center center');
		$j.get('./browsers?interval=' + interval, function(response) {
			data = response.split(",");
			allData = [];
			for (i=1; i<data.length; i++) {
				subData = data[i].split(";");
				allData[i-1] = { name: subData[0], y: subData[1]-0 };
			}
			showChart(allData, data[0]);
		});
	}

</script>
<?php

$page->displayFooter();

?>