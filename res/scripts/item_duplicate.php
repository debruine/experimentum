<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

$return = array(
    'error' => false,
    'new_id' => NULL
);

// exit if no permission
if (!permit($_POST['type'], $_POST['id'])) {
    $return['error'] = "You are not authorised to duplicate this item.";
    scriptReturn($return);
    exit;
}

$old_id = $_POST['id'];
$type = $_POST['type'];
if ($type=='set') $type = 'sets';

if ($type == "exp") {
    $q = new myQuery('SELECT * FROM exp WHERE id=' . $old_id);
    $old_info = $q->get_one_array();
    unset($old_info['id']);
    unset($old_info['res_name']);
    unset($old_info['status']);
    unset($old_info['create_date']);
    $fields = array_keys($old_info);
    
    $query = sprintf("INSERT INTO exp (create_date, status, res_name, %s) 
        SELECT NOW(), 'test', CONCAT(res_name, ' (Duplicate)'), %s 
        FROM exp WHERE id='%d'",
        implode(", ", $fields),
        implode(", ", $fields),
        $old_id
    );
    $q = new myQuery($query);
    $new_id = $q->get_insert_id();
    
    if (!validID($new_id)) {
        $return['error'] = "The experiment did not duplicate.";
        $return['query'] = $query;
        scriptReturn($return);
        exit;
    }
    
    duplicateTable("trial", 'exp', $old_id, $new_id);
    duplicateTable("adapt_trial", 'exp', $old_id, $new_id);
    duplicateTable("xafc", 'exp', $old_id, $new_id);
    duplicateTable("buttons", 'exp', $old_id, $new_id);
    
    $return['new_id'] = $new_id;

} else if ($type == "quest") {
    $q = new myQuery('SELECT * FROM quest WHERE id=' . $old_id);
    $old_info = $q->get_one_array();
    unset($old_info['id']);
    unset($old_info['res_name']);
    unset($old_info['status']);
    unset($old_info['create_date']);
    $fields = array_keys($old_info);
    
    $query = sprintf("INSERT INTO quest (create_date, status, res_name, %s) 
        SELECT NOW(), 'test', CONCAT(res_name, ' (Duplicate)'), %s 
        FROM quest WHERE id='%d'",
        implode(", ", $fields),
        implode(", ", $fields),
        $old_id
    );
    $q = new myQuery($query);
    $new_id = $q->get_insert_id();
    
    if (!validID($new_id)) {
        $return['error'] = "The questionnaire did not duplicate.";
        $return['query'] = $query;
        scriptReturn($return);
        exit;
    }
        
    $q = new myQuery("SELECT * FROM question WHERE quest_id={$old_id}");
    $questions = $q->get_assoc();
    if (count($questions) > 0) {
        // get fields for question table
        $fields = array_keys($questions[0]);
        $fields = array_diff($fields, array('quest_id', 'id'));
        
        // get fields for options table
        $q = new myQuery("SELECT * FROM options LIMIT 1");
        $options = $q->get_one_array();
        unset($options['q_id']);
        unset($options['quest_id']);
        $option_fields = array_keys($options);
        
        // set array for translating old to new
        $old_to_new = array();
        
        // replace each question and set associated options
        foreach ($questions as $question) {
            $old_qid = $question['id'];
        
            $query = sprintf("INSERT INTO question (quest_id, %s) 
                SELECT %d, %s 
                FROM question WHERE id='%d' AND quest_id='%d'",
                implode(", ", $fields),
                $new_id,
                implode(", ", $fields),
                $old_qid,
                $old_id
            );
            $q = new myQuery($query);
            $new_qid = $q->get_insert_id();
            
            $old_to_new['q' . $old_qid] = 'q' . $new_qid;
            
            $query = sprintf("INSERT INTO options (q_id, quest_id, %s) 
                SELECT %d, %d, %s 
                FROM options WHERE q_id='%d' AND quest_id='%d'",
                implode(", ", $option_fields),
                $new_qid,
                $new_id,
                implode(", ", $option_fields),
                $old_qid,
                $old_id
            );
            $q = new myQuery($query);
        }
    }
    
    duplicateTable("radiorow_options", 'quest', $old_id, $new_id);
    
    $return['new_id'] = $new_id;
    
} else if ($type == "sets") {
    $q = new myQuery('SELECT * FROM sets WHERE id=' . $old_id);
    $old_info = $q->get_one_array();
    unset($old_info['id']);
    unset($old_info['res_name']);
    unset($old_info['status']);
    unset($old_info['create_date']);
    $fields = array_keys($old_info);
    
    $query = sprintf("INSERT INTO sets (create_date, status, res_name, %s) 
        SELECT NOW(), 'test', CONCAT(res_name, ' (Duplicate)'), %s 
        FROM sets WHERE id='%d'",
        implode(", ", $fields),
        implode(", ", $fields),
        $old_id
    );
    $q = new myQuery($query);
    $new_id = $q->get_insert_id();
    
    if (!validID($new_id)) {
        $return['error'] = "The set did not duplicate.";
        $return['query'] = $query;
        scriptReturn($return);
        exit;
    }
    
    duplicateTable("set_items", 'set', $old_id, $new_id);
    
    $return['new_id'] = $new_id;

} else if ($type == "project") {
    $q = new myQuery('SELECT * FROM project WHERE id=' . $old_id);
    $old_info = $q->get_one_array();
    unset($old_info['id']);
    unset($old_info['res_name']);
    unset($old_info['url']);
    unset($old_info['status']);
    unset($old_info['create_date']);
    $fields = array_keys($old_info);
    
    $query = sprintf("INSERT INTO project (create_date, status, res_name, url, %s) 
        SELECT NOW(), 'test', CONCAT(res_name, ' (Duplicate)'), CONCAT(url, '_duplicate'), %s 
        FROM project WHERE id='%d'",
        implode(", ", $fields),
        implode(", ", $fields),
        $old_id
    );
    $q = new myQuery($query);
    $new_id = $q->get_insert_id();
    
    if (!validID($new_id)) {
        $return['error'] = "The project did not duplicate.";
        $return['query'] = $query;
        scriptReturn($return);
        exit;
    }
    
    duplicateTable("project_items", 'project', $old_id, $new_id);
    
    $return['new_id'] = $new_id;
}

// set owner/access
if ($return['new_id']) {
    $q = new myQuery("INSERT INTO access (type, id, user_id) VALUES ('$type', $new_id, {$_SESSION['user_id']})");
}

scriptReturn($return);

?>