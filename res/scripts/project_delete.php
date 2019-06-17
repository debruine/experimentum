<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

$return = array('error' => false);

if (validID($_POST['project_id']) && !permit('project', $_POST['project_id'])) {
    $return['error'] = "You are not authorised to delete this project.";
    scriptReturn($return);
    exit;
}

// delete the project
$q = new myQuery();
$q->prepare('DELETE FROM project WHERE id=?', array('i', $_POST['project_id']));
$q->prepare('DELETE FROM project_items WHERE project_id=?', array('i', $_POST['project_id']));
$q->prepare('DELETE FROM access WHERE type="project" AND id=?', array('i', $_POST['project_id']));
$q->prepare('DELETE FROM dashboard WHERE type="project" AND id=?', array('i', $_POST['project_id']));

scriptReturn($return);
exit;
    
?>
