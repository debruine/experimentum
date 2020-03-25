<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
    auth(1);

    $id = intval($_POST['id']);
    $trial = intval($_POST['trial']);
    $order = $_POST['order'];
    $response = $_POST['response'];
    $side = $_POST['side'];
    $rt = $_POST['rt'];
    $starttime = date('Y-m-d H:i:s'); ;
    $exptype = $_POST['exptype'];
        
    if (is_array($side)) {
        // set up side
        $side = "'" . implode($side, ',') . "'";
    }

    // start project session id if not started
    if (empty($_SESSION['session_id'])) {
        $qtext = sprintf("INSERT INTO session (project_id, user_id, dt) VALUES (%d, %d, '%s')",
            $_SESSION['project_id'],
            $_SESSION['user_id'],
            $starttime
        );
        $q = new myQuery($qtext);
        $_SESSION['session_id'] = $q->get_insert_id();
    }
    

    $newq = sprintf("INSERT INTO exp_data 
                    (exp_id, user_id, session_id, trial_n, 
                    dv, rt, side, `order`, dt) VALUES 
                    (%d, %d, %d, %d, 
                    %s, %s, %s, %s, '%s')",
                    $id,
                    $_SESSION['user_id'],
                    $_SESSION['session_id'],
                    $trial,
                    $response == "null" ? 'NULL' : "'" . $response . "'",
                    ifEmpty($rt, 'NULL', true),
                    ifEmpty($side, 'NULL', true),
                    ifEmpty($order, 'NULL', true),
                    date('Y-m-d H:i:s')
    );
    $query = new myQuery($newq);
    
    if (array_key_exists('done', $_POST)) {
        echo '/fb?type=exp&id=' . $id;
    } else {
        // next trial
        //echo $query->get_query();
        echo  $_SESSION['session_id'];
    }
    
    exit;
?>