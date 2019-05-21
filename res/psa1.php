<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

$styles = array(
    "table.query tr td:last-child" => "text-align: right;",
    ".clickhide" => "max-height: 1.5em; overflow: hidden;"
);

$title = array(
    '/res/' => 'Researchers',
    '/res/psa1' => 'Study 1'
);

$q = new myQuery('SELECT 
        REPLACE(SUBSTRING_INDEX(res_name, "_", 2), "PSA1_", "") AS Language,
        CONCAT("<a href=\'/res/project/info?id=", project.id, "\'>", SUBSTRING_INDEX(res_name, "_", -2), "</a>") AS Lab,
        CONCAT(COUNT(DISTINCT qd.dv), " distinct IDs:<ol><li>",
        GROUP_CONCAT(DISTINCT qd.dv ORDER BY qd.dv SEPARATOR "</li><li>"), "</li></ol>") AS `Lab Codes`,
        COUNT(DISTINCT user.user_id) AS `Participants`
    FROM session 
    LEFT JOIN user ON user.user_id = session.user_id
    LEFT JOIN project ON session.project_id = project.id
    LEFT JOIN quest_data AS qd ON qd.session_id = session.id
    LEFT JOIN question ON question.quest_id = qd.quest_id 
                      AND question.id = qd.question_id 
                      
    WHERE user.status IN ("guest", "registered")
      AND project.id > 0
      AND question.name = "lab"
      AND SUBSTRING_INDEX(res_name, "_", -1) != "test"
    GROUP BY project.id
    ORDER BY Lab');
    
$n = $q->get_one_col("Participants");
$table = $q->get_result_as_table(true, true);
$total = array_sum($n);
$table = str_replace("</table>", "<tfoot><tr><td colspan='3'>Total</td><td>{$total}</td></tfoot></table>", $table);

$q = new myQuery('SELECT 
	REPLACE(SUBSTRING_INDEX(res_name, "_", 2), "PSA1_", "") AS Language,
    SUBSTRING_INDEX(res_name, "_", -2) AS Lab,
    COUNT(DISTINCT user.user_id) AS `Participants`
	FROM project 
	LEFT JOIN session ON session.project_id = project.id
	LEFT JOIN user ON user.user_id = session.user_id 
	      AND user.status IN ("guest", "registered")
	WHERE LOCATE("PSA1_", res_name) 
	  AND !LOCATE("_test", res_name)
	GROUP BY project.id
    ORDER BY Lab');
$all_table = $q->get_result_as_table(true, true);

if (array_key_exists('download', $_POST)) {
	$filename = 'psa1_labs_' . date('Y-m-d') . '.csv';
	
	$data = $q->get_assoc();
	
	function cleanDataForExcel(&$str) { 
	    $str = preg_replace("/\t/", "\\t", $str); 
	    $str = preg_replace("/\r?\n/", "\\n", $str);  
	    if (strstr($str, '"') || strstr($str, ',')) {
	        $str = '"' . str_replace('"', '""', $str) . '"';
	    }
	}  
	
	# check that everything went OK
	
	if (!empty($data)) {
	    header("Content-Disposition: attachment; filename=\"$filename\"");
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

/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();

?>

<p>The number of guest and registered users who have started the 
    study at each project link. Click on a column header to sort it. Click on the 
    lab code count to toggle the list of lab codes. <button id="toggle_all_ids" class='tinybutton'>toggle all IDs</button></p>
    
    <?= $table ?>
    
    
<h2>All Labs</h2>

<button id="all_download">Download</button>
    
    <?= $all_table ?>
    

<script>
	$('#all_download').button().click(function() {
	    postIt('/res/psa1', {
	        download: true
	    });
	});
	
    $('table.query tbody tr td:nth-child(3)').wrapInner("<div class='clickhide' />").click(function() {
        $(this).find('div').toggleClass('clickhide');
    });
    
    $('#toggle_all_ids').button().click(function() {
        var $divs = $('table.query tbody tr td:nth-child(3) div');
        $divs.toggleClass('clickhide');
    });
</script>

<?php


$page->displayFooter();

?>