<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin'), "/res/");

$title = array(
	'/res/' => loc('Researchers'),
	'/res/data/' => loc('Data'),
	'/res/data/chartchooser' => loc('My Charts')
);

$styles = array(
	'#maincontent, #contentmask' => 'overflow:visible;',
	'#graph_container_container' => 	
		'margin-top: 3em;
		position: relative; top: 3em;
		width: 47%; min-width: 400px;
		float: right;
		height: 20%; min-height: 300px;
		margin: 0 0 1em 0;
		border: 5px solid white; 
		border-bottom-left-radius: 1em; 
		border-bottom-right-radius: 1em; 
		background-color: rgba(255, 255, 255, .5);
		box-shadow: 4px 4px 6px rgba(0,0,0,.5);',
	'#graph_container' => 'height: 100%; top: -3em; position: relative;',
	'#min-max_row input' => 'width: 50px;',
	'#myChartSettings_form, #autoSettings_form, #query_container' => 
		'width: 47%; 
		max-width: 400px; 
		float: left; clear: left; 
		margin: 0 1em 0 0; 
		padding: 0;',
	'#myChartSettings, #autoSettings' => 'padding: 0; margin: 0; width: 100%;',
	//'#example_line' => 'float: right;',
	'#query_text' => 
		'border: 1em solid hsl(200, 25%, 90%); 
		border-bottom-left-radius: 1em; 
		border-bottom-right-radius: 1em; 
		width: 100%; height: 100%;
		position: absolute; right: 0; bottom:0;
		-webkit-box-sizing: border-box;
		-moz-box-sizing: border-box;
		box-sizing: border-box;',
	'#query_text:focus' => 'box-shadow: none;',
	'select#exp, select#quest, select#econ' => 'width: 200px;',
	'#query_container' => 
		'margin-top: 3em;
		border: 5px solid white; 
		border-bottom-left-radius: 1em; 
		border-bottom-right-radius: 1em; 
		background-color: hsl(200, 25%, 90%); 
		max-width: none; 
		box-shadow: 4px 4px 6px rgba(0,0,0,.5); 
		min-height: 400px; min-width: 200px;',
	'.title' => 
		'text-align: center; 
		font-weight: bold; 
		color: white; 
		width: 100%;
		background-color: hsl(200, 100%, 20%); 
		border-bottom: 5px solid white; 
		border-top-right-radius: 1em; 
		border-top-left-radius: 1em;
		border: 5px solid white; 
		padding: .25em 0; cursor: move;
		height: 1.5em;
		position: relative; top: -2.6em; left: -5px;',
	'#myChartSettings_form thead, #autoSettings_form thead' => 'cursor: move;'
);

if (MOBILE) {
	$styles['#myChartSettings_form, #autoSettings_form,#query_container, #graph_container_container'] = 'float: none; clear: left;';
	$styles['#query_container, #graph_container_container'] = 'height: 15em; min-height: 0; box-shadow: none; width: 97%; min-width: 97%; max-width:97%;';
	$styles['#graph_container_container, .d'] = 'margin-bottom: 4.5em;'; 
	$styles['#query_text'] = 'height: 100%; width: 100%;
		width: 100%; height: 100%;
		border: none;
		position: relative;
		top: -2.6em;
		-webkit-box-sizing: border-box;
		-moz-box-sizing: border-box;
		box-sizing: border-box;';
	$styles['#name, #notes'] = 'width: 10px;';
}

