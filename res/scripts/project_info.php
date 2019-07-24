<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
require_once DOC_ROOT . '/include/classes/Parsedown.php';
auth($RES_STATUS);

$return = array('error' => false);

if (validID($_GET['id']) && !permit('project', $_GET['id'])) {
    $return['error'] = "You are not authorised to access this project.";
    scriptReturn($return);
    exit;
}

$item_id = $_POST['id'];


/***************************************************/
/* !Get Project Data */
/***************************************************/

$myset = new myQuery();
$myset->prepare('SELECT project.*, 
                        COUNT(DISTINCT session.id) AS sessions,
                        COUNT(DISTINCT session.user_id) AS users
                   FROM project 
              LEFT JOIN session ON project_id = project.id
                  WHERE project.id=?', 
                  array('i', $item_id)
        );

if ($myset->get_num_rows() == 0) { 
    $return['error'] = "The project does not exist.";
    scriptReturn($return);
    exit;
}

$itemdata = $myset->get_one_array();

// convert markdown sections
$Parsedown = new Parsedown();
$itemdata['intro'] = $Parsedown->text($itemdata['intro']);
$itemdata['url'] = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] . "/project?" . $itemdata['url'];

$itemdata['sex'] = array(
    'both' => 'All genders',
    'male' => 'Men only',
    'female' => 'Women only'
)[$itemdata['sex']];

$itemdata['upper_age'] = is_null($itemdata['upper_age']) ? 'any' : $itemdata['upper_age'];
$itemdata['lower_age'] = is_null($itemdata['lower_age']) ? 'any' : $itemdata['lower_age'];

$return['info'] = $itemdata;

// get status changer for admins and res
if (in_array($_SESSION['status'], array('admin', 'res'))) {
    $status_chooser = new select('status', 'status', $itemdata['status']);
    $status_chooser->set_options(array(
        'test' => 'test',
        'active' => 'active',
        'archive' => 'archive'
    ));
    $status_chooser->set_null(false);
    $status = $status_chooser->get_element();
    $status .= '<button class="tinybutton" id="all-status-change">Set all component statuses to project status</button>';
} else {
    $status = $itemdata['status'];
    $status .= ' <button class="tinybutton" id="request-status-change">Request status change</button>';
}

$return['status'] = $status;

// owner functions
$return['owners'] = array();
$myowners = new myQuery();
$myowners->prepare(
    'SELECT user_id, CONCAT(lastname, " ", firstname) as name 
       FROM access 
  LEFT JOIN res USING (user_id) 
      WHERE type="project" AND id=?
   ORDER BY lastname, firstname',
    array('i', $item_id)
);
$owners = $myowners->get_key_val('user_id', 'name');
$access = in_array($_SESSION['user_id'], array_keys($owners));
$return['owners']['owners'] = $owners;

$owner_edit = "";
foreach($owners as $id => $name) {
    $owner_edit .= "<tr><td>{$name}</td>";
    if ($_SESSION['status'] != 'student') { 
        $owner_edit .= "<td><button class='tinybutton owner-delete' owner-id='{$id}'>remove</button></td>";
        $owner_edit .= "<td><button class='tinybutton owner-delete-items' owner-id='{$id}'>remove from all items</button></td>";
    }
    $owner_edit .= "</tr>";
}
$return['owners']['owner_edit'] = $owner_edit; 

