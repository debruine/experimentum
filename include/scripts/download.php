<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

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

# filename for download 
$filename = ifEmpty($_POST['name'], 'data'); 
$filename .= ($_POST['csv'] == 'true') ? ".csv" : ".txt"; 

# check that everything went OK

if (!empty($data)) {

    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Content-Type: text/plain");  
    
    $flag = false; 
    $sep = ($_POST['csv'] == 'true') ? "," : "\t";
    
    foreach($data as $row) {
        if(!$flag) {
            # display field/column names as first row 
            echo implode($sep, array_keys($row)) . "\n"; 
            $flag = true; 
        } 
        
        array_walk($row, 'cleanDataForExcel'); 
        echo implode($sep, array_values($row)) . "\n"; 
    }
}

exit;
    
?>