/****************************************************
 
DROP TABLE charts;
 
CREATE TABLE charts (
id SERIAL,
user_id INT(11),
name VARCHAR(255),
query_text MEDIUMTEXT,
notes TEXT,
PRIMARY KEY (id));

INSERT INTO charts VALUES (1, 1, 'Femininity Preferences', 'CREATE TEMPORARY TABLE tmp_score SELECT sex, 
(t1+t2+t3+t4+t5+t6+t7+t8+t9+t10+t11+t12+t13+t14+t15+t16+t17+t18+t19+t20)/20 as "female_faces",
(t21+t22+t23+t24+t25+t26+t27+t28+t29+t30+t31+t32+t33+t34+t35+t36+t37+t38+t39+t40)/20 as "male_faces"
FROM exp_72
LEFT JOIN user USING (user_id)
WHERE status>1 AND status<5 AND (sex="male" OR sex="female");
SELECT "Fem Prefs" as title,
"graph_container" as container,
"column" as chart_type,
"Participant Sex" as xlabel,
"Femininity Preference" as ylabel,
0 as ymin,
7 as ymax,
1 as yticks,
CONCAT(sex, " participants") as xcat,
AVG(female_faces) as "female faces",
AVG(male_faces) as "male faces"
FROM tmp_score 
GROUP BY sex;',
'Test chart');
 
 ***************************************************/
 
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
 
if (isset($_GET['id'])) {
	$mychart = new myQuery('SELECT * FROM charts WHERE id=' . $_GET['id']);
	$clean = $mychart->get_one_array();
}

/****************************************************/
/* !AJAX Responses */
/****************************************************/

/* !    $_GET['save']: save a chart */
if (array_key_exists('save', $_GET)) {
	$clean = my_clean($_POST);
	
	// make sure user has permission to edit this chart
	if ($_SESSION['status'] < 8) { 
		// researchers can edit only their own charts
		if (validID($clean['id'])) {
			$myaccess = new myQuery('SELECT user_id FROM charts WHERE id='.$clean['id']." AND user_id=".$_SESSION['user_id']);
			$checkuser = $myaccess->get_assoc(0);
			if ($checkuser['user_id'] != $_SESSION['user_id']) { echo 'You do not have permission to edit this chart'; exit; }
		}
	}

	$query = new myQuery(sprintf('REPLACE INTO charts 
			(id, user_id, name, query_text, notes) 
			VALUES 
			(%s, %d, "%s", "%s", "%s")',
			check_null($_POST['id'], 'id'),
			$_SESSION['user_id'],
			$clean['name'],
			$clean['query_text'],
			$clean['notes']
		)
	);
	
	if (0 < mysql_affected_rows()) {
		if (validID($_POST['id'])) {
			echo 'id:' . $_POST['id'];
		} else {
			echo 'id:' . mysql_insert_id();
		}
	} else {
		echo $query->get_query();
	}
	exit;
}

/* !    $_GET['delete']: delete a chart */
if (array_key_exists('delete', $_GET)) {
	$query = new myQuery('DELETE FROM charts WHERE id="' . $_POST['id'] . '" AND user_id="' . $_SESSION['user_id'] . '"');
	if (0 < mysql_affected_rows()) {
		$query = new myQuery('DELETE FROM dashboard WHERE type="chart" AND id=' . $_POST['id']);
		echo 'deleted';
	} else {
		echo $query->get_query();
	}
	exit;
}

/****************************************************/
/* !Set up forms */
/****************************************************/
 
require_once DOC_ROOT . '/include/classes/quest.php';

$input = array();	
$settings = array();
$input_width = 300;

// id
$input['id'] = new hiddenInput('id', 'id', $clean['id']);

// container
$input['container'] = new hiddenInput('container', 'container', 'graph_container');

// title
$input['name'] = new input('name', 'name', $clean['name']);
$input['name']->set_question('Name');
$input['name']->set_maxlength(100);
$input['name']->set_width($input_width);

// notes
$input['notes'] = new textArea('notes', 'notes', $clean['notes']);
$input['notes']->set_question('Labnotes');
$input['notes']->set_dimensions($input_width, 50, true, 50, 0);

// title
$settings['title'] = new input('title', 'title', $clean['title']);
$settings['title']->set_question('Title');
$settings['title']->set_maxlength(100);
$settings['title']->set_width($input_width);


// xlabel
$settings['xlabel'] = new input('xlabel', 'xlabel', $clean['xlabel']);
$settings['xlabel']->set_question('X-Label');
$settings['xlabel']->set_maxlength(100);
$settings['xlabel']->set_width($input_width);

