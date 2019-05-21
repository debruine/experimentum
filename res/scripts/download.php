<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS, "/res/");

// exit if no permission
if (!permit($_POST['type'], $_POST['id'])) exit;

$id = $_POST['id'];
$type = $_POST['type'];

// record download
$query  = new myQuery("INSERT INTO downloads (user_id, type, id, dt) VALUES
                        ({$_SESSION['user_id']}, '{$type}', {$id}, NOW())");

if ($type == 'exp') {
    $query = new myQuery('SELECT res_name, exptype, subtype FROM exp WHERE id=' . $id);
    $fname = $query->get_one_array();
        
    $q = 'SELECT session_id, project_id, exp_id, user_id, sex as user_sex, status as user_status,
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
            LEFT JOIN session ON session.id = session_id
            WHERE exp_id = ' . $id;
} else if ($type == 'quest') {
    $query = new myQuery('SELECT res_name FROM quest WHERE id=' . $id);
    $fname = $query->get_one_array();
    
    $q = 'SELECT session_id, project_id, qd.quest_id, user_id, sex as user_sex, status as user_status,
            ROUND(DATEDIFF(endtime, REPLACE(birthday, "-00","-01"))/365.25, 1) AS user_age,
            question.name as q_name,
            question_id as q_id,
            `order`,
            dv,
            starttime, endtime
            FROM quest_data AS qd
            LEFT JOIN user USING (user_id)
            LEFT JOIN question ON qd.quest_id = question.quest_id AND question_id = question.id
            LEFT JOIN session ON session.id = session_id
            WHERE qd.quest_id = ' . $id;
} else if ($type == 'project' && $_POST['download'] == 'quest') {
    $query = new myQuery('SELECT CONCAT(res_name, "_quests") AS res_name FROM project WHERE id=' . $id);
    $fname = $query->get_one_array();
    
    $q = 'SELECT session_id, project_id, qd.quest_id, session.user_id, sex as user_sex, status as user_status,
            ROUND(DATEDIFF(endtime, REPLACE(birthday, "-00","-01"))/365.25, 1) AS user_age,
            question.name as q_name,
            question_id as q_id,
            `order`,
            dv,
            starttime, endtime
            FROM session 
            LEFT JOIN user USING (user_id)
            LEFT JOIN quest_data AS qd ON qd.session_id = session.id
            LEFT JOIN question ON qd.quest_id = question.quest_id AND question_id = question.id
            WHERE session.project_id = ' . $id;
} else if ($type == 'project' && $_POST['download'] == 'exp') {
    $query = new myQuery('SELECT CONCAT(res_name, "_exps") AS res_name FROM project WHERE id=' . $id);
    $fname = $query->get_one_array();
    
    $q = 'SELECT session_id, project_id, exp_id, session.user_id, sex as user_sex, status as user_status,
            ROUND(DATEDIFF(ed.dt, REPLACE(birthday, "-00","-01"))/365.25, 1) AS user_age,
            trial.name as trial_name,
            trial_n,
            `order`,
            dv,
            rt,
            side,
            ed.dt
            FROM session 
            LEFT JOIN user USING (user_id)
            LEFT JOIN exp_data AS ed ON ed.session_id = session.id
            LEFT JOIN trial USING (exp_id, trial_n)
            WHERE session.project_id = ' . $id;
} else {
    exit;
}

$filename = preg_replace('/[^a-zA-Z0-9]+/', '-', $fname['res_name']) . '_' . date('Y-m-d') . '.csv';

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