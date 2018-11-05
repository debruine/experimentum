<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
auth($RES_STATUS);

if (validID($_GET['id']) && !permit('project', $_GET['id'])) header('Location: /res/');


$title = array(
    '/res/' => 'Researchers',
    '/res/project/' => 'Projects',
    '' => 'Info'
);

$styles = array(
    "#setitems" => "margin-top: 1em;",
    "#setitems td+td+td+td+td" => "text-align: right;",
    "#setitems td" => "border-left: 1px dotted grey;",
    "#setitems tr" => "border-right: 1px dotted grey;",
    "span.set_nest" => "display: inline-block; width: 20px; height: 20px; background: transparent no-repeat center center url(/images/linearicons/arrow-down?c=F00);",
    "span.set_nest.hide_set"    => "background-image: url(/images/linearicons/arrow-right?c=000);",
    ".potential-error" => "color: hsl(0, 100%, 40%);"
);

// !AJAX get item data
if (array_key_exists('data', $_GET)) {
    // get stats on participant completion of the experiment
       if (substr($_GET['item'],0,4) == "exp_") {
           $equery = new myQuery('SELECT subtype, random_stim FROM exp WHERE id=' . intval(substr($_GET['item'],4)));
           $einfo = $equery->get_assoc(0);
           if ($einfo['subtype'] == "large_n") {
               $mydata = new myQuery(array(
                                      "CREATE TEMPORARY TABLE tmp_ln
                                      SELECT user_id, sex, COUNT(*) as n, 
                                      AVG(rt) as val 
                                      FROM {$_GET['item']} 
                                      LEFT JOIN user USING (user_id) 
                                      WHERE status>1 AND status<4 
                                      GROUP BY user_id
                                      HAVING n >={$einfo['random_stim']}",
                                      "CREATE TEMPORARY TABLE tmp_ln2
                                      SELECT * FROM tmp_ln",
                                      "SELECT COUNT(*) as total_c,
                                      COUNT(IF(sex='male',1,NULL)) as total_male,
                                      COUNT(IF(sex='female',1,NULL)) as total_female
                                      FROM tmp_ln")
                                      );
               $data = $mydata->get_one_array();

               $mytime = new myQuery("SELECT t1.val as median_val FROM (
                SELECT @rownum:=@rownum+1 as `row_number`, val
                  FROM tmp_ln AS d, (SELECT @rownum:=0) r
                  WHERE val>0 AND val<360001
                  ORDER BY val
                ) as t1, 
                (
                  SELECT count(*) as total_rows
                  FROM tmp_ln2 AS d
                  WHERE val>0 AND val<360001
                ) as t2
                WHERE t1.row_number=floor(1*total_rows/2)+1;", true);
            $median_seconds = $mytime->get_one();
            $median = round(($median_seconds * $einfo['random_stim'])/1000/6)/10;
            
            $mytime = new myQuery("SELECT t1.val as median_val FROM (
                SELECT @rownum:=@rownum+1 as `row_number`, val
                  FROM tmp_ln AS d, (SELECT @rownum:=0) r
                  WHERE val>0 AND val<360001
                  ORDER BY val
                ) as t1, 
                (
                  SELECT count(*) as total_rows
                  FROM tmp_ln2 AS d
                  WHERE val>0 AND val<360001
                ) as t2
                WHERE t1.row_number=floor(9*total_rows/10)+1;", true);
            $upper_seconds = $mytime->get_one();
            $upper = round(($upper_seconds* $einfo['random_stim'])/1000/6)/10;
 
               echo $data['total_c'] . ';' . 
                    $data['total_male'] . ';' . 
                    $data['total_female'] . ';' .
                    $median . ';' .
                    $upper;
                exit;
           }
       }
    
    
    
        $mydata = new myQuery('SELECT COUNT(*) as total_c,
                                COUNT(DISTINCT user_id) as total_dist,
                                COUNT(IF(sex="male",1,NULL)) as total_male,
                                COUNT(IF(sex="female",1,NULL)) as total_female,
                                COUNT(DISTINCT IF(sex="male",user_id,NULL)) as dist_male,
                                COUNT(DISTINCT IF(sex="female",user_id,NULL)) as dist_female
                                FROM ' . $_GET['item'] . ' LEFT JOIN user USING (user_id)
                                WHERE status>1 AND status<40');
        $data = $mydata->get_one_array();
        
        $mytime = new myQuery("SELECT t1.val as median_val FROM (
            SELECT @rownum:=@rownum+1 as `row_number`, (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime)) as val
              FROM {$_GET['item']} AS d,  (SELECT @rownum:=0) r
              WHERE (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))>0 AND (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))<601
              ORDER BY (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))
            ) as t1, 
            (
              SELECT count(*) as total_rows
              FROM {$_GET['item']} AS d
              WHERE (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))>0 AND (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))<601
            ) as t2
            WHERE t1.row_number=floor(1*total_rows/2)+1;", true);
        $median_seconds = $mytime->get_num_rows() ? $mytime->get_one() : 0;
        $median = round($median_seconds/6)/10;
        
        $mytime = new myQuery("SELECT t1.val as median_val FROM (
            SELECT @rownum:=@rownum+1 as `row_number`, 
                (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime)) as val
              FROM {$_GET['item']} AS d,  (SELECT @rownum:=0) r
              WHERE (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))>0 AND 
                    (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))<601
              ORDER BY (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))
            ) as t1, 
            (
              SELECT count(*) as total_rows
              FROM {$_GET['item']} AS d
              WHERE (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))>0 AND 
                    (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))<601
            ) as t2
            WHERE t1.row_number=floor(9*total_rows/10)+1;", true);
        $upper_seconds = $mytime->get_num_rows() ? $mytime->get_one() : 0;
        $upper = round($upper_seconds/6)/10;
        
        
        echo    $data['total_c'] . ';' . 
                $data['total_male'] . ';' . 
                $data['total_female'] . ';' .
                $data['total_dist'] . ';' . 
                $data['dist_male'] . ';' . 
                $data['dist_female'] . ';' .
                $median . ';' .
                $upper;
        exit;
}