// ylabel
$settings['ylabel'] = new input('ylabel', 'ylabel', $clean['ylabel']);
$settings['ylabel']->set_question('Y-Label');
$settings['ylabel']->set_maxlength(100);
$settings['ylabel']->set_width($input_width);

// min-max
$settings['min-max'] = new formElement('min-max', 'min-max');
$settings['min-max']->set_question('Axis Ranges<span class="note">Leave blank for default</span>');
$ci = 'X: <input type="text" name="xmin" id="xmin" title="Minimum x-value" placeholder="auto" value="' . $clean['xmin'] . '" /> to ' .
	'<input type="text" name="xmax" id="xmax" title="Maximum x-value" placeholder="auto" value="' . $clean['xmax'] . '" /> by ' .
	'<input type="text" name="xticks" id="xticks" title="X-axis interval" placeholder="auto" value="' . $clean['xticks'] . '" />' .
	'<br />Y: <input type="text" name="ymin" id="ymin" title="Minimum y-value" placeholder="auto" value="' . $clean['ymin'] . '" /> to ' .
	'<input type="text" name="ymax" id="ymax" title="Maximum y-value" placeholder="auto" value="' . $clean['ymax'] . '" /> by ' .
	'<input type="text" name="yticks" id="yticks" title="Y-axis interval" placeholder="auto" value="' . $clean['yticks'] . '" />';
$settings['min-max']->set_custom_input($ci);

// chart_type
$settings['chart_type'] = new select('chart_type', 'chart_type', $clean['chart_type']);
$settings['chart_type']->set_question('Chart Type');
$settings['chart_type']->set_options($chart_types);

// add exp score
$settings['score'] = new formElement('score', 'score');
$settings['score']->set_question('Add Score');
	// experiments
	$q = new myQuery('SELECT id, CONCAT(id, ": ", res_name) as name FROM exp ORDER BY id DESC');
	$experiments = $q->get_assoc(false, 'id', 'name');
	$exp = new select('exp', 'exp');
	$exp->set_options($experiments);
	// questionnaires
	$q = new myQuery('SELECT id, CONCAT(id, ": ", res_name) as name FROM quest ORDER BY id DESC');
	$questionnaires = $q->get_assoc(false, 'id', 'name');
	$quest = new select('quest', 'quest');
	$quest->set_options($questionnaires);
	// econ games
	$q = new myQuery('SELECT id, CONCAT(id, ": ", res_name) as name FROM econ ORDER BY id DESC');
	$econgames = $q->get_assoc(false, 'id', 'name');
	$econ = new select('econ', 'econ');
	$econ->set_options($econgames);
	// test against
	$test = new input('test', 'test');
	$test->set_maxlength(10);
	$test->set_width(50);

$ci = 	$exp->get_element() . ' exp<br />' . 
		$quest->get_element() . 'quest<br />' . 
		$econ->get_element() . ' econ<br />' .
		'test against ' . $test->get_element() . ' (e.g., >3)';
$settings['score']->set_custom_input($ci);

// note
$settings['msg'] = new msgRow('msg','msg');
$settings['msg']->set_custom_input('<span class="note">All of the settings for your chart are contained in the query below. The Auto Settings above are just quick ways to edit or insert these options into the query.</span>');

// set up chart attributes table
$q = new formTable();
$q->set_table_id('myChartSettings');
$q->set_title('Chart Data');
//$q->set_action('');
$q->set_questionList($input);
//$q->set_method('post');

$s = new formTable();
$s->set_table_id('autoSettings');
$s->set_title('Auto Settings');
//$s->set_action('');
$s->set_questionList($settings);
//$s->set_method('post');


// examples
$examples = new select('examples', 'examples');
$examples->set_options(array(
		2 => "2x2 Column",
		0 => "Range of scores",
		4 => "Range of grouped scores",
		1 => "Range of scores by sex",
		3 => "Pie chart of score distributions",
		5 => "Pie chart of a single question"
));


/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);

