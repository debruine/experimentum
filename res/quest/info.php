<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
auth(array('student', 'researcher', 'admin'));


$title = array(
    '/res/' => 'Researchers',
    '/res/quest/' => 'Questionnaires',
    '' => 'Info'
);

$styles = array( 
    'br' => 'margin: 0 0 .5em 0;',
    '#time_container' => 'height: 300px; width: 500px;',
    '.question_info, .quest_info' => 'margin-bottom: 1em; width: 100%;'
);


if (MOBILE) {
    $styles['#time_container'] = 'height: 200px; width: 100%; margin-left: -1em;';
}

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
        
    $new_fields = '';
    foreach ($table_schema as $field => $type) {
        $new_fields .= $old_to_new[$field] . ' ' . $type . ',' . ENDLINE;
    }
    
    $query = "CREATE TABLE quest_{$new_id} (
                id INT(11) NOT NULL AUTO_INCREMENT,
                user_id INT(11),
                {$new_fields}
                starttime DATETIME,
                endtime DATETIME,
                INDEX (user_id),
                PRIMARY KEY (id));";
    $q = new myQuery($query);           
    
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

$questdata = $myquest->get_one_array();

// owner functions
$myowners = new myQuery('SELECT user_id, CONCAT(lastname, " ", initials) as name FROM access LEFT JOIN researcher USING (user_id) WHERE type="quest" AND id=' . $quest_id);
$owners = $myowners->get_assoc(false, 'user_id', 'name');
$access = in_array($_SESSION['user_id'], array_keys($owners));

$allowners = new myQuery('SELECT user_id, firstname, lastname, initials, email FROM researcher LEFT JOIN user USING (user_id) WHERE status > 4');
$ownerlisting = $allowners->get_assoc();
$ownerlist = array();
foreach($ownerlisting as $res) {
    $user_id = $res['user_id'];
    $lastname = htmlspecialchars($res['lastname']);
    $firstname = htmlspecialchars($res['firstname']);
    $initials = htmlspecialchars($res['initials']);
    $email = htmlentities($res['email'], ENT_QUOTES);
    
    $ownerlist[] = "\n{ value: '{$user_id}', name: '{$lastname} {$initials}', label: '{$firstname} {$lastname} {$email}' }";
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
    $status_chooser = new select('status', 'status', $questdata['status']);
    $status_chooser->set_null(false);
    $status_chooser->set_options(array(
        'test' => 'test',
        'active' => 'active',
        'inactive' => 'inactive'
    ));
    $status = $status_chooser->get_element();
} else {
    $status = '(' . $questdata['status'] . ')';
}

// get stats on participant completion of the questeriment
/*
$mydata = new myQuery('SELECT COUNT(*) as total_c,
                        COUNT(IF(sex="male",1,NULL)) as total_male,
                        COUNT(IF(sex="female",1,NULL)) as total_female,
                        MAX(endtime) as last_completion
                        FROM quest_' . $quest_id . ' LEFT JOIN user USING (user_id)
                        WHERE status>1 AND status<4');
$data = $mydata->get_one_array();  
*/
$mysets = new myQuery('SELECT set_id, CONCAT(set_id, ": ", name) as si from set_items LEFT JOIN sets ON sets.id=set_id where item_type="quest" and item_id=' . $quest_id);
$setslist = $mysets->get_assoc(false, 'set_id', 'si');  
$insets = new select('insets', 'insets');
$insets->set_options($setslist);
$insets->set_null(false);

// create chart of time taken

$timechart = 'SELECT @percentile:=t1.val FROM (SELECT @rownum:=@rownum+1 as row_number, (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime)) as val FROM quest_' . $quest_id . ' AS d,  (SELECT @rownum:=0) r WHERE (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))>0 ORDER BY (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime)) ) as t1, (SELECT count(*) as total_rows FROM quest_' . $quest_id . ' AS d WHERE (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))>0) as t2 WHERE t1.row_number=floor(95*total_rows/100)+1; CREATE TEMPORARY TABLE tmp_score SELECT ROUND((UNIX_TIMESTAMP(endtime)-UNIX_TIMESTAMP(starttime))/60,1) as score FROM quest_' . $quest_id . '; SELECT @totalp := COUNT(*) FROM tmp_score WHERE score IS NOT NULL GROUP BY NULL; SELECT "   " as title, "Minutes" as xlabel, "Proportion of Participants" as ylabel, 0 as ymin, 0 as xmin, score as xcat, COUNT(*)/@totalp as dv, "line" as chart_type, "reverse" as reverse, "time_container" as container FROM tmp_score WHERE score IS NOT NULL AND score<=(@percentile/60) AND score>0 GROUP BY score;';
    