// !AJAX delete set
if (array_key_exists('delete', $_GET)) {
    $proj_id = intval($_GET['id']);
    $delete = new myQuery("DELETE FROM project WHERE id=$proj_id;
                        DELETE FROM access WHERE type='project' AND id=$proj_id;
                        DELETE FROM dashboard WHERE type='project' AND id=$proj_id;
                        DELETE FROM project_items WHERE project_id=$proj_id;",
                        true);
    
    echo 'deleted';
    exit;
}

// !AJAX duplicate set
if (array_key_exists('duplicate', $_GET) && validID($_GET['id'])) {
    $old_id = $_GET['id'];
    
    // duplicate exp table entry
    $q = new myQuery('SELECT * FROM project WHERE id=' . $old_id);
    $old_info = $q->get_one_array();
    unset($old_info['id']);
    unset($old_info['res_name']);
    unset($old_info['status']);
    unset($old_info['create_date']);
    $fields = array_keys($old_info);
    
    $query = sprintf("INSERT INTO project (create_date, status, res_name, %s) 
        SELECT NOW(), 'test', CONCAT(res_name, ' (Duplicate)'), %s 
        FROM project WHERE id='%d'",
        implode(", ", $fields),
        implode(", ", $fields),
        $old_id
    );
    $q = new myQuery($query);
    $new_id = $q->get_insert_id();
    
    if (!validID($new_id)) {
        echo "The project did not duplicate. The query was <blockquote>$query</blockquote>";
        exit;
    }
    
    // duplicate tables
    duplicateTable("project_items", 'project', $old_id, $new_id);
    
    // set owner/access
    $q = new myQuery("INSERT INTO access (type, id, user_id) VALUES ('project', $new_id, {$_SESSION['user_id']})");

    
    echo "duplicated:$new_id";
    exit;
}

/***************************************************/
/* !Get Project Data */
/***************************************************/

if (validID($_GET['id'])) {
    $proj_id = intval($_GET['id']);
} else {
    header('Location: /res/project/');
}

$myset = new myQuery('SELECT * FROM project WHERE id=' . $proj_id);

if ($myset->get_num_rows() == 0) { header('Location: /res/project/'); }

$projdata = $myset->get_one_array();

// get status changer for admins
if ($_SESSION['status'] == 'admin') {
    $status_chooser = new select('status', 'status', $projdata['status']);
    $status_chooser->set_options(array(
        'test' => 'test',
        'active' => 'active',
        'archive' => 'archive'
    ));
    $status_chooser->set_null(false);
    $status = $status_chooser->get_element();
} else {
    $status = '(' . $projdata['status'] . ')';
}

