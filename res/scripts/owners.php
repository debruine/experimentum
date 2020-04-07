<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin', 'super', 'res'), "/res/");

$type = $_POST['type'];
$id = intval($_POST['id']);

if (!in_array($type, array('exp','quest','project','sets', 'set'))) {
    echo "Incorrect type";
    exit;
}

if ($type == 'set') { $type = 'sets'; }

// if a researcher, check if they have access to this one
/*
if ($_SESSION['status'] == 'res') {
    $query = new myQuery();
    $query->prepare(
        "SELECT user_id FROM access WHERE type=? AND id=? AND user_id=?",
        array('sii', $type, $id, $user_id)
    );
    if ($query->get_num_rows() == 0) {
        echo "You do not have permission to change owners.";
        exit;
    }
}
*/

function addSetAccess($user_id, $set_id) {
    $query = new myQuery();
    
    // add access to all set items
    $query->prepare(
        "REPLACE INTO access (type, id, user_id) SELECT 
        IF(item_type='set','sets',item_type), item_id, ? 
        FROM set_items WHERE set_id = ?",
        array('ii', $user_id, $set_id)
    );
            
    // add access to all subsets
    $query->prepare("SELECT item_id FROM set_items WHERE item_type = 'set' AND set_id = ?",
                    array('i', $set_id));
    $set_sets = $query->get_col('item_id');
    foreach($set_sets as $subset_id) {
        addSetAccess($user_id, $subset_id);
    }
}

if (array_key_exists('add', $_POST)) {
    $query = new myQuery();
    
    foreach($_POST['add'] as $user_id) {
        $user_id = intval($user_id);
        $query->prepare(
            "REPLACE INTO access (type, id, user_id) VALUES (?, ?, ?)",
            array('sii', $type, $id, $user_id)
        );
    
        if ($_POST['add_items'] == 'true' && $type == 'project') {
            $query->prepare(
                "REPLACE INTO access (type, id, user_id) SELECT 
                IF(item_type='set','sets',item_type), item_id, ? 
                FROM project_items WHERE project_id = ?",
                array('ii', $user_id, $id)
            );
            
            
            $query->prepare("SELECT item_id FROM project_items WHERE item_type = 'set' AND project_id = ?",
                            array('i', $id));
            $proj_items = $query->get_col('item_id');
            
            foreach ($proj_items as $set_id) {
                addSetAccess($user_id, $set_id);
            }
        } else if ($_POST['add_items'] == 'true' && $type == 'sets') {
            addSetAccess($user_id, $id);
        }
    }
}

function deleteSetAccess($user_id, $set_id) {
    $query = new myQuery();
    
    // get set items 
    $query->prepare("SELECT IF(item_type='set','sets',item_type) AS item_type, item_id 
                     FROM set_items WHERE set_id = ?",
                    array('i', $set_id));
    $set_items = $query->get_assoc();
    foreach($set_items as $sitem) {
        $query->prepare(
            "DELETE FROM access WHERE user_id = ? AND type = ? AND id = ?",
            array('isi', $user_id, $sitem['item_type'], $sitem['item_id'])
        );

        if ($sitem['item_type'] == 'sets') {
            deleteSetAccess($user_id, $sitem['item_id']);
        }
    }
}


if (array_key_exists('delete', $_POST)) {
    $del_ids = implode(",", $_POST['delete']);
    $user_id = $_POST['delete'][0];
    
    $query = new myQuery("DELETE FROM access WHERE type='{$type}' AND id={$id} AND user_id IN({$del_ids})");
    
    if ($_POST['delete_items'] == 'true' && $type == 'project') {
        $query->prepare("SELECT IF(item_type='set','sets',item_type) AS item_type, item_id 
                         FROM project_items WHERE project_id = ?",
                        array('i', $id));
        $proj_items = $query->get_assoc();
        
        foreach ($proj_items as $pitem) {
            $query->prepare(
                "DELETE FROM access WHERE user_id = ? AND type = ? AND id = ?",
                array('isi', $user_id, $pitem['item_type'], $pitem['item_id'])
            );
            
            if ($pitem['item_type'] == 'sets') {
                deleteSetAccess($user_id, $pitem['item_id']);
            }
        }
    } else if ($_POST['delete_items'] == 'true' && $type == 'sets') {
        deleteSetAccess($user_id, $id);
    }
}
   
exit;

?>