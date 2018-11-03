<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

$table = in_array($_GET['table'], array('exp', 'quest', 'sets', 'project')) ?
    $_GET['table'] : "exp";
    
$id = validID($_GET['id']) ? $_GET['id'] : 1;

$query = "SELECT * FROM {$table} WHERE id = {$id}";

$q = new myQuery($query);

$tabledata = $q->get_row();

$subtables = array();
if ($table == "exp") {
    if ($tabledata['exptype'] == "xafc") {
        $q = new myQuery("SELECT * from xafc WHERE exp_id = {$id}");
        $tabledata['xafc'] = $q->get_assoc();
    } else {
        $q = new myQuery("SELECT * from trial WHERE exp_id = {$id}");
        $tabledata['trial'] = $q->get_assoc(false, 'trial_n');
        
        $q = new myQuery("SELECT id, path, type from (
            SELECT left_img as id FROM trial WHERE exp_id={$id} UNION 
            SELECT center_img as id from trial WHERE exp_id={$id} UNION 
            SELECT right_img as id from trial WHERE exp_id={$id}) as t 
            LEFT JOIN stimuli USING (id)
            WHERE id IS NOT NULL");
        $tabledata['stimuli'] = $q->get_assoc(false, 'id', 'path');
    }
    if ($tabledata['exptype'] == "buttons") {
        $q = new myQuery("SELECT * from buttons WHERE exp_id = {$id}");
        $tabledata['buttons'] = $q->get_assoc();
    }
}

if ($table == "quest") {
    $q = new myQuery("SELECT * from question WHERE quest_id = {$id}");
    $tabledata['question'] = $q->get_assoc();
    
    foreach ($tabledata['question'] as $i => $quest) {
        //if 
    }
}


scriptReturn($tabledata);

?>