// owner functions
$myowners = new myQuery('SELECT user_id, CONCAT(lastname, " ", initials) as name 
                        FROM access 
                        LEFT JOIN res USING (user_id) 
                        WHERE type="project" AND id=' . $proj_id . '
                        ORDER BY lastname, firstname');
$owners = $myowners->get_assoc(false, 'user_id', 'name');
$access = in_array($_SESSION['user_id'], array_keys($owners));

$allowners = new myQuery('SELECT user_id, firstname, lastname, email 
    FROM res 
    LEFT JOIN user USING (user_id) 
    WHERE status > 3');
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

    
/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<h2>Set <?= $projdata['id'] ?>: <?= $projdata['res_name'] ?> <?= $status ?></h2>

<div class='toolbar'>
    <div id="function_buttonset">
        <button id="view-project">Go</button><?php if ($_SESSION['status'] != 'student' || $access) { 
            echo '<button id="edit-project">Edit</button>';
            echo '<button id="delete-project">Delete</button>';
            echo '<button id="duplicate-project">Duplicate</button>';
        } ?>
    </div>
</div>

<table> 
    <tr><td>Name:</td><td><?= $projdata['name'] ?></td></tr>
    <tr><td>Type:</td><td><?= $types[$projdata['type']] ?></td></tr>
    <tr><td>Restrictions:</td><td><?= $projdata['sex'] ?> 
        ages <?= is_null($projdata['lower_age']) ? 'any' : $projdata['lower_age'] ?> 
        to <?= is_null($projdata['upper_age']) ? 'any' : $projdata['upper_age'] ?> years</td></tr>
    <tr><td>Labnotes:</td><td><?= ifEmpty($projdata['labnotes'], '<span class="potential-error">Please add labnotes</span>') ?></td></tr>
    <tr><td>Created on:</td><td><?= $projdata['create_date'] ?></td></tr>
    <tr><td>Owners:<br><?php if ($_SESSION['status'] != 'student') { echo '<button class="tinybutton"  id="owner-change">Change</button>'; } ?></td> 
        <td>
            <ul id='owner-edit'>
                <?= $owner_edit ?>
            </ul>
            <?php if ($_SESSION['status'] != 'student') { ?>
            <input id='owner-add-input' type='text' > (<a id='owner-add'>add</a>)
            <?php } ?>
        </td></tr>
    <tr><td>Blurb:</td><td><?= $projdata['blurb'] ?></td></tr>
    <tr><td>Intro:</td><td><?= $projdata['intro'] ?></td></tr>
</table>

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
            echo generate_project($proj_id);
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

<script>
    $(function() {
        $('#function_buttonset').buttonset();
        
        $( "#view-project" ).click(function() {
            window.location = '/project?<?= $projdata['url'] ?>';
        });
        $( "#edit-project" ).click(function() {
            window.location = '/res/project/builder?id=<?= $projdata['id'] ?>';
        });
        $( "#delete-project" ).click( function() {
            $( "<div/>").html("Do you really want to delete this project?").dialog({
                title: "Delete Project",
                position: ['center', 100],
                modal: true,
                buttons: {
                    Cancel: function() {
                        $( this ).dialog( "close" );
                    },
                    "Delete": function() {
                        $( this ).dialog( "close" );
                        $.get("?delete&id=<?= $projdata['id'] ?>", function(data) {
                            if (data == 'deleted') {
                                window.location = '/res/project/';
                            } else {
                                $('<div title="Problem with Deletion" />').html(data).dialog();
                            }
                        });
                    },
                }
            });
        });
        
        $( "#duplicate-project" ).click( function() {
            $( "<div/>").html("Do you really want to duplicate this project?").dialog({
                title: "Duplicate Project",
                position: ['center', 100],
                modal: true,
                buttons: {
                    Cancel: function() {
                        $( this ).dialog( "close" );
                    },
                    "Duplicate": function() {
                        $( this ).dialog( "close" );
                        $.get("?duplicate&id=<?= $projdata['id'] ?>", function(data) {
                            var resp = data.split(':');
                            if (resp[0] == 'duplicated' && parseInt(resp[1]) > 1) {
                                window.location = '/res/project/info?id=' + resp[1];
                            } else {
                                $('<div title="Problem with Duplication" />').html(data).dialog();
                            }
                        });
                    },
                }
            });
        });
        
        $( "#status" ).css('fontWeight', 'normal').change( function() {
            var $sel = $(this);
            $sel.css('color', 'red');

            $.ajax({
                url: '/res/scripts/status',
                type: 'POST',
                data: {
                    type: 'project',
                    status: $sel.val(),
                    id: <?= $projdata['id'] ?>
                },
                success: function(data) {
                    if (data == 'Status of project_<?= $projdata['id'] ?> changed to '+ $sel.val() ) {
                        $sel.css('color', 'inherit');
                    } else {
                        growl(data, 30);
                    }
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
        var totals = {
            people: 0,
            men: 0,
            women: 0,
            peopled: 0,
            mend: 0,
            womend: 0,
            median: 0,
            upper: 0
        };
        $.each(items, function(idx, item) {
            $.ajax({
                url: '?data&item=' + item,
                type: 'GET',
                success: function(data) {
                    var parts = data.split(';');
                    if (parts.length == 8) {
                        totals.people += parseInt(parts[0]);
                        totals.men += parseInt(parts[1]);
                        totals.women += parseInt(parts[2]);
                        totals.peopled += parseInt(parts[3]);
                        totals.mend += parseInt(parts[4]);
                        totals.womend += parseInt(parts[5]);
                        
                        totals.median += parseInt(parts[6]*10);
                        totals.upper += parseInt(parts[7]*10);
                        
                        $('#total_people').html(totals.people + " (" + totals.peopled + ")");
                        $('#total_men').html(totals.men + " (" + totals.mend + ")");
                        $('#total_women').html(totals.women + " (" + totals.womend + ")");
                        
                        if (<?= (substr($projdata['type'],0,3)=='one') ? 'true' : 'false' ?>) {
                            $('#total_median').html(parseInt(totals.median/items.length)/10 + ' min');
                            $('#total_upper').html(parseInt(totals.upper/items.length)/10 + ' min');
                        } else {
                            $('#total_median').html(totals.median/10 + ' min');
                            $('#total_upper').html(totals.upper/10 + ' min');
                        }
                        
                        var cells = $('#' + item + ' td');
                        
                        cells[4].innerHTML = parts[0] + " (" + parts[3] + ")";
                        cells[5].innerHTML = parts[1] + " (" + parts[4] + ")";
                        cells[6].innerHTML = parts[2] + " (" + parts[5] + ")";
                        cells[7].innerHTML = parts[6];
                        cells[8].innerHTML = parts[7];
                    } else {
                        //alert(data);
                    }
                }
            });
        });
        
        $('html').on("click", ".owner-delete", function() {
            if ($(this).text() == 'delete') {
                $(this).text('undelete');
                $(this).prev().addClass('delete-owner');
            } else {
                $(this).text('delete');
                $(this).prev().removeClass('delete-owner');
            }
        });
        
        $('button.tinybutton').button();
        
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
        
        $( "#owner-add" ).click( function() {
            var owner_id = $('#owner-add-input').data('id');
            
            if (owner_id == '' || owner_id == 0) { return false; }
            
            if ($('#owner-edit .owner-delete[owner-id=' + owner_id + ']').length == 0) {
                var new_owner = "<li><span class='new-owner'>" + $('#owner-add-input').val() + 
                                "</span> (<a class='owner-delete' owner-id='"+owner_id+"'>delete</a>)</li>";
                $('#owner-edit').append(new_owner);
            } else {
                growl("You can't add a duplicate owner.");
            }
            $('#owner-add-input').val('').data('id','');
        });
        
        $( "#owner-change" ).click( function() {
            var to_add = [];
            var to_delete = [];
            $('#owner-edit .owner-delete').each( function() {
                var $this = $(this);
                
                if ($this.text() == "delete") {
                    to_add.push($this.attr('owner-id'));
                } else {
                    to_delete.push($this.attr('owner-id'));
                }
            });
            
            if (to_add.length == 0) {
                growl("You have to keep at least one owner.");
                return false;
            }
            
            $.ajax({
                url: '/res/scripts/owners',
                type: 'POST',
                data: {
                    type: 'project',
                    id: <?= $projdata['id'] ?>,
                    add: to_add,
                    delete: to_delete
                },
                success: function(data) {
                    if (data) {
                        growl("Something went wrong");
                    } else {
                        $('#owner-edit .delete-owner').closest('li').remove();
                        $('#owner-edit span').removeClass('new-owner');
                    }
                }
            });
        });
        
        
    });
</script>

<?php

$page->displayFooter();

?>