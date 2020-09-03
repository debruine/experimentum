<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(0);

// save data and return nextpage
$clean = my_clean($_POST);
$endtime = date('Y-m-d H:i:s');
$order = array_flip(explode(",", $clean['order']));

// start project session id if not started
/*
if (empty($_SESSION['session_id'])) {
    $q = new myQuery();
    $q->prepare("INSERT INTO session (project_id, user_id, dt) VALUES (?, ?, ?)",
                array('iis',
                      $_SESSION['project_id'],
                      $_SESSION['user_id'],
                      $clean['starttime'] //$endtime
                )
    );
    $_SESSION['session_id'] = $q->get_insert_id();
}
*/

// record data in quest_data
foreach ($_POST as $qu => $a) {
    if (substr($qu, 0, 1) == 'q' && is_numeric(substr($qu, 1))) {
        $query = new myQuery();
        $query->prepare(
            "INSERT INTO quest_data (quest_id, user_id, session_id, question_id, dv, `order`, starttime, endtime) 
             VALUES(?, ?, ?, ?, ?, ?, ?, ?)",
             array(
                 'iiiisiss',
                 $clean['quest_id'],
                 $_SESSION['user_id'],
                 $_SESSION['session_id'],
                 str_replace('q', '', $qu),
                 $a,
                 $order[$qu] + 1,
                 $clean['starttime'],
                 $endtime
             )
        );
    }
}

// send to feedback page
echo 'url;/fb?type=quest&id=' . $clean['quest_id'];

exit;

?>