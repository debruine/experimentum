<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
require_once DOC_ROOT . '/include/classes/Parsedown.php';
auth($RES_STATUS);

if (validID($_GET['id']) && !permit('exp', $_GET['id'])) header('Location: /res/');

$title = array(
    '/res/' => 'Researchers',
    '/res/exp/' => 'Experiments',
    '' => 'Info'
);

$styles = array( 
    'br' => 'margin: 0 0 .5em 0;',
    '#trial_list, .adapt_list' => 'margin: 0 auto;',
    '.trial img' => 'width: 90px; display: inline; margin: 0; box-shadow: 1px 1px 2px rgba(0,0,0,.5);',
    '.trialname' => 'display: block; clear: right; margin: 5px 0 0 0;',
    '#time_container' => 'height: 300px; width: 500px;',
    '#image_table .trial' => 'border-top: 1px solid grey;',
    "select" => "max-width: 25em",
    '#function_buttonset, #image_list_toggle' => 'display: inline-block; float: left;'
);


/****************************************************
 * Get Experiment Data
 ***************************************************/
 
if (validID($_GET['id'])) {
    $exp_id = intval($_GET['id']);
} else {
    header('Location: /res/exp/');
}

$myexp = new myQuery(
    'SELECT exp.*, 
            GROUP_CONCAT(CONCAT(dv,": ",display) SEPARATOR "<br />") as buttons
       FROM exp 
  LEFT JOIN buttons ON exp.id=exp_id 
      WHERE exp.id=' . $exp_id . ' 
   GROUP BY exp.id'
);
                        
if ($myexp->get_num_rows() == 0) { header('Location: /res/exp/'); }

$itemdata = $myexp->get_one_array();

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
$myowners = new myQuery(
    'SELECT user_id, CONCAT(lastname, " ", firstname) as name 
       FROM access 
  LEFT JOIN res USING (user_id) 
      WHERE type="exp" AND id=' . $exp_id
);
$owners = $myowners->get_assoc(false, 'user_id', 'name');
$access = in_array($_SESSION['user_id'], array_keys($owners));

$allowners = new myQuery(
    'SELECT user_id, firstname, lastname, email 
       FROM res 
  LEFT JOIN user USING (user_id) 
      WHERE status > 4'
);
$ownerlisting = $allowners->get_assoc();
$ownerlist = array();
foreach($ownerlisting as $res) {
    $user_id = $res['user_id'];
    $lastname = htmlspecialchars($res['lastname'], ENT_QUOTES);
    $firstname = htmlspecialchars($res['firstname'], ENT_QUOTES);
    $email = htmlentities($res['email'], ENT_QUOTES);
    
    $ownerlist[] = "\n{ value: '{$user_id}', name: '{$lastname} {$firstname}', label: '{$firstname} {$lastname} {$email}' }";
}

$owner_edit = "";
foreach($owners as $id => $name) {
    $owner_edit .= "<li><span>{$name}</span>";
    if ($_SESSION['status'] == 'admin') { 
        $owner_edit .= " (<a class='owner-delete' owner-id='{$id}'>delete</a>)</li>";
    }
}

// get status changer for admins
if ($_SESSION['status'] == 'admin') {
    $status_chooser = new select('status', 'status', $itemdata['status']);
    $status_chooser->set_null(false);
    $status_chooser->set_options(array(
        'test' => 'test',
        'active' => 'active',
        'archive' => 'archive'
    ));
    $status = $status_chooser->get_element();
} else {
    $status = $itemdata['status'];
}

// get data on all trials
$mytrials = new myQuery(
    'SELECT trial_n, t.name, 
            GROUP_CONCAT(x.path ORDER BY n SEPARATOR ";") as xafc,
            l.path as left_stim,
            c.path as center_stim,
            r.path as right_stim,
            l.type as ltype,
            c.type as ctype,
            r.type as rtype,
            question,
            label1,
            label2,
            label3,
            label4,
            q_image,
            color
       FROM trial as t
  LEFT JOIN xafc USING (exp_id, trial_n)
  LEFT JOIN stimuli AS l ON (l.id=left_img)
  LEFT JOIN stimuli AS c ON (c.id=center_img)
  LEFT JOIN stimuli AS r ON (r.id=right_img)
  LEFT JOIN stimuli AS x ON (x.id=xafc.image)
      WHERE exp_id=' . $exp_id . ' GROUP BY trial_n'
);
$trials = $mytrials->get_assoc(false, 'trial_n');

