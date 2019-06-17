<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('res', 'admin'));

$return = array('error' => false);

$id = intval($_POST['id']);
$status = $_POST['status'];

if (!in_array($status, $ALL_STATUS)) {
    echo "Incorrect status: '{$status}'";
    scriptReturn($return);
    exit;
}

$query = new myQuery("SELECT status FROM user WHERE user_id={$id}");
$oldstatus = $query->get_one();

// if a researcher, check if they have access to this one
if ($_SESSION['status'] == 'res') {
    if ($oldstatus == 'admin' || $oldstatus == 'res') {
        $return['error'] = "You do not have permission to change the status of a {$oldstatus}.";
        scriptReturn($return);
        exit;
    }
}

if ($_SESSION['status'] == 'res') {
    if ($status == 'admin' || $status == 'res') {
        $return['error'] = "You do not have permission to change a status to {$status}. Please ask an administrator.";
        scriptReturn($return);
        exit;
    }
}

// changing to non-researcher status, check if they still have access to studies
if (!in_array($status, $RES_STATUS)) {
    $query = new myQuery("SELECT CONCAT(type, '_', id) as items FROM access WHERE user_id={$id}");
    
    if ($query->get_num_rows() == 0) {
        //$query = new myQuery("DELETE FROM res WHERE user_id={$id}");
    } else {
        $items = $query->get_col('items');
        $return['error'] = '<p>This user still has access to ' . count($items) . ' items (' . implode(", ", $items) . 
                ')</p> <p>You need to remove their access before you can change their status to a non-researcher status.<p>';
        scriptReturn($return);
        exit;
    }
}

$query = new myQuery("UPDATE user SET status='{$status}' WHERE user_id={$id}");

if ($query->get_affected_rows() == 0 ) {
    $return['error'] = "Status  of user {$id} not changed";
}

scriptReturn($return);

?>