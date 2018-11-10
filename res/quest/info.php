<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
require_once DOC_ROOT . '/include/classes/Parsedown.php';
auth($RES_STATUS);

if (validID($_GET['id']) && !permit('quest', $_GET['id'])) header('Location: /res/');


$title = array(
    '/res/' => 'Researchers',
    '/res/quest/' => 'Questionnaires',
    '' => 'Info'
);

$styles = array( 
    'br' => 'margin: 0 0 .5em 0;',
    '.question_info' => 'margin-bottom: 1em; width: 100%;'
);

// !AJAX duplicate questionnaire
if (array_key_exists('duplicate', $_GET) && validID($_GET['id'])) {
    $old_id = $_GET['id'];
    
    // duplicate quest table entry
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
        echo "The questionnaire did not duplicate. The query was <blockquote>$query</blockquote>";
        exit;
    }
    
    $q = new myQuery("UPDATE quest SET feedback_query=REPLACE(feedback_query, 'quest_{$old_id}', 'quest_{$new_id}') WHERE id='{$new_id}'");
    
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
    
    // duplicate tables
    duplicateTable("radiorow_options", 'quest', $old_id, $new_id);
    
    // duplicate data table
    $q = new myQuery("DESC quest_{$old_id}");
    $table_schema = $q->get_assoc(false, 'Field', 'Type');
    unset($table_schema['id']);
    unset($table_schema['user_id']);
    unset($table_schema['starttime']);
    unset($table_schema['endtime']);
    
    // set owner/access
    $q = new myQuery("INSERT INTO access (type, id, user_id) VALUES ('quest', $new_id, {$_SESSION['user_id']})");

    echo "duplicated:$new_id";
    exit;
}


/****************************************************
 * Get questionnaire Data
 ***************************************************/

 
if (validID($_GET['id'])) {
    $quest_id = intval($_GET['id']);
} else {
    header('Location: /res/quest/');
}
    