// get image base url
$imagelist = array();
foreach ($trials as $t) {
    if (!empty($t['left_stim'])) $imagelist[] = $t['left_stim'];
    if (!empty($t['center_stim'])) $imagelist[] = $t['center_stim'];
    if (!empty($t['right_stim'])) $imagelist[] = $t['right_stim'];
    if (!empty($t['xafc'])) {
        $x = explode(';', $t['xafc']);
        foreach ($x as $img) { $imagelist[] = $img; }
    }
}

$common_path = common_path($imagelist);

// exclude empty cells for a trial table
$table_query = 'SELECT trial_n, name';
if (!empty($trials[1]['left_stim'])) $table_query .= ', l.path as left_stim';
if (!empty($trials[1]['center_stim'])) $table_query .= ', c.path as center_stim';
if (!empty($trials[1]['right_stim'])) $table_query .= ', r.path as right_stim';
if (!empty($trials[1]['xafc'])) $table_query .= ', GROUP_CONCAT(x.path ORDER BY n SEPARATOR "<br />") as xafc';
if (!empty($trials[1]['question'])) $table_query .= ', question';
if (!empty($trials[1]['label1'])) $table_query .= ', label1, label2, label3, label4';
if (!empty($trials[1]['q_image'])) $table_query .= ', q_image';
if (!empty($trials[1]['color'])) $table_query .= ', color';
$table_query .= ' FROM trial 
             LEFT JOIN xafc USING (exp_id, trial_n)
             LEFT JOIN stimuli AS l ON (l.id=left_img)
             LEFT JOIN stimuli AS c ON (c.id=center_img)
             LEFT JOIN stimuli AS r ON (r.id=right_img)
             LEFT JOIN stimuli AS x ON (x.id=xafc.image)
                 WHERE exp_id=' . $exp_id . ' GROUP BY trial_n';
$mytable = new myQuery($table_query);
$trial_table = $mytable->get_result_as_table();
$trial_table = str_replace($common_path, '', $trial_table);

if (substr($itemdata['subtype'], 0, 5) == 'adapt') {
    // get adaptation trial info
    $adapt_query = new myQuery(
        'SELECT trial_n, exposure, a.version, name, notes, question,
                li.path as left_stim,
                ci.path as center_stim,
                ri.path as right_stim,
                li.type as ltype,
                ci.type as ctype,
                ri.type as rtype
           FROM adapt_trial as a
      LEFT JOIN versions AS v ON a.exp_id=v.exp_id AND a.version = v.version
      LEFT JOIN stimuli AS li ON (li.id=left_img)
      LEFT JOIN stimuli AS ci ON (ci.id=center_img)
      LEFT JOIN stimuli AS ri ON (ri.id=right_img)
          WHERE a.exp_id=' . $exp_id . ' ORDER BY a.version, trial_n'
    );
        
    $adapt_trials = $adapt_query->get_assoc();
}

// get stats on participant completion of the experiment
$mydata = new myQuery(
    'SELECT COUNT(*) as total_c,
            COUNT(IF(sex="male",1,NULL)) as total_male,
            COUNT(IF(sex="female",1,NULL)) as total_female,
            MAX(dt) as last_completion
       FROM exp_data 
  LEFT JOIN user USING (user_id)
      WHERE status>1 AND status<4 AND exp_id='.$exp_id.'
   GROUP BY user_id, session_id'
);
$data = $mydata->get_one_array();    

$mysets = new myQuery('
    SELECT set_id, CONCAT(set_id, ": ", name) as si 
      FROM set_items 
 LEFT JOIN sets ON sets.id=set_id 
     WHERE item_type="exp" AND item_id=' . $exp_id
);
$setslist = $mysets->get_assoc(false, 'set_id', 'si');    
$insets = new select('insets', 'insets');
$insets->set_options($setslist);
$insets->set_null(false);

// create chart of time taken
$timechart = 'CREATE TEMPORARY TABLE tmp_ln ' . 
             'SELECT SUM(rt) as total_time, ' .
             '  COUNT(*) as n ' .
             'FROM exp_data ' .
             'WHERE exp_id='.$exp_id. ' ' .
             'GROUP BY session_id, user_id ' .
             'HAVING n=' . $itemdata['random_stim'] . '; ' .
                
             'CREATE TEMPORARY TABLE tmp_ln2 ' .
             'SELECT * FROM tmp_ln; ' .
             
             'SELECT @percentile:=t1.val ' .
             'FROM (SELECT @rownum:=@rownum+1 as row_number, ' .
             '             total_time as val FROM tmp_ln AS d, ' .
             '             (SELECT @rownum:=0) r ORDER BY total_time ) as t1, ' .
             '             (SELECT count(*) as total_rows FROM tmp_ln2 AS d) as t2 ' .
             'WHERE t1.row_number=floor(95*total_rows/100)+1; ' .
             
             'CREATE TEMPORARY TABLE tmp_score ' .
             'SELECT ROUND(total_time/1000/60,2) as score ' .
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