$page->displayBody();

?>

<div class='toolbar'>
	<button id='redrawChart'>Redraw</button>
	<button id='saveChart'>Save</button>
	<?php if (validID($clean['id'])) { ?>
	<button id='saveNew'>Save as New</button>
	<button id='deleteChart'>Delete</button>
	<button id='newChart'>New Chart</button>
	<?php } else { ?>
	<span id='example_line'>Examples: <?= $examples->get_element() ?></span>
	<?php } ?>
</div>

<div id="graph_container_container">
	<div class='title'>Chart</div>
	<div id="graph_container"></div>
</div>

<?= $q->print_form() ?>

<?= $s->print_form() ?>

<div id='query_container'>
	<div class='title'>Query</div>
	<textarea id='query_text' name='query_text'><?= $clean['query_text'] ?></textarea>
</div>

<div id="help" title="Help Creating Charts">
	<ul>
		<li>You can drag the windows around by the blue title bars and resize the query window.</li>
		<li>Create a new chart by choosing an example on the right that is similar to the type of chart you want to make.</li>
		<li>You can edit the chart query directly in the query box below, or you can use the Auto Settings to change individual settings.</li>
		<li>Create a score for an experiment or a questionnaire by choosing it from the "Add Score" menus and clicking "Get".</li>
		<li>You can create JND scores by putting ">3" in the "test against" box. This adds 1 to the total score only if a trial's score is greater than 3.</li>
		<li>You can name your dependent variable(s) anything except: <ul>
			<li><kbd>container</kbd>: Must be set as "graph_container"</li> 
			<li><kbd>chart_type</kbd>: Must be set to the chart type (column, line, spline, pie, area, areaspline, bar, or scatter)</li>
			<li><kbd>xcat</kbd>: For some types, must be set to the name of the categories</li>
			<li><kbd>title</kbd>: Can be set to the title of the chart</li>
			<li><kbd>xlabel</kbd>: Can be set to the x-axis label</li>
			<li><kbd>ylabel</kbd>: Can be set to the y-axis label</li>
			<li><kbd>xmin</kbd>: Can be set to the minimum value on the x-axis</li>
			<li><kbd>xmax</kbd>: Can be set to the maximum value on the x-axis</li>
			<li><kbd>xticks</kbd>: Can be set to the number of categories between ticks on the x-axis</li>
			<li><kbd>ymin</kbd>: Can be set to the minimum value on the y-axis</li>
			<li><kbd>ymax</kbd>: Can be set to the maximum value on the y-axis</li>
			<li><kbd>yticks</kbd>: Can be set to the number of categories between ticks on the y-axis</li>
			<li><kbd>reverse</kbd>: Can be set to "reverse" to switch the x and y axes</li>
		</ul></li>
		<li>If you want your dependent variables to have spaces or non-alphanumeric characters, put them inside double quotes.</li>
	</ul>
</div>

<!--**************************************************-->
<!-- !Javascripts -->
<!--**************************************************-->

<script src="/include/js/highcharts/highcharts-<?= HIGHCHARTS ?>.js"></script>
<script src="/include/js/highcharts/<?= (MOBILE) ? 'mobile_' : '' ?>theme.js"></script>

