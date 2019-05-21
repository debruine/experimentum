<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
auth(array('admin'), "/res/");

$title = array(
	'/res/' => loc('Researchers'),
	'/res/admin/' => loc('Admin'),
	'/res/admin/query' => 'Custom Queries'
);

$styles = array(
	'#top_buttons, #bottom_buttons, #stat_buttons' => 'text-align: center; font-size: 100%',
	'#stat_buttons' => 'visible: false;',
	'#query' => 'max-height: 600px;',
	'#data_table' => 'max-width: 100%; min-height: 300px; overflow: auto;',
);

if (MOBILE) {
	$styles['div'] = 'float: none; clear: left;';
	$styles['#name'] = 'width: 100%;';
}

function cleanDataForExcel(&$str) { 
    $str = preg_replace("/\t/", "\\t", $str); 
    $str = preg_replace("/\r?\n/", "\\n", $str);  
    if (strstr($str, '"') || strstr($str, ',')) {
        $str = '"' . str_replace('"', '""', $str) . '"';
    }
} 


/****************************************************
 * AJAX Responses
 ***************************************************/

if (array_key_exists('query', $_POST)) {
    
    $query = new myQuery($_POST['query']);
    
    if (array_key_exists('table', $_GET)) {
        echo $query->get_result_as_table();
        exit;
    } else {
        $data = $query->get_assoc();
        if ($_POST['rotate']=='yes') $data = rotate_array($data); 
        
        # check that everything went OK
        
        if (!empty($data)) {
            header("Content-Disposition: attachment; filename=\"PSA_custom_query.csv\"");
            header("Content-Type: text/plain");  
            
            $header= true; 
            $sep = ",";
            
            foreach($data as $row) {
                if($header) {
                    # display field/column names as first row 
                    echo implode($sep, array_keys($row)) . "\n"; 
                    $header = false; 
                } 
                
                array_walk($row, 'cleanDataForExcel'); 
                echo implode($sep, array_values($row)) . "\n"; 
            }
        }
        
        exit;
    }
}

/****************************************************
 * Set up forms
 ***************************************************/

$input = array();
$input_width = (MOBILE) ? 300 : 500;

// query
$input['query'] = new textArea('query', 'query');
$input['query']->set_dimensions($input_width, 100, true, 100, 0);
$input['query']->set_question('Query');

// rotated
$input['rotate'] = new radio('rotate', 'rotate', 'no');
$input['rotate']->set_question('Rotate?');
$input['rotate']->set_orientation('horiz');
$input['rotate']->set_options(array('no'=>'no', 'yes'=>'yes'));

// set up form table
$q = new formTable();
$q->set_table_id('myQuery');
$q->set_action('/res/admin/query');
$q->set_questionList($input);
$q->set_method('post');


/****************************************************/
/* !Display Page */
/***************************************************/
 
$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<div id='top_buttons'>
	<button class='showData'>Show Data</button>
	<button class='downloadCSV'>CSV</button>
</div>

<?= $q->print_form(); ?>

<div id="data_table"></div>


<div id="help" title="Query Help">
	<ul>
		<li>Set &ldquo;Rotate&rdquo; to yes and click &ldquo;Show Data&rdquo; to view the data table rotated 90 degrees. This is often useful for calculating inter-rater agreement.</li>
	</ul>
</div>

<!--**************************************************
 * Javascripts for this page
 ***************************************************-->


<script>

	$("#rotate_options").buttonset();

	// set up main button functions
	$( "#top_buttons, #bottom_buttons" ).buttonset();
	$( ".showData" ).click(function() { 
		if ($('#query').val() == '') return false;
		$('#data_table').html('<img src="/images/loaders/loading" style="display: block; margin: 0 auto;" />');
		$.ajax({
			url: './query?table',
			type: 'POST',
			data: $('#myQuery_form').serialize(),
			success: function(data) {
				$('#data_table').html(data);
				stripe('#data_table tbody');
				
				var theHeight = $(window).height() - $('#data_table').offset().top - 30 - $('#footer').height();
				$('#data_table').css({
					'height': theHeight
				});
				console.log(theHeight);
			}
		});
	});
		$( ".downloadCSV" ).click(function() { downloadCSV(); });
	
	function downloadCSV() {
		if ($('#query').val() == '') return false;
		$('#csv').val(true);
		$('#myQuery_form').submit();
	}
</script>

<?php

$page->displayFooter();

?>