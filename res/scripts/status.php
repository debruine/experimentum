<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('researcher', 'admin'));

$type = $_POST['type'];
$id = intval($_POST['id']);
$status = $_POST['status'];

if (!in_array($type, array('exp','quest','project','sets'))) {
    echo "Incorrect item type '{$type}'";
    exit;
} else if (!in_array($status, array('active','inactive','test'))) {
    echo "Incorrect status '{$status}'";
    exit;
}

// if a researcher, check if they have access to this one
if ($_SESSION['status'] == 'researcher') {
    if ($status == 'active') {
        echo "You do not have permission to change the status of {$type}_{$id} to active, please ask an admin.";
        exit;
    }
    
    $query = new myQuery("SELECT user_id FROM access WHERE `type`='{$type}' AND id={$id} AND user_id={$_SESSION['user_id']}");
    if ($query->get_num_rows() == 0) {
        echo "You do not have permission to change the status of {$type}_{$id}.";
        exit;
    }
}

$query = new myQuery("UPDATE {$type} SET status='{$status}' WHERE id={$id}");

if ($query->get_affected_rows() == 0 ) {
    echo "Status  of {$type}_{$id} not changed";
} else {
    echo "Status of {$type}_{$id} changed to {$status}";
}

?>