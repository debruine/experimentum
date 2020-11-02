<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

$return = array('error' => false);

$proj_id = intval($_GET['project_id']);

if (validID($proj_id) && !permit('project', $proj_id)) {
    $return['error'] = loc("You are not authorised to access this project.");
    scriptReturn($return);
    exit;
}

$q = new myQuery();

$q->prepare('SELECT user.status as status, count(*) as n 
               FROM session 
          LEFT JOIN user USING (user_id)
              WHERE project_id = ?
           GROUP BY user.status', 
              array('i', $proj_id));

$return['users'] = $q->get_key_val('status', 'n');

scriptReturn($return);
exit;
    
?>
