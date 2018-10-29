<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('res', 'admin'), "/res/");

if (validID($_GET['id'])) {
    $id = $_GET['id'];

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
        
        echo "deleted";
    } else {
        // there is data from non-researchers
        echo "There is data from $non_researchers non-researchers, so this experiment has not been deleted.";
    }
} else {
    echo "ID $id is not recognised.";
}

exit;

?>