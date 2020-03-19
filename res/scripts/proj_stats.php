<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

$proj_id = intval($_GET['id']);
$status = (array_key_exists('all', $_GET)) ? 'status=status' : 'status>1 AND status<4';

$mydata = new myQuery(
    "SELECT COUNT(DISTINCT session.id) as people,
            COUNT(DISTINCT user_id) as peopled,
            COUNT(DISTINCT IF(sex='male',session.id,NULL)) as men,
            COUNT(DISTINCT IF(sex='female',session.id,NULL)) as women,
            COUNT(DISTINCT IF(sex='male',user_id,NULL)) as mend,
            COUNT(DISTINCT IF(sex='female',user_id,NULL)) as womend
       FROM session 
  LEFT JOIN user USING (user_id)
      WHERE project_id = {$proj_id} AND {$status} AND session.id IS NOT NULL"
);
    if ($mydata->get_num_rows() == 0) {
        $data = array('people' => 0, 'men' => 0, 'women' => 0, 
                      'peopled' => 0, 'mend' => 0, 'womend' => 0);
    } else {
        $data = $mydata->get_one_array();
    }

scriptReturn($data);
?>