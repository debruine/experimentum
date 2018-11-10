<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

function getExp($id) {
    $q = new myQuery("SELECT * FROM exp WHERE id = {$id}");
    $tabledata = $q->get_row();

    // trial data
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
    
    // buttons
    if ($tabledata['exptype'] == "buttons") {
        $q = new myQuery("SELECT * from buttons WHERE exp_id = {$id}");
        $tabledata['buttons'] = $q->get_assoc();
    }
    
    return($tabledata);
}

function getQuest($id) {
    $q = new myQuery("SELECT * FROM quest WHERE id = {$id}");
    $tabledata = $q->get_row();
    
    $q = new myQuery("SELECT * from question WHERE quest_id = {$id}");
    $tabledata['question'] = $q->get_assoc();
    
    // options
    foreach ($tabledata['question'] as $i => $question) {
        $q = new myQuery("SELECT opt_order, opt_value, display from options WHERE q_id = {$question['id']}");
        if ($q->get_num_rows() > 0) {
            $tabledata['question'][$i]['options'] = $q->get_assoc();
        }
    }
    
    if ($tabledata['questtype'] == 'radiopage') {
        $q = new myQuery("SELECT opt_order, opt_value, display from radiorow_options WHERE quest_id = {$id} ORDER BY opt_order");
        $tabledata['radiorow_options'] = $q->get_assoc();
    }
    
    return($tabledata);
}

function getSet($id) {
    $q = new myQuery("SELECT * FROM sets WHERE id = {$id}");
    $tabledata = $q->get_row();
    
    $q = new myQuery("SELECT * from set_items WHERE set_id = {$id}");
    $items = $q->get_assoc();
    
    $tabledata['set_items'] = array();
    
    foreach ($items as $item) {
        if ($item['item_type'] == "exp") {
            $tabledata['set_items'][$item['item_n']] = getExp($item['item_id']);
        } else if ($item['item_type'] == "quest") {
            $tabledata['set_items'][$item['item_n']] = getQuest($item['item_id']);
        } else if ($item['item_type'] == "sets") {
            $tabledata['set_items'][$item['item_n']] = getSet($item['item_id']);
        }
    }
    
    return($tabledata);
}

function getProject($id) {
    $q = new myQuery("SELECT * FROM project WHERE id = {$id}");
    $tabledata = $q->get_row();
    
    $q = new myQuery("SELECT * from project_items WHERE project_id = {$id}");
    $items = $q->get_assoc();
    
    $tabledata['project_items'] = array();
    
    foreach ($items as $item) {
        if ($item['item_type'] == "exp") {
            $tabledata['project_items'][$item['item_n']] = getExp($item['item_id']);
        } else if ($item['item_type'] == "quest") {
            $tabledata['project_items'][$item['item_n']] = getQuest($item['item_id']);
        } else if ($item['item_type'] == "sets") {
            $tabledata['project_items'][$item['item_n']] = getSet($item['item_id']);
        }
        
        $tabledata['project_items'][$item['item_n']]['icon'] = $item['icon'];
    }
    
    return($tabledata);
}

$table = in_array($_POST['table'], array('exp', 'quest', 'sets', 'project')) ?
    $_POST['table'] : "exp";
    
$id = validID($_POST['id']) ? $_POST['id'] : 1;

if ($table == "exp") {
    $tabledata = getExp($id);
} else if ($table == "quest") {
    $tabledata = getQuest($id);
} else if ($table == "sets") {
    $tabledata = getSet($id);
} else if ($table == "project") {
    $tabledata = getProject($id);
}

$filename = $_POST['table'] . "_" . $_POST['id'] . "_structure.json";

header("Content-Disposition: attachment; filename=\"$filename\"");
header('Content-Type: application/json');
echo json_encode($tabledata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);

?>