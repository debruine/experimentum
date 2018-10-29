<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('res', 'admin'));

$type = $_POST['type'];
$id = intval($_POST['id']);

if (!in_array($type, array('exp','quest','project','sets'))) {
    echo "Incorrect type";
    exit;
}

// if a researcher, check if they have access to this one
if ($_SESSION['status'] == 'res') {
    $query = new myQuery("SELECT user_id FROM access WHERE `type`='{$type}' AND id={$id} AND user_id={$_SESSION['user_id']}");
    if ($query->get_num_rows() == 0) {
        echo "You do not have permission to change owners.";
        exit;
    }
}

foreach($_POST['add'] as $user_id) {
    $user_id = intval($user_id);
    $query = new myQuery("REPLACE INTO access VALUES ('{$type}', {$id}, {$user_id})");
}
if (count($_POST['delete'])) {
    $del_ids = implode(",", $_POST['delete']);
    $query = new myQuery("DELETE FROM access WHERE `type`='{$type}' AND id={$id} AND user_id IN({$del_ids})");
}
   
exit;

?>