<h2>Exp <?= $itemdata['id'] ?>: <?= $itemdata['res_name'] ?></h2>

<div class='toolbar'>
    <div id="function_buttonset">
        <button id="view-exp">Go</button><?php if ($_SESSION['status'] == 'admin' || $access) { 
            echo '<button id="edit-exp">Edit</button>';
            echo '<button id="delete-exp">Delete</button>';
            echo '<button id="duplicate-exp">Duplicate</button>';
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
    <tr><td>Labnotes:</td> <td><pre><?= ifEmpty($itemdata['labnotes'], '<span class="error">Please add labnotes</span>') ?></pre></td></tr>
    <?php
        if (count($setslist) > 0) {
            echo "<tr><td>In Sets:</td> <td>";
            echo $insets->get_element();
            echo '<button class="tinybutton" id="gosets">Go</button></td></tr>';
        }
        
        if (count($querylist) > 0) {
            echo '<tr><td>In Queries:</td> <td>';
            echo $inqueries->get_element();
            echo '<button class="tinybutton" id="goqueries">Go</button></td></tr>';
        }
    ?>
    <tr><td>Completed by:</td> <td>    <?= number_format($data['total_c']) ?> people: 
                                <?= number_format($data['total_male']) ?> men; 
                                <?= number_format($data['total_female']) ?> women</td></tr>
    <tr><td>Last completion:</td> <td><?= $data['last_completion'] ?></td></tr>
    <tr><td>Time to complete:<div class="note">(excluding slowest 5%)</div></td> <td><div id="time_container"></div></td></tr>
    
    <tr><td>Trials:</td> <td><?= $itemdata['random_stim'] ?> of <?= count($trials) ?></td></tr>
    <tr><td>Stimulus Path:</td> <td><?= $common_path ?></td></tr>
    
    <tr><td>Type:</td> <td><?= $itemdata['subtype'] ?> <?= $itemdata['exptype'] ?> with <?= $itemdata['orient'] ?> image orientation</td></tr>
    <?php if (count($exposure) > 0) { ?>
        <tr><td>Adapt time:</td> <td><?= implode(', ', array_keys($exposure)) ?> ms per trial</td></tr>
    <?php } 
        if (!empty($itemdata['label1'])) { ?>
        <tr><td>Labels:</td> <td>    <?= $itemdata['label1'] ?><br />
                                <?= $itemdata['label2'] ?><br />
                                <?= $itemdata['label3'] ?><br />
                                <?= $itemdata['label4'] ?>
                         </td></tr>
    <?php } else if (!empty($itemdata['buttons'])) {?>
        <tr><td>Buttons:</td> <td><?= $itemdata['buttons'] ?></td></tr>
    <?php } else if (!empty($itemdata['low_anchor'])) { ?>
        <tr><td>Anchors:</td> <td>    <?= $itemdata['low_anchor'] ?> - <?= $itemdata['high_anchor'] ?></td></tr>
    <?php } ?>
    <?php if ($itemdata['rating_range']>0) { ?>
        <tr><td>Rating range:</td> <td>    <?= $itemdata['rating_range'] ?></td></tr>
    <?php } ?>
    <tr><td>Design:</td> <td><?= $itemdata['design'] ?>-subjects</td></tr>
    <tr><td>Order:</td> <td><?= $itemdata['trial_order'] ?></td></tr>
    <tr><td>Side:</td> <td><?= $itemdata['side'] ?></td></tr>
    <tr><td>Restrictions:</td> <td><?= $itemdata['sex'] ?> 
        ages <?= is_null($itemdata['lower_age']) ? 'any' : $itemdata['lower_age'] ?> 
        to <?= is_null($itemdata['upper_age']) ? 'any' : $itemdata['upper_age'] ?> years</td></tr>
    <tr><td>Instructions:</td> <td><pre><?= $itemdata['instructions'] ?></pre></td></tr>
    <tr><td>Question:</td> <td><?= ifEmpty($itemdata['question'], '<i>Varies by trial</i>') ?></td></tr>
    <tr><td>Feedback:</td> <td><?= $itemdata['feedback_general'] ?><br><?= $itemdata['feedback_specific'] ?></td></tr>
</table>

<div class='toolbar'>
    <div id="image_list_toggle">
        <input type="radio" id="image_toggle" name="radio" checked="checked" /><label for="image_toggle">Images</label><input 
        type="radio" id="list_toggle" name="radio" /><label for="list_toggle">List</label>
    </div>
</div>

<?php

if (substr($itemdata['subtype'], 0, 5) == 'adapt') {
    $exposure = array(); // check if all exposures are the same
    $version = $adapt_trials[0]['version'];
    echo '<div class="adapt_list">V' . $version . ': ' . $adapt_trials[0]['name'];
    
    foreach ($adapt_trials as $t) {
        if ($t['version'] != $version) {
            $version = $t['version'];
            echo '</div><div class="adapt_list">V' . $version . ': ' . $t['name'];
        }
        
        $exposure[$t['exposure']]++;
    
        echo '        <div class="trial" exposure="' . $t['exposure'] . '">' . ENDLINE;
        
        // show images
        if (!empty($t['left_stim'])) echo '            <img src="' . $t['left_stim'] . '" title="' . $t['left_stim'] . '" />' . ENDLINE;
        if (!empty($t['center_stim'])) echo '            <img src="' . $t['center_stim'] . '" title="' . $t['center_stim'] . '" />' . ENDLINE;
        if (!empty($t['right_stim'])) echo '            <img src="' . $t['right_stim'] . '" title="' . $t['right_stim'] . '" />' . ENDLINE;
        
        echo '        </div>' . ENDLINE;
    }
    
    echo '</div>';
}
    
?>
    
<div id="trial_list">
    <div id="trial_table">
        <?= $trial_table ?>
    </div>

    <div id="image_table">
<?php

foreach ($trials as $t) {
    
    echo '        <div class="trial">' . ENDLINE;
    echo '            <span class="trialname">t' . $t['trial_n'] . ': ' . $t['name'] . '</span>' . ENDLINE;
    
    // show images
    if (!empty($t['left_stim'])) {
        if ($t['ltype'] == 'image') {
            echo '            <img src="' . $t['left_stim'] . '" title="' . $t['left_stim'] . '" />' . ENDLINE;
        } else if ($t['ltype'] == 'audio') {
            $audioname = str_replace($common_path, '',$t['left_stim']);
            echo "            Hi: $audioname<br><audio controls>
                    <source src='{$t['left_stim']}.ogg' type='audio/ogg' autoplay='false' />
                    <source src='{$t['left_stim']}.mp3' type='audio/mp3' autoplay='false' />
                </audio><br>" . ENDLINE;
        }
    }
    if (!empty($t['center_stim'])) {
        if ($t['ctype'] == 'image') {
            echo '            <img src="' . $t['center_stim'] . '" title="' . $t['center_stim'] . '" />' . ENDLINE;
        } else if ($t['ctype'] == 'audio') {
            $audioname = str_replace($common_path, '',$t['center_stim']);
            echo "            $audioname<br><audio controls>
                    <source src='{$t['center_stim']}.ogg' type='audio/ogg' autoplay='false' />
                    <source src='{$t['center_stim']}.mp3' type='audio/mp3' autoplay='false' />
                </audio><br>" . ENDLINE;
        }
    }
    if (!empty($t['right_stim'])) {
        if ($t['rtype'] == 'image') {
            echo '            <img src="' . $t['right_stim'] . '" title="' . $t['right_stim'] . '" />' . ENDLINE;
        } else if ($t['rtype'] == 'audio') {
            $audioname = str_replace($common_path, '',$t['right_stim']);
            echo "            Lo: $audioname<br><audio controls>
                    <source src='{$t['right_stim']}.ogg' type='audio/ogg' autoplay='false' />
                    <source src='{$t['right_stim']}.mp3' type='audio/mp3' autoplay='false' />
                </audio><br>" . ENDLINE;
        }
    }
    if (!empty($t['xafc'])) {
        $x = explode(';', $t['xafc']);
        foreach ($x as $img) {
            echo "            <img src='$img' title='$img' />" . ENDLINE;
        }
    }
    
    echo '        </div>' . ENDLINE;
}

?>

    </div>
</div>

<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script src="/include/js/highcharts/highcharts-<?= HIGHCHARTS ?>.js"></script>
<script src="/include/js/highcharts/<?= (MOBILE) ? 'mobile_' : '' ?>theme.js"></script>    
    
<script>
    var chart;
    // get time graph
    if (<?= ifEmpty($data['total_c'],'0') ?> > 0) {
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

    $( "#view-exp" ).click(function() {
        window.location = '/exp?id=<?= $itemdata['id'] ?>';
    });
    $( "#edit-exp" ).click(function() {
        window.location = '/res/exp/builder?id=<?= $itemdata['id'] ?>';
    });
    $( "#edit-trials" ).click(function() {
        window.location = '/res/exp/trials?id=<?= $itemdata['id'] ?>';
    });
    $( "#edit-adapt" ).click(function() {
        window.location = '/res/exp/adapt?id=<?= $itemdata['id'] ?>';
    });
    $( "#data-download" ).click(function() { 
        postIt('/res/scripts/download', {
            type: 'exp',
            id: <?= $itemdata['id'] ?>
        });
    });
    $( "#get-json" ).click(function() { 
        postIt('/res/scripts/get_json', {
            table: 'exp',
            id: <?= $itemdata['id'] ?>
        });
    });
    
    $( "#delete-exp" ).click( function() {
        $( "<div/>").html("Do you really want to delete this experiment?").dialog({
            title: "Delete Experiment",
            position: ['center', 100],
            modal: true,
            buttons: {
                Cancel: function() {
                    $( this ).dialog( "close" );
                },
                "Delete": function() {
                    $( this ).dialog( "close" );
                    $.get("/res/scripts/delete_exp?id=<?= $itemdata['id'] ?>", function(data) {
                        if (data == 'deleted') {
                            window.location = '/res/exp/';
                        } else {
                            $('<div title="Problem with Deletion" />').html(data).dialog();
                        }
                    });
                },
            }
        });
    });
    
    $( "#duplicate-exp" ).click( function() {
        $( "<div/>").html("Do you really want to duplicate this experiment?").dialog({
            title: "Duplicate Experiment",
            position: ['center', 100],
            modal: true,
            buttons: {
                Cancel: function() {
                    $( this ).dialog( "close" );
                },
                "Duplicate": function() {
                    $( this ).dialog( "close" );
                    /*$.get("?duplicate&id=<?= $itemdata['id'] ?>", function(data) {
                        var resp = data.split(':');
                        if (resp[0] == 'duplicated' && parseInt(resp[1]) > 1) {
                            window.location = '/res/exp/info?id=' + resp[1];
                        } else {
                            $('<div title="Problem with Duplication" />').html(data).dialog();
                        }
                    });*/
                    $.ajax({
                        url: "/res/scripts/exp_duplicate",
                        type: 'POST',
                        dataType: 'json',
                        data: {id: <?= $itemdata['id'] ?>},
                        success: function(data) {
                            if (!data.error) {
                                window.location = '/res/exp/info?id=' + data.new_id;
                            } else {
                                $('<div title="Problem with Duplication" />').html(data.error).dialog();
                            }
                        }
                    });
                },
            }
        });
    });
        
    $( "#image_list_toggle" ).buttonset();
    $( "#list_toggle" ).click(function() { 
        $('#image_table').hide();
        $('#trial_table').show();
    });
    $( "#image_toggle" ).click(function() { 
        $('#image_table').show();
        $('#trial_table').hide();
    });
    
    $( "#status" ).css('fontWeight', 'normal').change( function() {
        var $sel = $(this);
        $sel.css('color', 'red');

        $.ajax({
            url: '/res/scripts/status',
            type: 'POST',
            data: {
                type: 'exp',
                status: $sel.val(),
                id: <?= $itemdata['id'] ?>
            },
            success: function(data) {
                if (data == 'Status of exp_<?= $itemdata['id'] ?> changed to '+ $sel.val() ) {
                    $sel.css('color', 'inherit');
                } else {
                    growl(data, 30);
                }
            }
        });
    });
    
    $('#trial_table').hide();
    
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
                type: 'exp',
                id: <?= $itemdata['id'] ?>,
                add: to_add,
                delete: to_delete
            },
            success: function(data) {
                if (data) {
                    growl(data);
                } else {
                    $('#owner-edit .delete-owner').closest('li').remove();
                    $('#owner-edit span').removeClass('new-owner');
                }
            }
        });
    });
    $('#list_toggle').click();
    
</script>

<?php

$page->displayFooter();

?>