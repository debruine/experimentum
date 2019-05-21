<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

$styles = array(
    "table.query tr td:last-child" => "text-align: right;",
    ".clickhide" => "max-height: 1.5em; overflow: hidden;"
);

$title = array(
    '/res/' => 'Researchers',
    '/res/project/' => 'Projects',
    '/res/prject/translate' => 'Translate'
);

function get_set_components($id) {
    $item_list = array();
    
    $q = new myQuery();
    $q->prepare(
        "SELECT item_type, item_id FROM set_items WHERE set_id = ?",
        array('i', $id)
    );
    
    $items = $q->get_assoc();
    
    foreach ($items as $item) {
        if ($item['item_type'] == 'set') {
            // add set to list
            $item_name = $item['item_type'] . "_" . $item['item_id'];
            $item_list[$item_name] = $item;
            
            // add set's items
            $item_list = array_merge(
                $item_list, 
                get_set_components($item['item_id'])
            );
        } else {
            // add item to list
            $item_name = $item['item_type'] . "_" . $item['item_id'];
            $item_list[$item_name] = $item;
        }
    }
    
    return $item_list;
}


// get set ID for this project
$q = new myQuery();
$q->prepare(
    "SELECT item_id FROM project_items WHERE project_id = ?",
    array('i', $_GET['id'])
);
$set_id = $q->get_one();
$item_list = get_set_components($set_id);
$item_list[] = array(
    "item_type" => "project",
    "item_id" => $_GET['id']
);

$all_text = array();
$q = new myQuery();

foreach ($item_list as $item) {
    if ($item['item_type'] == "set") {
        $query = "SELECT name, res_name, feedback_general FROM sets WHERE id=?";
    } else if ($item['item_type'] == "quest") {
        $query = "SELECT name, res_name, instructions, feedback_general FROM quest WHERE id=?";
    } else if ($item['item_type'] == "exp") {
        $query = "SELECT name, res_name, instructions, question, low_anchor, high_anchor, feedback_general FROM exp WHERE id=?";
    } else if ($item['item_type'] == "project") {
        $query = "SELECT name, res_name, intro FROM project WHERE id=?";
    }
    
    
    $q->prepare($query, array('i', $item['item_id']));
    $text = $q->get_one_row();
    
    foreach($text as $col => $val) {
        $all_text[] = array(
            "table" => $item['item_type'],
            "id" => $item['item_id'],
            "column" => $col,
            "text" => $val,
            "translate" => ""
        );
    }
    
    if ($item['item_type'] == "quest") {
        $q->prepare(
            "SELECT id, question FROM question WHERE quest_id=? ORDER BY n",
            array('i', $item['item_id'])
        );
        $questions = $q->get_assoc();
        foreach($questions as $qu) {
            $all_text[] = array(
                "table" => "question",
                "id" => $qu['id'],
                "column" => "question",
                "text" => $qu['question'],
                "translate" => ""
            );
        }
    }
}


/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();

?>

<?= array_to_table($all_text) ?>

<script>

</script>

<?php


$page->displayFooter();

?>