<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

$return = array('error' => false);

$proj_id = intval($_POST['project_id']);

if (validID($proj_id) && !permit('project', $proj_id)) {
    $return['error'] = "You are not authorised to delete test data for this project.";
    scriptReturn($return);
    exit;
}

// delete test and researcher data from the project
$q = new myQuery();

$q->prepare('DELETE exp_data
               FROM exp_data  
          LEFT JOIN user USING (user_id)
          LEFT JOIN session ON (session.id = session_id)
              WHERE project_id = ?
                AND user.status IN ("test", "student", "res", "super", "admin")', 
              array('i', $proj_id));
              
$return['exp_del'] = $q->get_affected_rows();

$q->prepare('DELETE quest_data
               FROM quest_data  
          LEFT JOIN user USING (user_id)
          LEFT JOIN session ON (session.id = session_id)
              WHERE project_id = ?
                AND user.status IN ("test", "student", "res", "super", "admin")', 
              array('i', $proj_id));
              
$return['quest_del'] = $q->get_affected_rows();

// have to do this last, or links between session and project IDs are lost
$q->prepare('DELETE session
               FROM session 
          LEFT JOIN user USING (user_id)
              WHERE project_id = ?
                AND user.status IN ("test", "student", "res", "super", "admin")', 
              array('i', $proj_id));

$return['sess_del'] = $q->get_affected_rows();

scriptReturn($return);
exit;
    
?>
