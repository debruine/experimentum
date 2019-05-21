<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(1);

$item_list = array();

function get_set_items($id) {
    global $item_list;

    $q = new myQuery('SELECT type FROM sets WHERE id=' . $id);
    $type = $q->get_one();

    // get set items that this person is elegible to do (get all items if experimenter)
    $q = new myQuery('SELECT item_type, item_id, 
                    IF(item_type="exp", e.upper_age, IF(item_type="quest", q.upper_age, s.upper_age)) as upper_age,
                    IF(item_type="exp", e.lower_age, IF(item_type="quest", q.lower_age, s.lower_age)) as lower_age,
                    IF(item_type="exp", e.sex, IF(item_type="quest", q.sex, s.sex)) as sex,
                    IF(item_type="exp", e.status, IF(item_type="quest", q.status, s.status)) as status
                    FROM set_items 
                    LEFT JOIN exp AS e ON (item_type="exp" AND e.id=item_id)
                    LEFT JOIN quest AS q ON (item_type="quest" AND q.id=item_id)
                    LEFT JOIN sets AS s ON (item_type="set" AND s.id=item_id)
                    WHERE set_id=' . $id . ' 
                    GROUP BY item_type, item_id, item_n
                    HAVING "' . $_SESSION['status'] . '" IN("student","res","admin") OR 
                    (
                        status !="archive" AND status !="test"
                        AND (sex="both" OR sex="' . $_SESSION['sex'] . '")
                        AND (upper_age IS NULL OR ' . $_SESSION['age'] . '<= upper_age)
                        AND (lower_age IS NULL OR ' . $_SESSION['age'] . '>= lower_age)
                    )
                    ORDER BY item_n');
    $items = $q->get_assoc();
    
    if (count($items > 0)) {
        if ($type == 'random') {
            // randomise order of items
            shuffle($items);        
        } else if ($type == 'one_random') {
            // choose a random one of the set items
            $rand_key = array_rand($items);
            $items = array($items[$rand_key]);
        } else if ($type == 'one_equal') {
            // choose one from the set, equalising participants numbers and sex
            $male_counts = array();
            $female_counts = array();
            $all_counts = array();
            
            foreach ($items as $item) {
                if ($item['item_type'] != 'set') {
                    $table = $item['item_type'] . "_data"; // . $item['item_id'];
                    $q = new myQuery("SELECT COUNT(IF(sex='male',1,NULL)) AS male, 
                                COUNT(IF(sex='female',1,NULL)) AS female, 
                                COUNT(*) AS allpeople 
                                FROM {$table}
                                LEFT JOIN user USING (user_id) 
                                WHERE status>1 AND status<3 
                                  AND {$item['item_type']}_id = {$item['item_id']}
                                GROUP BY NULL");
                    $item_counts = $q->get_assoc(0);
                    
                    $male_counts[$table] = $item_counts['male'];
                    $female_counts[$table] = $item_counts['female'];
                    $all_counts[$table] = $item_counts['allpeople'];
                } else {
                    // item is a set, don't get counts
                    // set to 10000000 plus rand (to randomise ordering) in case all items are sets
                    $male_counts[$table] = 10000000 + rand(0,100);
                    $female_counts[$table] = 10000000 + rand(0,100);
                    $all_counts[$table] = 10000000 + rand(0,100);
                }
            }
            
            // choose item with lowest item count for the participant's sex
            if ($_SESSION['sex'] == 'male') {
                asort($male_counts);
                reset($male_counts);
                list($min, $c) = each($male_counts);
                $min_item = explode('_', $min);
            } elseif ($_SESSION['sex'] == 'female') {
                asort($female_counts);
                reset($female_counts);
                list($min, $c) = each($female_counts);
                $min_item = explode('_', $min);
            } else {
                asort($all_counts);
                reset($all_counts);
                list($min, $c) = each($all_counts);
                $min_item = explode('_', $min);
            }
            
            $items = array(array(
                'item_type' => $min_item[0],
                'item_id' => $min_item[1]
            ));
        }
    }
    
    foreach ($items as $item) {
        if ($item['item_type'] == 'exp') {
            $item_list[] = '/exp?id=' . $item['item_id'];
        } else if ($item['item_type'] == 'quest') {
            $item_list[] = '/quest?id=' . $item['item_id'];
        } else if ($item['item_type'] == 'set') {
            get_set_items($item['item_id']); // recurses if item is another set
        }
    }
}


if (validID($_GET['id'])) {
    get_set_items($_GET['id']);
}

// send to feedback last
$q = new myQuery('SELECT feedback_general, feedback_specific, feedback_query, chart_id, forward FROM sets WHERE id=' . $_GET['id']);
$fb = $q->get_one_array();
if (   !empty($fb['feedback_general'])
    || !empty($fb['feedback_specific'])
    || !empty($fb['feedback_query'])
    || !empty($fb['chart_id'])
    || !empty($fb['forward'])   ) {
    $item_list[] = '/fb?type=sets&id=' . $_GET['id'];
}

if (array_key_exists("test", $_GET)) {
    htmlArray($item_list);
} else {
    $_SESSION['set_list'] = $item_list;
    $_SESSION['set_item_number'] = 0;
    if (count($_SESSION['set_list'])>0) {
        header('Location: ' . $_SESSION['set_list'][0]);
    } else {
        header('Location: /');
    }
}
exit();

?>