$myquest = new myQuery('SELECT quest.*
                        FROM quest 
                        WHERE quest.id=' . $quest_id . ' GROUP BY quest.id');

if ($myquest->get_num_rows() == 0) { header('Location: /res/quest/'); }

$itemdata = $myquest->get_one_array();

// convert markdown sections
$Parsedown = new Parsedown();
$itemdata['instructions'] = $Parsedown->text($itemdata['instructions']);
$itemdata['feedback_general'] = $Parsedown->text($itemdata['feedback_general']);
$itemdata['feedback_specific'] = $Parsedown->text($itemdata['feedback_specific']);

$itemdata['sex'] = array(
    'both' => 'All genders',
    'male' => 'Men only',
    'female' => 'Women only'
)[$itemdata['sex']];

// owner functions
$myowners = new myQuery('SELECT user_id, CONCAT(lastname, ", ", firstname) as name 
                           FROM access 
                           LEFT JOIN res USING (user_id) 
                           WHERE type="quest" AND id=' . $quest_id);
$owners = $myowners->get_assoc(false, 'user_id', 'name');
$access = in_array($_SESSION['user_id'], array_keys($owners));

$allowners = new myQuery('SELECT user_id, firstname, lastname, email 
                            FROM res 
                            LEFT JOIN user USING (user_id) 
                            WHERE status > 4');
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
    if ($_SESSION['status'] == 'admin') { 
        $owner_edit .= " (<a class='owner-delete' owner-id='{$id}'>delete</a>)</li>";
    }
}

// get data on questions
$query = new myQuery("SELECT q.*,
                        IF(options.q_id IS NOT NULL, GROUP_CONCAT(CONCAT(opt_value, ':', display) SEPARATOR '</li><li>'), 
                            IF(q.type='text', CONCAT('text: limit ', maxlength, ' characters'), 
                                If(q.type='radioanchor', CONCAT('1 (', low_anchor, ') to ', maxlength, ' (', high_anchor, ')'), 
                                    If(q.type='radiorow', 'FWD',  If(q.type='radiorev', '<span class=\"ui-state-highlight\">REV</span>',  
                                        CONCAT(q.type, ': ', low_anchor, ' to ', high_anchor))
                                    )
                                )
                            )
                        ) as option_list
                        FROM question as q
                        LEFT JOIN options ON q_id=q.id 
                        WHERE q.quest_id={$quest_id}
                        GROUP BY q.id
                        ORDER BY n");
$questlist = $query->get_assoc();
$questlist_info = '';
foreach($questlist as $q) {
    $questlist_info .= "<tr><td>q{$q['id']}</td><td>{$q['name']}</td><td>{$q['question']}</td><td><ul><li>{$q['option_list']}</li></ul></td></tr>" . ENDLINE;
}

// get status changer for researchers
if (in_array($_SESSION['status'], array('researcher', 'admin'))) {
    $status_chooser = new select('status', 'status', $itemdata['status']);
    $status_chooser->set_null(false);
    $status_chooser->set_options(array(
        'test' => 'test',
        'active' => 'active',
        'archive' => 'archive'
    ));
    $status = $status_chooser->get_element();
} else {
    $status = '(' . $itemdata['status'] . ')';
}

$mysets = new myQuery('SELECT set_id, CONCAT(set_id, ": ", name) as si from set_items LEFT JOIN sets ON sets.id=set_id where item_type="quest" and item_id=' . $quest_id);
$setslist = $mysets->get_assoc(false, 'set_id', 'si');  
$insets = new select('insets', 'insets');
$insets->set_options($setslist);
$insets->set_null(false);

// create chart of time taken

$timechart = 'CREATE TEMPORARY TABLE tmp_ln ' . 
             'SELECT endtime-starttime as total_time ' .
             'FROM quest_data ' .
             'WHERE quest_id='.$quest_id. ' ' .
             'GROUP BY session_id, user_id; ' .
                
             'CREATE TEMPORARY TABLE tmp_ln2 ' .
             'SELECT * FROM tmp_ln; ' .
             
             'SELECT @percentile:=t1.val ' .
             'FROM (SELECT @rownum:=@rownum+1 as row_number, ' .
             '             total_time as val FROM tmp_ln AS d, ' .
             '             (SELECT @rownum:=0) r ORDER BY total_time ) as t1, ' .
             '             (SELECT count(*) as total_rows FROM tmp_ln2 AS d) as t2 ' .
             'WHERE t1.row_number=floor(95*total_rows/100)+1; ' .
             
             'CREATE TEMPORARY TABLE tmp_score ' .
             'SELECT ROUND(total_time/60,2) as score ' .
             'FROM tmp_ln; ' .
             
             'SELECT @totalp := COUNT(*) ' .
             'FROM tmp_score ' .
             'WHERE score IS NOT NULL GROUP BY NULL; ' .
             
             'SELECT "  " as title, ' .
             '  "Minutes" as xlabel, ' .
             '  "Proportion of Participants" as ylabel, ' .
             '  0 as ymin, 0 as xmin, ' .
             '  score as xcat, ' .
             '  COUNT(*)/@totalp as dv, ' .
             '  "line" as chart_type, ' .
             '  "reverse" as reverse, ' .
             '  "time_container" as container ' .
             'FROM tmp_score ' .
             'WHERE score IS NOT NULL ' .
             '  AND score<=(@percentile/60) ' .
             '  AND score>0 ' .
             'GROUP BY score;';
    
/****************************************************/
/* !Display Page */
/***************************************************/


$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<h2>quest <?= $itemdata['id'] ?>: <?= $itemdata['res_name'] ?></h2>

<div class='toolbar'>
    <div id="function_buttonset">
        <button id="view-quest">Go</button><?php if ($_SESSION['status'] == 'admin' || $access) { 
            echo '<button id="edit-quest">Edit</button>';
            echo '<button id="delete-quest">Delete</button>';
            echo '<button id="duplicate-quest">Duplicate</button>';
            echo '<button id="data-download">Data</button>';
            echo '<button id="get-json">Structure</button>';
        } ?>
    </div>
</div>

<table class="info"> 
    <tr><td>Name:</td> <td><?= $itemdata['name'] ?></td></tr>
    <tr><td>Status:</td> <td><?= $status ?></td></tr>
    <tr><td>Created on:</td> <td><?= $itemdata['create_date'] ?></td></tr>
    <tr><td>Owners:<br><?php if ($_SESSION['status'] == 'admin') { echo '<button class="tinybutton"  id="owner-change">Change</button>'; } ?></td> 
        <td>
            <ul id='owner-edit'>
                <?= $owner_edit ?>
            </ul>
            <?php if ($_SESSION['status'] == 'admin') { ?>
            <input id='owner-add-input' type='text' > (<a id='owner-add'>add</a>)
            <?php } ?>
        </td></tr>
    <?php
        if (count($setslist) > 0) {
            echo "<tr><td>In Sets:</td> <td>";
            echo $insets->get_element();
            echo '<button class="tinybutton" id="gosets">Go</button></td></tr>';
        }
    ?>
    <tr><td>Labnotes:</td> <td><pre><?= ifEmpty($itemdata['labnotes'], '<span class="error">Please add labnotes</span>') ?></pre></td></tr>
    <tr><td>Completed by:</td> <td> <?= number_format($data['total_c']) ?> people: 
                                <?= number_format($data['total_male']) ?> men; 
                                <?= number_format($data['total_female']) ?> women</td></tr>
    <tr><td>Last completion:</td> <td><?= $data['last_completion'] ?></td></tr>
    <tr><td>Time to complete:<div class="note">(excluding slowest 5%)</div></td> <td><div id="time_container"></div></td></tr>
    
    <tr><td>Type:</td> <td><?= $itemdata['questtype'] ?></td></tr>
    <tr><td>Order:</td> <td><?= $itemdata['quest_order'] ?></td></tr>
    <?php if (!empty($itemdata['url'])) { ?>
        <tr><td>URL:</td> <td><?= $itemdata['url'] ?></td></tr>
    <?php } ?>
    <tr><td>Restrictions:</td> <td><?= $itemdata['sex'] ?> 
        ages <?= is_null($itemdata['lower_age']) ? 'any' : $itemdata['lower_age'] ?> 
        to <?= is_null($itemdata['upper_age']) ? 'any' : $itemdata['upper_age'] ?> years</td></tr>
    <tr><td>Instructions:</td> <td><pre><?= $itemdata['instructions'] ?></pre></td></tr>
    <tr><td>Feedback:</td> <td><?= $itemdata['feedback_general'] ?><br><?= $itemdata['feedback_specific'] ?></td></tr>
</table>

<table class="question_info">
<thead>
    <tr><th>Qid</th><th>DV name</th><th>Question</th><th>Options</th></tr>
</thead>
<tbody>
    <?= $questlist_info ?>
</tbody>
</table>

<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script src="/include/js/highcharts/highcharts-<?= HIGHCHARTS ?>.js"></script>
<script src="/include/js/highcharts/<?= (MOBILE) ? 'mobile_' : '' ?>theme.js"></script> 
    
<script>
    var chart;

    if (<?= ifEmpty($data['total_c'], 0) ?> > 0) {
        // get time graph
        $('#time_container').css('background', 'url("/images/loaders/loading.gif") center center no-repeat');
        $.ajax({
            type: 'POST',
            url: '/include/scripts/chart',
            data: 'query_text=' + encodeURIComponent('<?= $timechart ?>'),
            success: function(data) {
                //alert(JSON.stringify(data));
                $('#time_container').css('background', 'none');
                chart = new Highcharts.Chart(data);
            },
            dataType: 'json'
        }); 
    } else {
        $('#time_container').hide();
    }
    
    $('#function_buttonset').buttonset();

    $( "#view-quest" ).click(function() {
        window.location = '/quest?id=<?= $itemdata['id'] ?>';
    });
    $( "#edit-quest" ).click(function() {
        window.location = '/res/quest/builder?id=<?= $itemdata['id'] ?>';
    });
    $( "#data-download" ).click(function() { 
        postIt('/res/scripts/download', {
            type: 'quest',
            id: <?= $itemdata['id'] ?>
        });
    });
    $( "#get-json" ).click(function() { 
        postIt('/res/scripts/get_json', {
            table: 'quest',
            id: <?= $itemdata['id'] ?>
        });
    });
    $( "#delete-quest" ).click( function() {
        $( "<div/>").html("Do you really want to delete this questionnaire?").dialog({
            title: "Delete questionnaire",
            position: ['center', 100],
            modal: true,
            buttons: {
                Cancel: function() {
                    $( this ).dialog( "close" );
                },
                "Delete": function() {
                    $( this ).dialog( "close" );
                    $.get("/res/scripts/delete_quest?id=<?= $itemdata['id'] ?>", function(data) {
                        if (data == 'deleted') {
                            window.location = '/res/quest/';
                        } else {
                            $('<div title="Problem with Deletion" />').html(data).dialog();
                        }
                    });
                },
            }
        });
    });
    
    $( "#duplicate-quest" ).click( function() {
        $( "<div/>").html("Do you really want to duplicate this questionnaire?").dialog({
            title: "Duplicate Questionnaire",
            position: ['center', 100],
            modal: true,
            buttons: {
                Cancel: function() {
                    $( this ).dialog( "close" );
                },
                "Duplicate": function() {
                    $( this ).dialog( "close" );
                    $.get("?duplicate&id=<?= $itemdata['id'] ?>", function(data) {
                        var resp = data.split(':');
                        if (resp[0] == 'duplicated' && parseInt(resp[1]) > 1) {
                            window.location = '/res/quest/info?id=' + resp[1];
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
                type: 'quest',
                status: $sel.val(),
                id: <?= $itemdata['id'] ?>
            },
            success: function(data) {
                if (data == 'Status of quest_<?= $itemdata['id'] ?> changed to '+ $sel.val() ) {
                    $sel.css('color', 'inherit');
                } else {
                    growl(data, 30);
                }
            }
        });
    });
    
    $('#gosets').click( function() {
        var s = $('#insets').val();
        window.location.href = "/res/set/info?id=" + s;
    });
    
    $('#goqueries').click( function() {
        var q = $('#inqueries').val();
        window.location.href = "/res/data/?id=" + q;
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
            var new_owner = "<li><span class='new-owner'>" + $('#owner-add-input').val() + "</span> (<a class='owner-delete' owner-id='"+owner_id+"'>delete</a>)</li>";
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
                type: 'quest',
                id: <?= $itemdata['id'] ?>,
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
</script>

<?php

$page->displayFooter();

?>