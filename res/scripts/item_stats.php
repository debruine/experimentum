<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

$proj_id = (array_key_exists('proj', $_POST)) ? intval($_POST['proj']) : 'project_id';
$status = ($_POST['all'] == "true") ? 'status=status' : 'user.status IN ("registered", "guest")';

// get stats on participant completion of the experiment
if (substr($_POST['item'],0,4) == "exp_") {
     $exp_id = intval(substr($_POST['item'],4));
     $equery = new myQuery('SELECT subtype, random_stim FROM exp WHERE id=' . $exp_id);
     $einfo = $equery->get_assoc(0);
     
     $mydata = new myQuery(
        "CREATE TEMPORARY TABLE tmp_exp
                         SELECT COUNT(*) as n,
                                sex,
                                session.id as session_id,
                                user.user_id AS user_id
                           FROM exp_data 
                      LEFT JOIN user USING (user_id)
                      LEFT JOIN session ON session.id=exp_data.session_id
                          WHERE {$status} AND 
                                exp_id={$exp_id} AND 
                                project_id={$proj_id} AND
                                session.id IS NOT NULL
                       GROUP BY session.id, user.user_id
                         HAVING n >={$einfo['random_stim']}",
         
        "SELECT COUNT(DISTINCT session_id) as total_c,
                COUNT(DISTINCT user_id) as total_dist,
                COUNT(DISTINCT IF(sex='male',session_id,NULL)) as total_male,
                COUNT(DISTINCT IF(sex='female',session_id,NULL)) as total_female,
                COUNT(DISTINCT IF(sex='nonbinary',session_id,NULL)) as total_nb,
                COUNT(DISTINCT IF(sex='male',user_id,NULL)) as dist_male,
                COUNT(DISTINCT IF(sex='female',user_id,NULL)) as dist_female,
                COUNT(DISTINCT IF(sex='nonbinary',user_id,NULL)) as dist_nb
           FROM tmp_exp"
    );
    if ($mydata->get_num_rows() == 0) {
        $data = array('total_c' => 0, 'total_male' => 0, 'total_female' => 0, 'total_nb' => 0,
                      'total_dist' => 0, 'dist_male' => 0, 'dist_female' => 0, 'dist_nb' => 0);
    } else {
        $data = $mydata->get_one_array();
    }
    
    $mydata = new myQuery(array(
        "CREATE TEMPORARY TABLE tmp_ln
                         SELECT user_id, sex, 
                                COUNT(*) as n, 
                                AVG(rt) as val 
                           FROM exp_data
                      LEFT JOIN user USING (user_id) 
                          WHERE exp_id={$exp_id} AND {$status}
                       GROUP BY user_id
                         HAVING n >={$einfo['random_stim']}",

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
        $data['median'] = "No info";
        $data['upper'] = "No info";
    } else {
        $median_seconds = $mytime->get_one();
        $data['median']  = round(($median_seconds * $einfo['random_stim'])/1000/6)/10;
        
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
        $data['upper'] = round(($upper_seconds* $einfo['random_stim'])/1000/6)/10;
    }

    scriptReturn($data);
    exit;
}


// if not an experiment, then must be a questionnaire
$quest_id = intval(substr($_POST['item'],6));

$mydata = new myQuery(
    "SELECT COUNT(DISTINCT session.id) as total_c,
            COUNT(DISTINCT user.user_id) as total_dist,
            COUNT(DISTINCT IF(sex='male',session.id,NULL)) as total_male,
            COUNT(DISTINCT IF(sex='female',session.id,NULL)) as total_female,
            COUNT(DISTINCT IF(sex='nonbinary',session.id,NULL)) as total_nb,
            COUNT(DISTINCT IF(sex='male',user.user_id,NULL)) as dist_male,
            COUNT(DISTINCT IF(sex='female',user.user_id,NULL)) as dist_female,
            COUNT(DISTINCT IF(sex='nonbinary',user.user_id,NULL)) as dist_nb
       FROM quest_data 
  LEFT JOIN user USING (user_id)
  LEFT JOIN session ON session.id=quest_data.session_id
      WHERE {$status} AND 
            quest_id={$quest_id} AND 
            project_id={$proj_id} AND 
            session.id IS NOT NULL"
);

if ($mydata->get_num_rows() == 0) {
    $data = array('total_c' => 0, 'total_male' => 0, 'total_female' => 0, 'total_nb' => 0,
                  'total_dist' => 0, 'dist_male' => 0, 'dist_female' => 0, 'dist_nb' => 0);
} else {
    $data = $mydata->get_one_array();
}

$mydata = new myQuery(array(
    "CREATE TEMPORARY TABLE tmp_ln
                     SELECT user_id, sex, 
                            COUNT(*) as n, 
                            AVG(endtime-starttime) as val 
                       FROM quest_data
                  LEFT JOIN user USING (user_id) 
                      WHERE quest_id={$quest_id} AND {$status}
                   GROUP BY user_id, starttime, endtime",

    "CREATE TEMPORARY TABLE tmp_ln2
                     SELECT * 
                       FROM tmp_ln")
);

$mytime = new myQuery("SELECT t1.val as median_val FROM (
    SELECT @rownum:=@rownum+1 as `row_number`, val
      FROM tmp_ln AS d, (SELECT @rownum:=0) r
      WHERE val>0 AND val<3601
      ORDER BY val
    ) as t1, 
    (
      SELECT count(*) as total_rows
      FROM tmp_ln2 AS d
      WHERE val>0 AND val<3601
    ) as t2
    WHERE t1.row_number=floor(1*total_rows/2)+1;", true);
if ($mytime->get_num_rows() == 0) {
    $data['median'] = "No info";
    $data['upper'] = "No info";
} else {
    $median_seconds = $mytime->get_one();
    $data['median']  = round(($median_seconds)/6)/10;
    
    $mytime = new myQuery("SELECT t1.val as median_val FROM (
        SELECT @rownum:=@rownum+1 as `row_number`, val
          FROM tmp_ln AS d, (SELECT @rownum:=0) r
          WHERE val>0 AND val<3601
          ORDER BY val
        ) as t1, 
        (
          SELECT count(*) as total_rows
          FROM tmp_ln2 AS d
          WHERE val>0 AND val<3601
        ) as t2
        WHERE t1.row_number=floor(9*total_rows/10)+1;", true);
    $upper_seconds = $mytime->get_one();
    $data['upper'] = round(($upper_seconds)/6)/10;
}

scriptReturn($data);

?>