/****************************************************/
/* !Display Page */
/***************************************************/


$page = new page($title);
$page->set_logo(false);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<h2>quest <?= $questdata['id'] ?>: <?= $questdata['res_name'] ?> <?= $status ?></h2>

<div class='toolbar'>
    <div id="function_buttonset">
        <button id="view-quest">Go</button><?php if ($_SESSION['status'] == 'admin' || $access) { 
            echo '<button id="edit-quest">Edit</button>';
            echo '<button id="delete-quest">Delete</button>';
            echo '<button id="duplicate-quest">Duplicate</button>';
            echo '<button id="data-quest">Data</button>';
        } ?>
    </div>
</div>

<table class="quest_info"> 
    <tr><td>Name:</td> <td><?= $questdata['name'] ?></td></tr>
    <tr><td>Created on:</td> <td><?= $questdata['create_date'] ?></td></tr>
    <tr><td>Owners:<br><?php if ($_SESSION['status'] == 'admin') { echo '<button class="tinybutton"  id="owner-change">Change</button>'; } ?></td> 
        <td>
            <ul id='owner-edit'>
                <?= $owner_edit ?>
            </ul>
            <?php if ($_SESSION['status'] == 'admin') { ?>
            <input id='owner-add-input' type='text' > (<a id='owner-add'>add</a>)
            <?php } ?>
        </td></tr>
    <tr><td>Labnotes:</td> <td><pre><?= ifEmpty($questdata['labnotes'], '<span class="ui-state-error">Please add labnotes</span>') ?></pre></td></tr>
    <?php
        if (count($setslist) > 0) {
            echo "<tr><td>In Sets:</td> <td>";
            echo $insets->get_element();
            echo '<button class="tinybutton" id="gosets">Go</button></td></tr>';
        }
    ?>
    <tr><td>Completed by:</td> <td> <?= number_format($data['total_c']) ?> people: 
                                <?= number_format($data['total_male']) ?> men; 
                                <?= number_format($data['total_female']) ?> women</td></tr>
    <tr><td>Last completion:</td> <td><?= $data['last_completion'] ?></td></tr>
    <tr><td>Time to complete:<div class="note">(excluding slowest 5%)</div></td> <td><div id="time_container"></div></td></tr>
    
    <tr><td>Type:</td> <td><?= $questdata['questtype'] ?></td></tr>
    <tr><td>Order:</td> <td><?= $questdata['quest_order'] ?></td></tr>
    <?php if (!empty($questdata['url'])) { ?>
        <tr><td>URL:</td> <td><?= $questdata['url'] ?></td></tr>
    <?php } ?>
    <tr><td>Restrictions:</td> <td><?= $questdata['sex'] ?> who prefer <?= is_null($questdata['sexpref']) ? 'unspecified sex' : $questdata['sexpref'] ?>, 
        ages <?= is_null($questdata['lower_age']) ? 'any' : $questdata['lower_age'] ?> 
        to <?= is_null($questdata['upper_age']) ? 'any' : $questdata['upper_age'] ?> years</td></tr>
    <tr><td>Instructions:</td> <td><?= $questdata['instructions'] ?></td></tr>
    <tr><td><a href="/quest/feedback?id=<?= $questdata['id'] ?>">Feedback</a>:</td> 
        <td><?= $questdata['feedback_general'] ?><br /><?= $questdata['feedback_specific'] ?></td></tr>
    <?php if (!empty($questdata['forward'])) { ?>
        <tr><td>Forward to URL:</td> <td><a href="<?= $questdata['forward'] ?>"><?= $questdata['forward'] ?></a></td></tr>
    <?php } ?>
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

    $j(function() {
        if (<?= ifEmpty($data['total_c'], 0) ?> > 0) {
            // get time graph
            $j('#time_container').css('background', 'url("/images/loaders/loading.gif") center center no-repeat');
            $j.ajax({
                type: 'POST',
                url: '/include/scripts/chart',
                data: 'query_text=' + encodeURIComponent('<?= $timechart ?>'),
                success: function(data) {
                    //alert(JSON.stringify(data));
                    $j('#time_container').css('background', 'none');
                    chart = new Highcharts.Chart(data);
                },
                dataType: 'json'
            }); 
        } else {
            $j('#time_container').hide();
        }
        
        $j('#function_buttonset').buttonset();
    
        $j( "#view-quest" ).click(function() {
            window.location = '/quest?id=<?= $questdata['id'] ?>';
        });
        $j( "#edit-quest" ).click(function() {
            window.location = '/res/quest/builder?id=<?= $questdata['id'] ?>';
        });
        $j( "#data-quest" ).click(function() {
            window.location = '/res/data/index?quest_id=<?= $questdata['id'] ?>';
        });
        $j( "#delete-quest" ).click( function() {
            $j( "<div/>").html("Do you really want to delete this questionnaire?").dialog({
                title: "Delete questionnaire",
                position: ['center', 100],
                modal: true,
                buttons: {
                    Cancel: function() {
                        $j( this ).dialog( "close" );
                    },
                    "Delete": function() {
                        $j( this ).dialog( "close" );
                        $j.get("/res/scripts/delete_quest?id=<?= $questdata['id'] ?>", function(data) {
                            if (data == 'deleted') {
                                window.location = '/res/quest/';
                            } else {
                                $j('<div title="Problem with Deletion" />').html(data).dialog();
                            }
                        });
                    },
                }
            });
        });
        
        $j( "#duplicate-quest" ).click( function() {
            $j( "<div/>").html("Do you really want to duplicate this questionnaire?").dialog({
                title: "Duplicate Questionnaire",
                position: ['center', 100],
                modal: true,
                buttons: {
                    Cancel: function() {
                        $j( this ).dialog( "close" );
                    },
                    "Duplicate": function() {
                        $j( this ).dialog( "close" );
                        $j.get("?duplicate&id=<?= $questdata['id'] ?>", function(data) {
                            var resp = data.split(':');
                            if (resp[0] == 'duplicated' && parseInt(resp[1]) > 1) {
                                window.location = '/res/quest/info?id=' + resp[1];
                            } else {
                                $j('<div title="Problem with Duplication" />').html(data).dialog();
                            }
                        });
                    },
                }
            });
        });
        
        $j( "#status" ).css('fontWeight', 'normal').change( function() {
            var $sel = $j(this);
            $sel.css('color', 'red');

            $j.ajax({
                url: '/res/scripts/status',
                type: 'POST',
                data: {
                    type: 'quest',
                    status: $sel.val(),
                    id: <?= $questdata['id'] ?>
                },
                success: function(data) {
                    if (data == 'Status of quest_<?= $questdata['id'] ?> changed to '+ $sel.val() ) {
                        $sel.css('color', 'inherit');
                    } else {
                        growl(data, 30);
                    }
                }
            });
        });
        
        $j('#gosets').click( function() {
            var s = $j('#insets').val();
            window.location.href = "/res/set/info?id=" + s;
        });
        
        $j('#goqueries').click( function() {
            var q = $j('#inqueries').val();
            window.location.href = "/res/data/?id=" + q;
        });
              
        $j('html').on("click", ".owner-delete", function() {
            if ($j(this).text() == 'delete') {
                $j(this).text('undelete');
                $j(this).prev().addClass('delete-owner');
            } else {
                $j(this).text('delete');
                $j(this).prev().removeClass('delete-owner');
            }
        });
        
        $j('button.tinybutton').button();
        
        $j('#owner-add-input').autocomplete({
            source: [<?= implode(",", $ownerlist) ?>],
            focus: function( event, ui ) {
                $j(this).val(ui.item.name);
                return false;
            },
            select: function( event, ui ) {
                $j(this).val(ui.item.name).data('id', ui.item.value);
                return false;
            }
        }).data('id', 0);
        
        $j( "#owner-add" ).click( function() {
            var owner_id = $j('#owner-add-input').data('id');
            
            if (owner_id == '' || owner_id == 0) { return false; }
            
            if ($j('#owner-edit .owner-delete[owner-id=' + owner_id + ']').length == 0) {
                var new_owner = "<li><span class='new-owner'>" + $j('#owner-add-input').val() + "</span> (<a class='owner-delete' owner-id='"+owner_id+"'>delete</a>)</li>";
                $j('#owner-edit').append(new_owner);
            } else {
                growl("You can't add a duplicate owner.");
            }
            $j('#owner-add-input').val('').data('id','');
        });
        
        $j( "#owner-change" ).click( function() {
            var to_add = [];
            var to_delete = [];
            $j('#owner-edit .owner-delete').each( function() {
                var $this = $j(this);
                
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
            
            $j.ajax({
                url: '/res/scripts/owners',
                type: 'POST',
                data: {
                    type: 'quest',
                    id: <?= $questdata['id'] ?>,
                    add: to_add,
                    delete: to_delete
                },
                success: function(data) {
                    if (data) {
                        growl("Something went wrong");
                    } else {
                        $j('#owner-edit .delete-owner').closest('li').remove();
                        $j('#owner-edit span').removeClass('new-owner');
                    }
                }
            });
        });

    });
    
</script>

<?php

$page->displayFooter();

?>