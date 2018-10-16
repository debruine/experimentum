<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin'), "/res/");

/****************************************************
 * Get Data for Past $interval Days
 ***************************************************/

if ($_GET['interval'] > 0) {
	$i = intval($_GET['interval']);
	$interval = ifEmpty($i, 7);
	
	$query = new myQuery("SELECT COUNT(*) as c FROM referer WHERE DATE_ADD(datetime, INTERVAL $interval DAY) > NOW() GROUP BY NULL");
	$total = $query->get_assoc(0);
	
	$query = new myQuery("SELECT LEFT(REPLACE(REPLACE(url, 'www.',''), 'http://', ''),
						 LOCATE('/',REPLACE(REPLACE(url, 'www.',''), 'http://', ''))-1) AS newURL,
						MAX(REPLACE(url, 'http://','')) AS fullURL,
						COUNT(*) as c
						FROM referer 
						WHERE DATE_ADD(datetime, INTERVAL $interval DAY) > NOW()
						GROUP BY newURL
						HAVING c > {$total['c']}/100
						ORDER BY c DESC"); 
	
	$data_assoc = $query->get_assoc();
	$data = array();
	$allPercent = 0;
	foreach($data_assoc as $d) {
		$percent = round($d['c'] / $total['c'] * 100, 1);
		$data[] = array(
			'name' => $d['newURL'],
			'y' => $percent,
			'url' => 'http://' . $d['fullURL']
		);
		$allPercent = $allPercent + $percent;
	}
	
	// add rest of percent as other
	$data[] = array(
		'name' => 'Other',
		'y' => (100-$allPercent),
		'url' => 'referer'
	);	
	
	$json_data = array(
		$total['c'],
		$data
	);
	
	echo json_encode($json_data, JSON_NUMERIC_CHECK);
	exit;
}


/****************************************************/
/* !Display Page */
/***************************************************/

$title = array(
	'/res/' => loc('Researchers'),
	'/res/data/' => loc('Data'),
	'' => loc('Domain Referrals')
);

$styles = array(
	'#graph_container' 	=> 	'width: 90%; 
							height: 500px; 
							margin: 1em auto;'
);

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);

$page->displayBody();

?>

<div id="graph_container"></div>

<script src="/include/js/highcharts/highcharts-<?= HIGHCHARTS ?>.js"></script>
<script src="/include/js/highcharts/<?= (MOBILE) ? 'mobile_' : '' ?>theme.js"></script>

<script>
	
	var interval = 7;
	var chart;
	
	$(function() {
		getData(interval);
	});
	
	function showChart(theData, total) {
		chart = new Highcharts.Chart({
			chart: {
				renderTo: 'graph_container',
			},
			title: {
				text: 'Referrals in the Past ' + interval + ' Days'
			},
			subtitle: {
				text: 'Total: ' + total + ' Referrals'
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
		            point: {
		                events: {
		                    click: function() {
		                        location.href = this.options.url;
		                    }
		                }
		            },
		            shadow: true
		        }
			},
		    series: [{
				type: 'pie',
				name: 'Referrals in the Past ' + interval + ' Days',
				data: theData
			}]
		});
	}
	
	function getData(interv) {
		interval = interv;
		$('#graph_container').css('background', 'url(/images/stuff/loading.gif) no-repeat center center');
		$.ajax({
			url: './referer?interval=' + interval,
			type: 'GET',
			dataType: 'json',
			success: function(data) {
				//alert(JSON.stringify(data));
				showChart(data[1], data[0]);
			}
		});
	}

</script>

<?php

$page->displayFooter();

?>