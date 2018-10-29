<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('res', 'admin'));

$id = intval($_POST['id']);
$status = $_POST['status'];

if (!in_array($status, $ALL_STATUS)) {
    echo "Incorrect status: '{$status}'";
    exit;
}

$query = new myQuery("SELECT status FROM user WHERE user_id={$id}");
$oldstatus = $query->get_one();

// if a researcher, check if they have access to this one
if ($_SESSION['status'] == 'res') {
    if ($oldstatus == 'admin' || $oldstatus == 'res') {
        echo "You do not have permission to change the status of a {$oldstatus}.";
        exit;
    }
}

// changing to non-researcher status, check if they still have access to studies
if (!in_array($status, $RES_STATUS)) {
    $query = new myQuery("SELECT CONCAT(type, '_', id) as items FROM access WHERE user_id={$id}");
    
    if ($query->get_num_rows() == 0) {
        $query = new myQuery("DELETE FROM res WHERE user_id={$id}");
    } else {
        $items = $query->get_col('items');
        echo '<p>This user still has access to ' . count($items) . ' items (' . implode(", ", $items) . 
                ')</p> <p>You need to remove their access before you can change their status to a non-researcher status.<p>';
        exit;
    }
}

$query = new myQuery("UPDATE user SET status='{$status}' WHERE user_id={$id}");

if ($query->get_affected_rows() == 0 ) {
    echo "Status  of user {$id} not changed";
} else {
    echo "Status of user {$id} changed to {$status}";
}

?>