<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth('admin');

$title = array(
    '/res/' => loc('Researchers'),
    '/res/admin/' => loc('Admin'),
    '' => 'Custom Query'
);

$styles = array(
    '#top_buttons' => 'text-align: center; font-size: 100%',
    '#query' => 'max-height: 600px;',
    '#data_table' => 'max-width: 100%; min-height: 300px; overflow: auto;',
);

if (MOBILE) {
    $styles['div'] = 'float: none; clear: left;';
}


/****************************************************
 * AJAX Responses
 ***************************************************/

if (array_key_exists('show', $_GET)) {
    $query = new myQuery($_POST['query'], true);
    echo '<h2>', $query->get_num_rows(), ' participants</h2>', ENDLINE;
    $rotated = ($_POST['rotate']=='yes') ? true : false;
    echo $query->get_result_as_table(true, false, $rotated);
    exit;
} else if (array_key_exists('download', $_GET)) {
    $query = new myQuery($_POST['query'], true);
    $data = $query->get_assoc();
    if ($_POST['rotate']=='yes') $data = rotate_array($data);
    
    function cleanDataForExcel(&$str) { 
        $str = preg_replace("/\t/", "\\t", $str); 
        $str = preg_replace("/\r?\n/", "\\n", $str);  
        if (strstr($str, '"') || strstr($str, ',')) {
            $str = '"' . str_replace('"', '""', $str) . '"';
        }
    }  
    
    # check that everything went OK
    
    if (!empty($data)) {
        header("Content-Disposition: attachment; filename=\"admin_query_data.csv\"");
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

/****************************************************
 * Set up forms
 ***************************************************/
 
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';
 
$input = array();
$input_width = (MOBILE) ? 300 : 500;

// query
$input['query'] = new textArea('query', 'query', $clean['query']);
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
$q->set_action('./query?download');
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
    <button class='downloadCSV'>Download</button>
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

    $(function() {
        setOriginalValues('myQuery');
        
        $("#rotate_options").buttonset();

        // set up main button functions
        $( "#top_buttons" ).buttonset();
        
        $( ".showData" ).click(function() { 
            if ($('#query').val() == '') return false;
            $('#data_table').html('<img src="/images/loaders/loading" style="display: block; margin: 0 auto;" />');
            $.ajax({
                url: './query?show',
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
    });
    
    function downloadCSV() {
        if ($('#query').val() == '') return false;
        $('#myQuery_form').submit();
    }
</script>

<?php

$page->displayFooter();

?>