<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS, "/res/");

// exit if post data are invalid
if (!array_key_exists('query_type', $_POST) || 
    !in_array($_POST['query_type'], array('exp', 'quest')) || 
    !validID($_POST['query_id'])) {
    exit;
}

if ($_POST['query_type'] == 'exp') {
    $query = new myQuery('SELECT res_name, exptype, subtype FROM exp WHERE id=' . $_POST['query_id']);
    $exp = $query->get_one_array();
    
    $filename = preg_replace('/[^a-zA-Z0-9]+/', '-', $exp['res_name']) . '_' . date('Y-m-d') . '.csv';
    
    $q = 'SELECT session_id, exp_id, version, user_id, sex as user_sex, status as user_status,
            ROUND(DATEDIFF(dt, REPLACE(birthday, "-00","-01"))/365.25, 1) AS user_age,
            trial.name as trial_name,
            trial_n,
            `order`,
            dv,
            rt,
            side,
            dt
            FROM exp_data AS ed
            LEFT JOIN user USING (user_id)
            LEFT JOIN trial USING (exp_id, trial_n)
            WHERE exp_id = ' . $_POST['query_id'];
} else if ($_POST['query_type'] == 'quest') {
    $query = new myQuery('SELECT res_name FROM quest WHERE id=' . $_POST['query_id']);
    $quest = $query->get_one_array();

    $filename = preg_replace('/[^a-zA-Z0-9]+/', '-', $quest['res_name']) . '_' . date('Y-m-d') . '.csv';
    
    $q = 'SELECT session_id, qd.quest_id, user_id, sex as user_sex, status as user_status,
            ROUND(DATEDIFF(endtime, REPLACE(birthday, "-00","-01"))/365.25, 1) AS user_age,
            question.name as q_name,
            question_id as q_id,
            `order`,
            dv,
            starttime, endtime
            FROM quest_data AS qd
            LEFT JOIN user USING (user_id)
            LEFT JOIN question ON qd.quest_id = question.quest_id AND question_id = question.id
            WHERE qd.quest_id = ' . $_POST['query_id'];
}

$query = new myQuery($q, true);

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
    
?>