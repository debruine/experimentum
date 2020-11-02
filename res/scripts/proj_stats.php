<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

$proj_id = intval($_POST['id']);
$status = ($_POST['all'] == "true") ? 'status=status' : 'user.status IN ("registered", "guest")';

// get data from all starters
$mydata = new myQuery(
    "SELECT COUNT(DISTINCT session.id) as people,
            COUNT(DISTINCT user_id) as peopled,
            COUNT(DISTINCT IF(sex='male',session.id,NULL)) as men,
            COUNT(DISTINCT IF(sex='male',user_id,NULL)) as mend,
            COUNT(DISTINCT IF(sex='female',session.id,NULL)) as women,
            COUNT(DISTINCT IF(sex='female',user_id,NULL)) as womend,
            COUNT(DISTINCT IF(sex='nonbinary',session.id,NULL)) as nb,
            COUNT(DISTINCT IF(sex='nonbinary',user_id,NULL)) as nbd
       FROM session 
  LEFT JOIN user USING (user_id)
      WHERE project_id = {$proj_id} 
        AND {$status} 
        AND session.id IS NOT NULL"
);
    if ($mydata->get_num_rows() == 0) {
        $data = array('people' => 0, 'men' => 0, 'women' => 0, 'nb' => 0, 
                      'peopled' => 0, 'mend' => 0, 'womend' => 0, 'nbd' => 0);
    } else {
        $data = $mydata->get_one_array();
    }

// only data from completers   
$mydata = new myQuery(
    "SELECT COUNT(DISTINCT session.id) as people,
            COUNT(DISTINCT user_id) as peopled,
            COUNT(DISTINCT IF(sex='male',session.id,NULL)) as men,
            COUNT(DISTINCT IF(sex='female',session.id,NULL)) as women,
            COUNT(DISTINCT IF(sex='male',user_id,NULL)) as mend,
            COUNT(DISTINCT IF(sex='female',user_id,NULL)) as womend,
            COUNT(DISTINCT IF(sex='nonbinary',session.id,NULL)) as nb,
            COUNT(DISTINCT IF(sex='nonbinary',user_id,NULL)) as nbd
       FROM session 
  LEFT JOIN user USING (user_id)
      WHERE project_id = {$proj_id} 
        AND {$status} 
        AND session.id IS NOT NULL 
        AND endtime IS NOT NULL"
);
    if ($mydata->get_num_rows() == 0) {
        $compl = array('people' => 0, 'men' => 0, 'women' => 0, 'nb' => 0,
                      'peopled' => 0, 'mend' => 0, 'womend' => 0, 'nbd' => 0);
    } else {
        $compl = $mydata->get_one_array();
    }

// completion timings
$timings = array();
$mydata = new myQuery(array(
    "CREATE TEMPORARY TABLE tmp_ln
                     SELECT endtime - dt as val 
                       FROM session
                  LEFT JOIN user USING (user_id)
                      WHERE project_id = {$proj_id} 
                        AND {$status} 
                        AND session.id IS NOT NULL 
                        AND endtime IS NOT NULL",

    "CREATE TEMPORARY TABLE tmp_ln2
                     SELECT * 
                       FROM tmp_ln")
);

$mytime = new myQuery("SELECT t1.val as median_val FROM (
    SELECT @rownum:=@rownum+1 as `row_number`, val
      FROM tmp_ln AS d, (SELECT @rownum:=0) r
      WHERE val>0 AND val<360001
      ORDER BY val
    ) as t1, 
    (
      SELECT count(*) as total_rows
      FROM tmp_ln2 AS d
      WHERE val>0 AND val<360001
    ) as t2
    WHERE t1.row_number=floor(1*total_rows/2)+1;", true);
    
if ($mytime->get_num_rows() == 0) {
    $timings['median'] = "No info";
    $timings['upper'] = "No info";
} else {
    $median_seconds = $mytime->get_one();
    $timings['median']  = $median_seconds; #round($median_seconds/6)/10;
    
    $mytime = new myQuery("SELECT t1.val as median_val FROM (
        SELECT @rownum:=@rownum+1 as `row_number`, val
          FROM tmp_ln AS d, (SELECT @rownum:=0) r
          WHERE val>0 AND val<360001
          ORDER BY val
        ) as t1, 
        (
          SELECT count(*) as total_rows
          FROM tmp_ln2 AS d
          WHERE val>0 AND val<360001
        ) as t2
        WHERE t1.row_number=floor(9*total_rows/10)+1;", true);
    $upper_seconds = $mytime->get_one();
    $timings['upper'] = round($upper_seconds/6)/10;
}


scriptReturn(array('total' => $data, 'compl' => $compl, 'timings' => $timings));
?>