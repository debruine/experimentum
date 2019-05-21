<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
require_once DOC_ROOT . '/include/classes/Parsedown.php';
auth($RES_STATUS);

if (validID($_GET['id']) && !permit('sets', $_GET['id'])) header('Location: /res/');

$title = array(
    '/res/' => 'Researchers',
    '/res/set/' => 'Sets',
    '' => 'Info'
);

$styles = array(
    "#setitems" => "width: 100%;",
    "#setitems td+td+td+td+td" => "text-align: right;",
    "#setitems td" => "border-left: 1px dotted grey;",
    "#setitems tr" => "border-right: 1px dotted grey;",
    "span.set_nest" => "display: inline-block; width: 20px; height: 20px; background: transparent no-repeat center center url(/images/linearicons/arrow-down?c=1f9c03);",
    "span.set_nest.hide_set"    => "background-image: url(/images/linearicons/arrow-right?c=000);",
    ".potential-error" => "color: hsl(0, 100%, 40%);"
);


/***************************************************/
/* !Get Set Data */
/***************************************************/

$item_id = intval($_GET['id']);

$myset = new myQuery('SELECT * FROM sets WHERE id=' . $item_id);

if ($myset->get_num_rows() == 0) { header('Location: /res/set/'); }

$itemdata = $myset->get_one_array();

// convert markdown sections
$Parsedown = new Parsedown();
$itemdata['feedback_general'] = $Parsedown->text($itemdata['feedback_general']);
$itemdata['feedback_specific'] = $Parsedown->text($itemdata['feedback_specific']);

$itemdata['sex'] = array(
    'both' => 'All genders',
    'male' => 'Men only',
    'female' => 'Women only'
)[$itemdata['sex']];

// get status changer for admins
if ($_SESSION['status'] == 'admin') {
    $status_chooser = new select('status', 'status', $itemdata['status']);
    $status_chooser->set_options(array(
        'test' => 'test',
        'active' => 'active',
        'archive' => 'archive'
    ));
    $status_chooser->set_null(false);
    $status = $status_chooser->get_element();
} else {
    $status = '(' . $itemdata['status'] . ')';
}

