<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

$return = array(
    'error' => false
);

// exit if no permission
if (!permit($_POST['type'], $_POST['id'])) {
    $return['error'] = "You are not authorised to delete this item.";
    scriptReturn($return);
    exit;
}

$id = $_POST['id'];
$type = $_POST['type'];

if ($type == "exp") {
    $query = new myQuery('SELECT COUNT(DISTINCT user_id) as c 
                          FROM exp_data 
                          LEFT JOIN user USING (user_id) 
                          WHERE exp_id = '.$id.' AND status IN ("guest", "registered")');
    $non_researchers = $query->get_one();
    
    if ($non_researchers == 0) {
        // no non-researchers have completed the experiment, so OK to delete
        $query = new myQuery('DELETE FROM exp WHERE id=' . $id);
        $query = new myQuery('DELETE FROM exp_data WHERE exp_id=' . $id);
        $query = new myQuery('DELETE FROM trial WHERE exp_id=' . $id);
        $query = new myQuery('DELETE FROM adapt_trial WHERE exp_id=' . $id);
        $query = new myQuery('DELETE FROM buttons WHERE exp_id=' . $id);
        $query = new myQuery('DELETE FROM xafc WHERE exp_id=' . $id);
        $query = new myQuery('DELETE FROM versions WHERE exp_id=' . $id);
        $query = new myQuery('DELETE FROM dashboard WHERE type="exp" AND id=' . $id);
        $query = new myQuery('DELETE FROM access WHERE type="exp" AND id=' . $id);
        $query = new myQuery('DELETE FROM set_items WHERE item_type="exp" AND item_id=' . $id);
        $query = new myQuery('DELETE FROM project_items WHERE item_type="exp" AND item_id=' . $id);
    } else {
        // there is data from non-researchers
        $return['error'] = "There is data from $non_researchers non-researchers, so this experiment has not been deleted.";
    }
} else if ($type == "quest") {
    $query = new myQuery('SELECT COUNT(DISTINCT user_id) as c 
                          FROM quest_data 
                          LEFT JOIN user USING (user_id) 
                          WHERE quest_id='.$id.' AND status IN ("guest", "registered")');
    $non_researchers = $query->get_one();
    
    if ($non_researchers == 0) {
        // no non-researchers have completed the experiment, so OK to delete
        $query = new myQuery('DELETE FROM quest WHERE id=' . $id);
        $query = new myQuery('DELETE FROM quest_data WHERE quest_id=' . $id);
        $query = new myQuery('DELETE FROM question WHERE quest_id=' . $id);
        $query = new myQuery('DELETE FROM options WHERE quest_id=' . $id);
        $query = new myQuery('DELETE FROM radiorow_options WHERE quest_id=' . $id);
        $query = new myQuery('DELETE FROM dashboard WHERE type="quest" AND id=' . $id);
        $query = new myQuery('DELETE FROM access WHERE type="quest" AND id=' . $id);
        $query = new myQuery('DELETE FROM set_items WHERE item_type="quest" AND item_id=' . $id);
        $query = new myQuery('DELETE FROM project_items WHERE item_type="quest" AND item_id=' . $id);
    } else {
        // there is data from non-researchers
        $return['error'] =  "There is data from $non_researchers non-researchers, so this questionnaire has not been deleted.";
    }
} else if ($type == "sets") {
    $query = new myQuery("DELETE FROM sets WHERE id=$id");
    $query = new myQuery("DELETE FROM set_items WHERE item_type='set' AND item_id=$id");
    $query = new myQuery("DELETE FROM access WHERE type='sets' AND id=$id");
    $query = new myQuery("DELETE FROM dashboard WHERE type='set' AND id=$id");
    $query = new myQuery("DELETE FROM set_items WHERE set_id=$id");
} else if ($type == "project") {
    $query = new myQuery("DELETE FROM project WHERE id=$id");
    $query = new myQuery("DELETE FROM access WHERE type='project' AND id=$id");
    $query = new myQuery("DELETE FROM dashboard WHERE type='project' AND id=$id");
    $query = new myQuery("DELETE FROM project_items WHERE project_id=$id");
} else {
    $return['error'] = "The type {$type} is not valid";
}

scriptReturn($return);

?>