<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin','super'), '/res/');

$return = array('error' => false);

$query = new myQuery();
/*
if ($_POST['supervisor_id'] == "") {
    $query->prepare(
        "DELETE FROM supervise WHERE supervisee_id = ?",
        array('i', $_POST['supervisee_id'])
    );
} else {
    $query->prepare(
        "INSERT INTO supervise (supervisor_id, supervisee_id) VALUES (?, ?)",
        array('ii', $_POST['supervisor_id'], $_POST['supervisee_id'])
    );
}
*/

$query->prepare(
    "UPDATE res SET supervisor_id = ? WHERE user_id = ?",
    array('ii', $_POST['supervisor_id'], $_POST['supervisee_id'])
);

if ($query->get_affected_rows() == 0 ) {
    $return['error'] = "Supervisor not changed";
}

scriptReturn($return);

?>