// owner functions
$myowners = new myQuery('SELECT user_id, CONCAT(lastname, ", ", firstname) as name 
                            FROM access 
                            LEFT JOIN res USING (user_id) 
                            WHERE type="sets" AND id=' . $item_id);
$owners = $myowners->get_assoc(false, 'user_id', 'name');
$access = in_array($_SESSION['user_id'], array_keys($owners));

$allowners = new myQuery('SELECT user_id, firstname, lastname, email FROM res LEFT JOIN user USING (user_id) WHERE status > 3');
$ownerlisting = $allowners->get_assoc();
$ownerlist = array();
foreach($ownerlisting as $res) {
    $user_id = $res['user_id'];
    $lastname = htmlspecialchars($res['lastname'], ENT_QUOTES);
    $firstname = htmlspecialchars($res['firstname'], ENT_QUOTES);
    $email = htmlentities($res['email'], ENT_QUOTES);
    
    $ownerlist[] = "\n{ value: '{$user_id}', name: '{$lastname}, {$firstname}', label: '{$firstname} {$lastname} {$email}' }";
}
$owner_edit = "";
foreach($owners as $id => $name) {
    $owner_edit .= "<li><span>{$name}</span>";
    if ($_SESSION['status'] != 'student') { 
        $owner_edit .= " (<a class='owner-delete' owner-id='{$id}'>delete</a>)</li>";
    }
}


// get data on all items
$subset = 0.25;
$items_for_data = array();

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
        
        $itemlist .= "<a href='/res/{$item['item_type']}/info?id={$item['item_id']}'>$table</a></td><td>{$item['name']}</td><td class='{$status_check}'>{$item['status']}</td><td>{$item['type']}</td>";

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

$types = array(
    'fixed' => 'Fixed Order (presents each item in the order you set)',
    'random' => 'Random Order (presents each item in a randomised order)',
    'one_random' => 'One of - random (presents only a single random item from your list)',
    'one_equal' => 'One of - equal (Presents only a single item from your list and tries to ensure that an equal number of men and women participate in each item. Do not use this option if some of your items already have different numbers of participants.)'
);
    
/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<input type="hidden" id="item_id" value="<?= $itemdata['id'] ?>" />
<input type="hidden" id="item_type" value="set" />

<h2>Set <?= $itemdata['id'] ?>: <?= $itemdata['res_name'] ?></h2>

<div class='toolbar'>
    <div id="function_buttonset">
        <?php
            echo '<button id="view-set">Go</button>';
            echo '<button id="edit-set">Edit</button>';
            echo '<button id="delete-item">Delete</button>';
            echo '<button id="duplicate-item">Duplicate</button>';
            echo '<button id="get-json">Structure</button>';
            echo '<button id="test-set">Test</button>';
        ?>
    </div>
</div>

<table class='info'> 
    <tr><td>Name:</td><td><?= $itemdata['name'] ?></td></tr>
    <tr><td>Status:</td> <td><?= $status ?></td></tr>
    <tr><td>Created on:</td><td><?= $itemdata['create_date'] ?></td></tr>
    <tr><td>Type:</td><td id='itemtype'><?= $types[$itemdata['type']] ?></td></tr>
    <tr><td>Owners:<br><?php if ($_SESSION['status'] != 'student') { echo '<button class="tinybutton"  id="owner-change">Change</button>'; } ?></td> 
        <td>
            <ul id='owner-edit'>
                <?= $owner_edit ?>
            </ul>
            <?php if ($_SESSION['status'] != 'student') { ?>
            <input id='owner-add-input' type='text' > (<a id='owner-add'>add</a>)
            <?php } ?>
        </td></tr>
    <tr><td>Restrictions:</td><td><?= $itemdata['sex'] ?> 
        ages <?= is_null($itemdata['lower_age']) ? 'any' : $itemdata['lower_age'] ?> 
        to <?= is_null($itemdata['upper_age']) ? 'any' : $itemdata['upper_age'] ?> years</td></tr>
    <tr><td>Feedback:</td><td><?= $itemdata['feedback_general'] ?><br /><?= $itemdata['feedback_specific'] ?></td></tr>
    <tr><td>Labnotes:</td><td><?= ifEmpty($itemdata['labnotes'], '<span class="error">Please add labnotes</span>') ?></td></tr>
</table>

<p class="fullwidth">The table below shows the number of total 
    completions (and unique participants) for the items from this set.</p>

<table id="setitems">
    <thead>
        <tr>
            <td>Item</td>
            <td>Name</td>
            <td>Status</td>
            <td>Type</td>
            <td>People</td>
            <td>Men</td>
            <td>Women</td>
            <td>Median Time</td>
            <td>90th Percentile</td>
        </tr>
    </thead>
    <tbody>
        <?php 
            echo generate_set($item_id);
        ?>
    </tbody>
    <tfoot>
        <td>Totals</td>
        <td></td>
        <td></td>
        <td></td>
        <td id="total_people">...</td>
        <td id="total_men">...</td>
        <td id="total_women">...</td>
        <td id="total_median">...</td>
        <td id="total_upper">...</td>
</table>

<div id="help" title="Set Info Help">
    
    <ul>
        <li>The table shows information about each item in the set.</li>
        <li>Subsets are indented under their set name.</li>
        <li>The totals at the bottom are for every item, even if all or some of the subsets are &ldquo;one of&rdquo; types.</li>
        <li>Click the &ldquo;Test&rdquo; button to generate a sample order.</li>
        <li>Click the &ldquo;Go&rdquo; button to participate in the set.</li>
    </ul>
</div>

<!--**************************************************-->
<!-- !Javascripts for this page -->
<!--**************************************************-->

<script src="/res/scripts/res.js"></script>

<script>
    $( "#view-set" ).click(function() {
        window.location = '/include/scripts/set?id=' + $('#item_id').val();
    });
    $( "#edit-set" ).click(function() {
        window.location = '/res/set/builder?id=' + $('#item_id').val();
    });
    
    $('#test-set').button().click( function() {
        $.ajax({
            url: '/include/scripts/set?test&id=' + $('#item_id').val(),
            type: 'GET',
            success: function(data) {
                if (data) $('<div title="Sample Order" />').html(data).dialog();
            }
        });
    });
    
    $('span.set_nest').click( function() {
        var toggle_class = $(this).closest('tr').attr('id');
        $('tr.' + toggle_class).toggle();
        stripe('#setitems tbody');
        $(this).toggleClass("hide_set");
    });
    
    var items = ["<?= implode('","', $items_for_data) ?>"];
    item_stats(items);
    
    $('#owner-add-input').autocomplete({
        source: [<?= implode(",", $ownerlist) ?>],
        focus: function( event, ui ) {
            $(this).val(ui.item.name);
            return false;
        },
        select: function( event, ui ) {
            $(this).val(ui.item.name).data('id', ui.item.value);
            return false;
        }
    }).data('id', 0);
</script>

<?php

$page->displayFooter();

?>