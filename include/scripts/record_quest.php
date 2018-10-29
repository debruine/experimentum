<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(1);

// save data and return nextpage
$clean = my_clean($_POST);
$endtime = date('Y-m-d H:i:s');
$order = array_flip(explode(",", $clean['order']));

// start project session id if not started
if (empty($_SESSION['session_id'])) {
    $qtext = sprintf("INSERT INTO session (project_id, user_id, dt) VALUES (%d, %d, '%s')",
        $_SESSION['project_id'],
        $_SESSION['user_id'],
        $endtime
    );
    $q = new myQuery($qtext);
    $_SESSION['session_id'] = $q->get_insert_id();
}

// record data in quest_data
foreach ($clean as $qu => $a) {
    if (substr($qu, 0, 1) == 'q' && is_numeric(substr($qu, 1))) {
        $q = sprintf('INSERT INTO quest_data (quest_id, user_id, session_id, question_id, dv, `order`, starttime, endtime) 
             VALUES(%d, %d, %d, %d, "%s", %d, "%s", "%s")',
             $clean['quest_id'],
             $_SESSION['user_id'],
             $_SESSION['session_id'],
             str_replace('q', '', $qu),
             $a,
             $order[$qu] + 1,
             $clean['starttime'],
             $endtime
        );
        $q = str_replace('"NULL"', 'NULL', $q);
        $query = new myQuery($q);
    }
}

// send to feedback page
echo 'url;/fb?type=quest&id=' . $clean['quest_id'];

exit;

?>