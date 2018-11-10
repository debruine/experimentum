<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

$return = array(
    'error' => false,
    'new_id' => NULL
);

$old_id = $_POST['id'];

if (!validID($old_id)) {
    $return['error'] = "The ID was not valid";
    scriptReturn($return);
    exit;
}

if (!permit('exp', $old_id)) {
    $return['error'] = "You are not authorised to duplicate this experiment.";
    scriptReturn($return);
    exit;
}

// duplicate exp table entry
$q = new myQuery('SELECT * FROM exp WHERE id=' . $old_id);
$old_info = $q->get_one_array();
unset($old_info['id']);
unset($old_info['res_name']);
unset($old_info['status']);
unset($old_info['create_date']);
$fields = array_keys($old_info);

$query = sprintf("INSERT INTO exp (create_date, status, res_name, %s) 
    SELECT NOW(), 'test', CONCAT(res_name, ' (Duplicate)'), %s 
    FROM exp WHERE id='%d'",
    implode(", ", $fields),
    implode(", ", $fields),
    $old_id
);
$q = new myQuery($query);
$new_id = $q->get_insert_id();

if (!validID($new_id)) {
    $return['error'] = "The experiment did not duplicate.";
    $return['query'] = $query;
    scriptReturn($return);
    exit;
}

$q = new myQuery("UPDATE exp SET feedback_query=REPLACE(feedback_query, 'exp_{$old_id}', 'exp_{$new_id}') WHERE id='{$new_id}'");

// duplicate tables
duplicateTable("trial", 'exp', $old_id, $new_id);
duplicateTable("adapt_trial", 'exp', $old_id, $new_id);
duplicateTable("xafc", 'exp', $old_id, $new_id);
duplicateTable("buttons", 'exp', $old_id, $new_id);

// set owner/access
$q = new myQuery("INSERT INTO access (type, id, user_id) VALUES ('exp', $new_id, {$_SESSION['user_id']})");

$return['new_id'] = $new_id;

scriptReturn($return);
exit;

?>
