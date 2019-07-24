<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
require_once DOC_ROOT . '/include/classes/Parsedown.php';
auth($RES_STATUS);

$return = array('error' => false);

if (validID($_POST['project_id']) && !permit('project', $_POST['project_id'])) {
    $return['error'] = "You are not authorised to save this project.";
    scriptReturn($return);
    exit;
}

// save a project
$clean = my_clean($_POST);

// check for duplicate URL
$q = new myQuery("SELECT id FROM project WHERE url='{$clean['url']}'");
if ($q->get_num_rows() > 0 && $q->get_one() != $clean['project_id']) {
    $return['error'] = 'Your URL is already taken.';
    scriptReturn($return);
    exit;
}

$proj_query = sprintf('REPLACE INTO project (id, name, res_name, url, 
                          intro, labnotes, sex, lower_age, upper_age, blurb, create_date) 
                        VALUES (%s, "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", NOW())',
                        $clean['project_id'],
                        $clean['project_name'],
                        $clean['res_name'],
                        $clean['url'],
                        $clean['intro'],
                        $clean['labnotes'],
                        $clean['sex'],
                        $clean['lower_age'],
                        $clean['upper_age'],
                        $clean['blurb']);

$proj_query = str_replace('""', 'NULL', $proj_query);
$proj_query = str_replace('"NULL"', 'NULL', $proj_query);

$q = new myQuery($proj_query);

// get new set ID if a new set
if ('NULL' == $clean['project_id']) $clean['project_id'] = $q->get_insert_id();

// delete old items from set list
$q = new myQuery('DELETE FROM project_items WHERE project_id=' . $clean['project_id']);

// add set items to set list
$project_items = explode(';', $clean['project_items']);
$project_icons = explode(';', $clean['project_icons']);

$item_query = array();
foreach ($project_items as $n => $item) {
    $item_n = $n+1;
    $i = explode('_', $item);
    $icon = ($project_icons[$n] == 'undefined') ? 'NULL' : "'{$project_icons[$n]}'";
    $item_query[] = "('{$clean['project_id']}', '{$i[0]}', '{$i[1]}', '{$item_n}', {$icon})";
}

if (count($item_query)) {
    $q = new myQuery('INSERT INTO project_items (project_id, item_type, item_id, item_n, icon) VALUES ' . implode(',', $item_query));
}

// add to access list
$q = new myQuery("REPLACE INTO access (type, id, user_id) VALUES ('project', {$clean['project_id']}, {$_SESSION['user_id']})");

$return['id'] = $clean['project_id']; 
scriptReturn($return);
exit;

?>