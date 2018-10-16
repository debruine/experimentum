<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(4);

/****************************************************/
/* !Display Page */
/***************************************************/

$title = array(
	'/res/' => loc('Researchers'),
	'/res/data/' => loc('Data'),
	'' => loc('Stats')
);

$styles = array(
	'#graph_container' 	=> 	'width: 90%; 
							height: 500px; 
							margin: 1em auto;'
);

if (array_key_exists('tables', $_GET)) {

	// get list of all tables
	$query = new myQuery("SHOW TABLES WHERE LOCATE('exp_', Tables_in_exp) OR LOCATE('quest_', Tables_in_exp) OR LOCATE('econ_', Tables_in_exp)");
	$all_tables = $query->get_assoc(false, 'Tables_in_exp', 'Tables_in_exp'); 

	$sections = array(
		"exp" => "Experiments",
		"quest" => "Questionnaires",
		"econ" => "Econ Games"
	);
	
	foreach($sections as $section => $sectname) {
		echo "<table class='sortable' id='participant_$section_table'>\n";
		echo "<tr><th>ID</th><th>$sectname</th><th>Count</th><th>Last Date</th></tr>\n\n";
		
		$query = new myQuery("SELECT id, res_name FROM $section ORDER BY id DESC");
		$rows = $query->get_assoc();
		
		foreach ($rows as $myrow) {
			if (in_array($section . "_" . $myrow['id'], $all_tables)) {
				$query = sprintf("SELECT count(*) as c FROM %s_%d 
					LEFT JOIN user USING (user_id) 
					WHERE DATEDIFF('%s', endtime)<=0 
					AND status>1 AND status<6 
					GROUP BY NULL", 
					$section, 
					$myrow['id'], 
					$_GET['date']
				);
				($result2 = @mysql_query($query, $db)) || myerror($query);
				
				if (@mysql_num_rows($result2) > 0) {
					$myrow2 = @mysql_fetch_assoc($result2);
					$total += $myrow2['c'];
					echo sprintf("<tr><td><a href='../%s/info?id=%d'>%s</a></td><td>%s</td><td>%s</td><td>%s</td></tr>\n\n", 
						$section, $myrow['id'], $myrow['id'], $myrow['res_name'], $myrow2['c'], $total
					);
					
				}
			}
		}
		
		echo "</table>\n\n";
	}
	
	exit;
}

/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);

$page->displayBody();

?>




<button id="show_totals">Show Totals Since</button>
<input type="date" class="datepicker" name="date" id="date" 
			value="2012-07-01" placeholder="yyyy-mm-dd" yearrange="-10:0" mindate="-10y" maxdate="0y" />
<div id="completed_tables">
	
</div>


<!--*************************************************
 * Javascripts for this page
 *************************************************-->

<script>

$(function() {

	$('#show_totals').button().click(function() {
		var theDate = $('#date').val();
		
		$('#completed_tables').html("<img src='/images/loader/circle_column_theme' />")
							   .load('stats?tables&date=' + theDate, function() {
									stripe('#completed_tables tbody');
									$('#show_comp_tables').hide();
								});
	});
});

</script



<?php

$page->displayFooter();

?>