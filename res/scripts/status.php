<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('res', 'admin'));

$return = array('error' => false);

$type = $_POST['type'];
$id = intval($_POST['id']);
$status = $_POST['status'];

if (!in_array($type, array('exp','quest','project','sets'))) {
    $return['error'] = "Incorrect item type: '{$type}'";
    scriptReturn($return);
    exit;
} else if (!in_array($status, array('active','archive','test'))) {
    $return['error'] =  "Incorrect status: '{$status}'";
    scriptReturn($return);
    exit;
}

// if a researcher, check if they have access to this one
if ($_SESSION['status'] == 'res') {
    if ($status == 'active') {
        $return['error'] =  "You do not have permission to change the status of {$type}_{$id} to active, please ask an admin.";
        scriptReturn($return);
        exit;
    }
    
    $query = new myQuery("SELECT user_id FROM access WHERE `type`='{$type}' AND id={$id} AND user_id={$_SESSION['user_id']}");
    if ($query->get_num_rows() == 0) {
        $return['error'] =  "You do not have permission to change the status of {$type}_{$id}.";
        scriptReturn($return);
        exit;
    }
}

$query = new myQuery("UPDATE {$type} SET status='{$status}' WHERE id={$id}");

scriptReturn($return);
exit;

?>