<script>
	/* !    Variables */
	var chart;
	
	/* !    Load Functions */
	$j(function() {
		getData();

		setOriginalValues('myChartSettings');
		//setOriginalValues('query_container');
		
		<?php if (!MOBILE) { ?>
		$j('#myChartSettings_form').addClass('drags').draggable({handle: 'thead', stack: '.drags'});
		$j('#autoSettings_form').addClass('drags').draggable({handle: 'thead', stack: '.drags'}); 
		$j('#query_container').addClass('drags').draggable({handle: '.title', stack: '.drags'}).resizable();
		$j('#graph_container_container').addClass('drags').draggable({handle: '.title', stack: '.drags'}).resizable({
			stop: function() { $j('#graph_container').show(); getData(); },
			start: function() { $j('#graph_container').hide(); }
		});
		<?php } ?>
		
		$j('.toolbar button').button();
		
		/* !        deleteChart */
		$j('#deleteChart').click( function() {
			var doublecheck = confirm("Delete this chart?");
			
			if (doublecheck) {
				$j.ajax({
					url: './mycharts?delete',
					type: 'POST',
					data: $j('#id').serialize(),
					success: function(data) {
						if ('deleted' == data) {
							window.location.href='/res/data/chartchooser';
						} else {
							$j('<div />').attr('title', 'Chart was not deleted').html(data).dialog();;
						}
					}
				});
				
				getData();
			}
		});
		
		/* !        addScore */
		$j('<button />').html('Get').button().appendTo($j('#score_row td.question')).click( function() {
			var score_id, score_type;
			if ($j('#exp').val() != 'NULL') {
				score_id = $j('#exp').val();
				score_type='exp';
			} else if ($j('#quest').val() != 'NULL') {
				score_id = $j('#quest').val();
				score_type='quest';
			} else if ($j('#econ').val() != 'NULL') {
				score_id = $j('#econ').val();
				score_type='econ';
			}

			var url = '/res/scripts/get_all_dv?id=' + score_id + '&type=' + score_type + '&' + $j('#test').serialize();
			$j.get(url, function(data) {
				$j('<div />').html('Copy the equation below to insert into the query.<div class="ui-state-highlight">' + data + '</div>').dialog({
					title: 'Score for ' + score_type + '_' + score_id,
					width: 500
				});
			});
			
			return false;
		});
		
		// only one on exp, quest and econ can be set at a time
		$j('#exp').change( function() { if ($j(this).val() > 0) { $j('#quest').val('NULL'); $j('#econ').val('NULL'); } } );
		$j('#quest').change( function() { if ($j(this).val() > 0) { $j('#exp').val('NULL'); $j('#econ').val('NULL'); } } );
		$j('#econ').change( function() { if ($j(this).val() > 0) { $j('#quest').val('NULL'); $j('#exp').val('NULL'); } } );
		
		/* !        newChart */
		$j('#newChart').click(function() { window.location = "mycharts"; });
		
		/* !        redrawChart */
		$j('#redrawChart').click(function() { getData(); });
			
		/* !        saveNew */
		$j('#saveNew').click(function() {
			$j('#id').val('');
			$j('#saveChart').trigger('click');
		});
		
		/* !        saveChart */
		$j('#saveChart').click(function() {
			if ($j('#name').val() == '') {
				$j('<div />').html('The chart must have a name.').dialog();
				$j('#name').focus();
				return false;
			}
	
			$j.ajax({
				type: 'POST',
				url: './mycharts?save',
				data: $j('#id, #name, #query_text, #notes').serialize(),
				dataType: 'html',
				success: function(data) {
					var parsedData = data.split(':');
					if (parsedData[0] == 'id') {
						$j('#id').val(parsedData[1]);
						//growl('Query Saved');
						//$j('#id').val(parsedData[1]);
						window.location = '?id=' + parsedData[1];
					} else {
						$j('<div />').html(data).dialog();
					}
				}
			});	
		});

		$j('#examples').change( function() {
			$j('#query_text').val(exampleQueryText[$j(this).val()]);
			$j('#myChartSettings_form')[0].reset();
			
			getData();
			setOriginalValues('myChartSettings');
			//setOriginalValues('query_container');
		});
		
		$j('#title, #chart_type, #xlabel, #ylabel').change( function() {
			var theRegex = new RegExp('"[^"]+" as ' + $j(this).attr('id'));
			var new_query = $j('#query_text').val().replace(theRegex, '"' + $j(this).val() + '" as ' + $j(this).attr('id'));
			
			if (new_query == $j('#query_text').val()) {
				// query didn't change, so insert the line
				theRegex = new RegExp('\\sFROM(?![\\s\\S]*FROM)');
				new_query = $j('#query_text').val().replace(theRegex, ',' + "\n" + '"' + $j(this).val() + '" as ' + $j(this).attr('id') + "\n" + 'FROM');
			}
			$j('#query_text').val(new_query);
			
			if ($j('#query_text').val() != '') { getData(); }
		});
		
		$j('#xmin, #xmax, #xticks, #ymin, #ymax, #yticks').change( function() {
			var theValue = ($j(this).val() == '') ? 'NULL' : $j(this).val();
			var theRegex = new RegExp('[-+]?[0-9]*\.?[0-9]+ as ' + $j(this).attr('id'));
			var new_query = $j('#query_text').val().replace(theRegex, theValue + ' as ' + $j(this).attr('id'));
			
			if (new_query == $j('#query_text').val()) {
				// query didn't change, so insert the line
				theRegex = new RegExp('\\sFROM(?![\\s\\S]*FROM)');
				new_query = $j('#query_text').val().replace(theRegex, ',' + "\n" + theValue + ' as ' + $j(this).attr('id') + "\n" + 'FROM');
			}
			$j('#query_text').val(new_query);
			
			if ($j('#query_text').val() != '') { getData(); }
		});
		
	});

	

	/* !    getData() */
	function getData() {
		if ($j('#id').val() > 0 || $j('#query_text').val() != '') {
			$j('#graph_container').html('').css('background', 'url("/images/loaders/loading.gif") center center no-repeat');
	
			$j.ajax({
				type: 'POST',
				url: '/include/scripts/chart?id=' + $j('#id').val(),
				data: $j('#query_text').serialize(),
				success: function(data) {
					//alert(JSON.stringify(data));
					$j('#graph_container').css('background', 'none');
					chart = new Highcharts.Chart(data);
				},
				error: function() {
					growl('Graph Error', 2000);
				},
				dataType: 'json'
			});
		}
	}

	
	/* !    data for loading example queries */

	var exampleQueryText = [];

	//score-range	
	exampleQueryText[0] = 	'CREATE TEMPORARY TABLE tmp_score SELECT' + "\n" +
							'q3233+q3234+q3235+q3236+q3237+' + "\n" +
							'q3238+q3239+q3240+q3241+q3242+' + "\n" +
							'q3243+q3244+q3245+q3246+q3247+' + "\n" +
							'q3248+q3249+q3250+q3251+q3252 as score' + "\n" +
							'FROM quest_151;' + "\n" +
							"\n" +
							'SELECT @totalp := COUNT(*) FROM tmp_score WHERE score IS NOT NULL GROUP BY NULL;' + "\n" +
							"\n" +
							'SELECT "Mini-K Demo" as title,' + "\n" +
							'"Mini-K Score" as xlabel,' + "\n" +
							'"Proportion of Participants with that score" as ylabel,' + "\n" +
							'score as xcat,' + "\n" +
							'COUNT(*)/@totalp as dv,' + "\n" +
							'10 as xticks,' + "\n" +
							'0 as ymin,' + "\n" +
							'"line" as chart_type,' + "\n" +
							'"reverse" as reverse,' + "\n" +
							'"graph_container" as container' + "\n" +
							'FROM tmp_score WHERE score IS NOT NULL GROUP BY score';
	
	//score-range-by-sex
	exampleQueryText[1] = 	'CREATE TEMPORARY TABLE tmp_score SELECT sex,' + "\n" + 
							'(q2801+q2802+q2803+q2804+q2805+q2806+q2807) as score' + "\n" +
							'FROM quest_130' + "\n" +
							'LEFT JOIN user USING (user_id)' + "\n" +
							'WHERE sex="male" OR sex="female";' + "\n" +
							"\n" +
							'SELECT "Pathogen Disgust Demo" as title,' + "\n" +
							'"graph_container" as container,' + "\n" +
							'"areaspline" as chart_type,' + "\n" +
							'"Pathogen Disgust Score" as xlabel,' + "\n" +
							'"Proportion of Participants with that score" as ylabel,' + "\n" +
							'sex as xcat,' + "\n" +
							'AVG(IF(score=0,1,0)) as "0.0",' + "\n" +
							'AVG(IF(score=1,1,0)) as "1",' + "\n" +
							'AVG(IF(score=2,1,0)) as "2",' + "\n" +
							'AVG(IF(score=3,1,0)) as "3",' + "\n" +
							'AVG(IF(score=4,1,0)) as "4",' + "\n" +
							'AVG(IF(score=5,1,0)) as "5",' + "\n" +
							'AVG(IF(score=6,1,0)) as "6",' + "\n" +
							'AVG(IF(score=7,1,0)) as "7",' + "\n" +
							'AVG(IF(score=8,1,0)) as "8",' + "\n" +
							'AVG(IF(score=9,1,0)) as "9",' + "\n" +
							'AVG(IF(score=10,1,0)) as "10",' + "\n" +
							'AVG(IF(score=11,1,0)) as "11",' + "\n" +
							'AVG(IF(score=12,1,0)) as "12",' + "\n" +
							'AVG(IF(score=13,1,0)) as "13",' + "\n" +
							'AVG(IF(score=14,1,0)) as "14",' + "\n" +
							'AVG(IF(score=15,1,0)) as "15",' + "\n" +
							'AVG(IF(score=16,1,0)) as "16",' + "\n" +
							'AVG(IF(score=17,1,0)) as "17",' + "\n" +
							'AVG(IF(score=18,1,0)) as "18",' + "\n" +
							'AVG(IF(score=19,1,0)) as "19",' + "\n" +
							'AVG(IF(score=20,1,0)) as "20",' + "\n" +
							'AVG(IF(score=21,1,0)) as "21",' + "\n" +
							'AVG(IF(score=22,1,0)) as "22",' + "\n" +
							'AVG(IF(score=23,1,0)) as "23",' + "\n" +
							'AVG(IF(score=24,1,0)) as "24",' + "\n" +
							'AVG(IF(score=25,1,0)) as "25",' + "\n" +
							'AVG(IF(score=26,1,0)) as "26",' + "\n" +
							'AVG(IF(score=27,1,0)) as "27",' + "\n" +
							'AVG(IF(score=28,1,0)) as "28",' + "\n" +
							'AVG(IF(score=29,1,0)) as "29",' + "\n" +
							'AVG(IF(score=30,1,0)) as "30",' + "\n" +
							'AVG(IF(score=31,1,0)) as "31",' + "\n" +
							'AVG(IF(score=32,1,0)) as "32",' + "\n" +
							'AVG(IF(score=33,1,0)) as "33",' + "\n" +
							'AVG(IF(score=34,1,0)) as "34",' + "\n" +
							'AVG(IF(score=35,1,0)) as "35",' + "\n" +
							'AVG(IF(score=36,1,0)) as "36",' + "\n" +
							'AVG(IF(score=37,1,0)) as "37",' + "\n" +
							'AVG(IF(score=38,1,0)) as "38",' + "\n" +
							'AVG(IF(score=39,1,0)) as "39",' + "\n" +
							'AVG(IF(score=40,1,0)) as "40",' + "\n" +
							'AVG(IF(score=41,1,0)) as "41",' + "\n" +
							'AVG(IF(score=42,1,0)) as "42"' + "\n" +
							'FROM tmp_score' + "\n" +
							'GROUP BY sex';
								
	//facesex-by-sex
	exampleQueryText[2] = 	'SELECT "2x2 Columns Demo" as title,' + "\n" +
							'"column" as chart_type,' + "\n" +
							'"Face Sex" as xlabel,' + "\n" +
							'"Average femininity preference" as ylabel,' + "\n" +
							'CONCAT(sex, " participants") as xcat,' + "\n" +
							'AVG(t1+t2+t3+t4+t5+t6+t7+t8+t9+t10+t11+t12+' + "\n" +
							't13+t14+t15+t16+t17+t18+t19+t20)/20 as "female faces",' + "\n" +
							'AVG(t21+t22+t23+t24+t25+t26+t27+t28+t29+t30+t31+t32+' + "\n" +
							't33+t34+t35+t36+t37+t38+t39+t40)/20 as "male faces",' + "\n" +
							'7 as ymax,' + "\n" +
							'"graph_container" as container' + "\n" +
							'FROM exp_72 LEFT JOIN user USING (user_id)' + "\n" +
							'WHERE sex="male" OR sex="female"' + "\n" +
							'GROUP BY sex';
	
	// participant sex and sexpref
	exampleQueryText[3] = 	'CREATE TEMPORARY TABLE tmp_score' + "\n" +
							'SELECT q6227 + q6228 + q6229 + q6230 + q6231 + q6232 + ' + "\n" +
							'q6233 + q6234 + q6235 + q6236 + q6237 + q6238 as score' + "\n" +
							'FROM quest_269;' + "\n" +
							"\n" +
							'SELECT "Religious Fundamentalism Scores" as title,' + "\n" +
							'"graph_container" as container,' + "\n" +
							'"pie" as chart_type,' + "\n" +
							'AVG(IF(score<-40,1,0)) as "low (<-40)",' + "\n" +
							'AVG(IF(score>=-40 AND score<-20,1,0)) as "medium(-40 to -21)",' + "\n" +
							'AVG(IF(score>=-20 AND score<0,1,0)) as "high(-20 to -1)",' + "\n" +
							'AVG(IF(score>=0,1,0)) as "very high (0+)"' + "\n" +
							'FROM tmp_score' + "\n" +
							'WHERE score IS NOT NULL' + "\n" +
							'GROUP BY NULL';
							
	exampleQueryText[4] = 	'CREATE TEMPORARY TABLE tmp_score SELECT' + "\n" +
							'ROUND((q3233+q3234+q3235+q3236+q3237+' + "\n" +
							'q3238+q3239+q3240+q3241+q3242+' + "\n" +
							'q3243+q3244+q3245+q3246+q3247+' + "\n" +
							'q3248+q3249+q3250+q3251+q3252)/10)*10 as score' + "\n" +
							'FROM quest_151;' + "\n" +
							"\n" +
							'SELECT @totalp := COUNT(*) FROM tmp_score WHERE score IS NOT NULL GROUP BY NULL;' + "\n" +
							"\n" +
							'SELECT "Mini-K Scores grouped to the nearest 10" as title,' + "\n" +
							'"Mini-K Score" as xlabel,' + "\n" +
							'"Proportion of Participants with that score" as ylabel,' + "\n" +
							'score as xcat,' + "\n" +
							'COUNT(*)/@totalp as dv,' + "\n" +
							'10 as xticks,' + "\n" +
							'0 as ymin,' + "\n" +
							'"column" as chart_type,' + "\n" +
							'"reverse" as reverse,' + "\n" +
							'"graph_container" as container' + "\n" +
							'FROM tmp_score WHERE score IS NOT NULL GROUP BY score';

	exampleQueryText[5] = 	'SELECT "Contraceptive Use" as title,' + "\n" +
							'"graph_container" as container,' + "\n" +
							'"pie" as chart_type,' + "\n" +
							'AVG(IF(q16=0,1,0)) as "None",' + "\n" +
							'AVG(IF(q16=1,1,0)) as "Oral (e.g., the pill)",' + "\n" +
							'AVG(IF(q16=2,1,0)) as "Injection (e.g., Depo-Provera)",' + "\n" +
							'AVG(IF(q16=3,1,0)) as "Patch (e.g., OrthoEvra)",' + "\n" +
							'AVG(IF(q16=4,1,0)) as "Implant (e.g., Norplant)",' + "\n" +
							'AVG(IF(q16=5,1,0)) as "Other"' + "\n" +
							'FROM quest_5' + "\n" +
							'LEFT JOIN user USING (user_id)' + "\n" +
							'WHERE status>1 AND status<6' + "\n" +
							'AND q16 IS NOT NULL' + "\n" +
							'GROUP BY NULL';
	

</script>

<?php

$page->displayFooter();

?>