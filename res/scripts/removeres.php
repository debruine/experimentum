<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin', 'super', 'res'), '/res/');

$return = array('error' => false);

$q = new myQuery();
$q->prepare("SELECT status FROM res LEFT JOIN user USING (user_id) WHERE user_id = ?", array('i', $_POST['supervisee_id']));
$status = $q->get_one();

if (in_array($status, $RES_STATUS)) {
    $return['error'] = "Users with status {$status} cannot be removed from the researcher table";
} else {
    // remove the user rom the res table
    $q->prepare("DELETE FROM res WHERE user_id = ?", array('i', $_POST['supervisee_id']));
    
    if ($q->get_affected_rows() == 0) {
        $return['error'] = "The supervisee could not be found";
    }
}

scriptReturn($return);

?>