$allowners = new myQuery('SELECT user_id, firstname, lastname, email 
    FROM res 
    LEFT JOIN user USING (user_id) 
    WHERE status IN ("admin", "res", "student")');
$ownerlisting = $allowners->get_assoc();
$ownerlist = array();
$return['owners']['list'] = array();
foreach($ownerlisting as $res) {
    $user_id = $res['user_id'];
    $lastname = htmlspecialchars($res['lastname'], ENT_QUOTES);
    $firstname = htmlspecialchars($res['firstname'], ENT_QUOTES);
    $email = htmlentities($res['email'], ENT_QUOTES);
    
    $ownerlist[] = "\n{ value: '{$user_id}', name: '{$lastname}, {$firstname}', label: '{$firstname} {$lastname} {$email}' }";
    
    $return['owners']['source'][] = array(
        'value' => $user_id,
        'name' => "{$lastname}, {$firstname}",
        'label' => "{$firstname} {$lastname} {$email}"
    );
}

if ($_SESSION['status'] != 'student') { 
    $return['owners']['button'] = '<button class="tinybutton"  id="owner-change">Change</button>'; 
}


// get data on all items
$subset = 0.25;
$items_for_data = array();

function generate_project($id, $class="") {
    global $subset, $items_for_data;

    $myitems = new myQuery("SELECT item_type, item_id, 
    IF(item_type='exp', exp.res_name, 
        IF(item_type='quest', quest.res_name, 
            IF(item_type='set', sets.res_name,'No such item'))) as name,
    IF(item_type='exp', exp.status, 
        IF(item_type='quest', quest.status, 
            IF(item_type='set', sets.status,'No such item'))) as status,
    IF(item_type='exp', CONCAT(exp.exptype, '-', exp.subtype,  IF(exp.design='between', '<br /><span class=\"potential-error\">between</span>', ': w/in')), 
        IF(item_type='quest', quest.questtype, 
            IF(item_type='set', sets.type,'No such item'))) as type,
    icon
    FROM project_items 
    LEFT JOIN exp ON item_type='exp' AND exp.id=item_id
    LEFT JOIN quest ON item_type='quest' AND quest.id=item_id
    LEFT JOIN sets ON item_type='set' AND sets.id=item_id
    WHERE project_id=$id ORDER BY item_n");
    $items = $myitems->get_assoc();
    
    $itemlist = '';
    
    foreach ($items as $item) {
        $table = $item['item_type'] . '_' . $item['item_id'];
        $status_check = ($item['status'] == 'active') ? '' : 'potential-error';
        
        $itemlist .= "<tr id='$table' class='$class'><td style='padding-left: {$subset}em'>";
        
        if ($item['item_type'] == 'set') {
            $itemlist .= "<span class='set_nest'></span>";
        }
        
        $itemlist .= "<a href='/res/{$item['item_type']}/info?id={$item['item_id']}'>$table</a></td><td>{$item['name']}</td><td class='status {$status_check}'>{$item['status']}</td><td>{$item['type']}</td>";

        if ($item['item_type'] == 'set') {
            $itemlist .= "<td colspan='100'></td></tr>\n";
            $subset += 1;
            $itemlist .= generate_set($item['item_id'], $class . ' ' . $table);
            $subset -= 1;
        } else {
            $items_for_data[] = $table;
            $itemlist .= "<td>...</td><td>...</td><td>...</td><td>...</td><td>...</td></tr>\n";
        }
    }
    return $itemlist;
}

function generate_set($id, $class="") {
    global $subset, $items_for_data;

    $myitems = new myQuery("SELECT item_type, item_id, 
    IF(item_type='exp', exp.res_name, 
        IF(item_type='quest', quest.res_name, 
            IF(item_type='set', sets.res_name,'No such item'))) as name,
    IF(item_type='exp', exp.status, 
        IF(item_type='quest', quest.status, 
            IF(item_type='set', sets.status,'No such item'))) as status,
    IF(item_type='exp', CONCAT(exp.exptype, '-', exp.subtype,  IF(exp.design='between', '<br /><span class=\"potential-error\">between</span>', ': w/in')), 
        IF(item_type='quest', quest.questtype, 
            IF(item_type='set', sets.type,'No such item'))) as type  
    FROM set_items 
    LEFT JOIN exp ON item_type='exp' AND exp.id=item_id
    LEFT JOIN quest ON item_type='quest' AND quest.id=item_id
    LEFT JOIN sets ON item_type='set' AND sets.id=item_id
    WHERE set_id=$id ORDER BY item_n");
    $items = $myitems->get_assoc();
    
    $itemlist = '';
    
    foreach ($items as $item) {
        $table = $item['item_type'] . '_' . $item['item_id'];
        $status_check = ($item['status'] == 'active') ? '' : 'potential-error';
        
        $itemlist .= "<tr id='$table' class='$class'><td style='padding-left: {$subset}em'>";
        
        if ($item['item_type'] == 'set') {
            $itemlist .= "<span class='set_nest'></span>";
        }
        
        $itemlist .= "<a href='/res/{$item['item_type']}/info?id={$item['item_id']}'>$table</a></td><td>{$item['name']}</td><td class='status {$status_check}'>{$item['status']}</td><td>{$item['type']}</td>";

        if ($item['item_type'] == 'set') {
            $itemlist .= "<td colspan='100'></td></tr>\n";
            $subset += 1;
            $itemlist .= generate_set($item['item_id'], $class . ' ' . $table);
            $subset -= 1;
        } else {
            $items_for_data[] = $table;
            $itemlist .= "<td>...</td><td>...</td><td>...</td><td>...</td><td>...</td></tr>\n";
        }
    }
    return $itemlist;
}

$return['project_items'] = generate_project($item_id);
$return['items_for_data'] = $items_for_data;

scriptReturn($return);
exit;


?>