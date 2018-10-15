<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
auth(array('student', 'researcher', 'admin'));


$title = array(
    '/res/' => 'Researchers',
    '/res/exp/' => 'Experiments',
    '' => 'Info'
);

$styles = array( 
    'br' => 'margin: 0 0 .5em 0;',
    '#trial_list, .adapt_list' => 'float: left;',
    '.adapt_list' => 'margin-right: 1em;',
    '.trial img' => 'width: 90px; display: inline; margin: 0; box-shadow: 1px 1px 2px rgba(0,0,0,.5);',
    '.trialname' => 'display: block; clear: right; margin: 5px 0 0 0;',
    '#time_container' => 'height: 300px; width: 500px;',
    '#image_table .trial' => 'border-top: 1px solid grey;',
    "select" => "max-width: 25em",
    "#infotable td" => "min-width: 25%;",
    "#infotable > tbody> tr.even" => "background: rgba(0,0,0,0);",
    '#expinfo, #infotable' => 'margin-bottom: 1em; width: 100%;'
);

// !AJAX duplicate experiment
if (array_key_exists('duplicate', $_GET) && validID($_GET['id'])) {
    $old_id = $_GET['id'];
    
    // duplicate exp table entry
    $q = new myQuery('SELECT * FROM exp WHERE id=' . $old_id);
    $old_info = $q->get_one_array();
    unset($old_info['id']);
    unset($old_info['res_name']);
    unset($old_info['status']);
    unset($old_info['create_date']);
    $fields = array_keys($old_info);
    
    $query = sprintf("INSERT INTO exp (create_date, status, res_name, %s) 
        SELECT NOW(), 'test', CONCAT(res_name, ' (Duplicate)'), %s 
        FROM exp WHERE id='%d'",
        implode(", ", $fields),
        implode(", ", $fields),
        $old_id
    );
    $query = str_replace(' range', ' `range`', $query);
    $q = new myQuery($query);
    $new_id = $q->get_insert_id();
    
    if (!validID($new_id)) {
        echo "The experiment did not duplicate. The query was <blockquote>$query</blockquote>";
        exit;
    }
    
    $q = new myQuery("UPDATE exp SET feedback_query=REPLACE(feedback_query, 'exp_{$old_id}', 'exp_{$new_id}') WHERE id='{$new_id}'");
    
    // duplicate tables
    duplicateTable("trial", 'exp', $old_id, $new_id);
    duplicateTable("adapt_trial", 'exp', $old_id, $new_id);
    duplicateTable("xafc", 'exp', $old_id, $new_id);
    duplicateTable("versions", 'exp', $old_id, $new_id);
    duplicateTable("buttons", 'exp', $old_id, $new_id);
    
    // duplicate data table
    $q = new myQuery("CREATE TABLE exp_{$new_id} LIKE exp_{$old_id}");
    // set owner/access
    $q = new myQuery("INSERT INTO access (type, id, user_id) VALUES ('exp', $new_id, {$_SESSION['user_id']})");

    
    echo "duplicated:$new_id";
    exit;
}

/****************************************************
 * Get Experiment Data
 ***************************************************/
 

 
if (validID($_GET['id'])) {
    $exp_id = intval($_GET['id']);
} else {
    header('Location: /res/exp/');
}

$myexp = new myQuery('SELECT exp.*, 
                        GROUP_CONCAT(CONCAT(dv,": ",display) SEPARATOR "<br />") as buttons
                        FROM exp 
                        LEFT JOIN buttons ON exp.id=exp_id 
                        WHERE exp.id=' . $exp_id . ' GROUP BY exp.id');
                        
if ($myexp->get_num_rows() == 0) { header('Location: /res/exp/'); }

$expdata = $myexp->get_one_array();

// owner functions
$myowners = new myQuery('SELECT user_id, CONCAT(lastname, " ", initials) as name FROM access LEFT JOIN researcher USING (user_id) WHERE type="exp" AND id=' . $exp_id);
$owners = $myowners->get_assoc(false, 'user_id', 'name');
$access = in_array($_SESSION['user_id'], array_keys($owners));

$allowners = new myQuery('SELECT user_id, firstname, lastname, initials, email FROM researcher LEFT JOIN user USING (user_id) WHERE status > 4');
$ownerlisting = $allowners->get_assoc();
$ownerlist = array();
foreach($ownerlisting as $res) {
    $user_id = $res['user_id'];
    $lastname = htmlspecialchars($res['lastname'], ENT_QUOTES);
    $firstname = htmlspecialchars($res['firstname'], ENT_QUOTES);
    $initials = htmlspecialchars($res['initials'], ENT_QUOTES);
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

// get status changer for admins
if ($_SESSION['status'] == 'admin') {
    $status_chooser = new select('status', 'status', $expdata['status']);
    $status_chooser->set_null(false);
    $status_chooser->set_options(array(
        'test' => 'test',
        'active' => 'active',
        'inactive' => 'inactive'
    ));
    $status = $status_chooser->get_element();
} else {
    $status = '(' . $expdata['status'] . ')';
}

// get data on all trials
$mytrials = new myQuery('SELECT trial_n, t.name, 
                        GROUP_CONCAT(x.path ORDER BY n SEPARATOR ";") as xafc,
                        l.path as limage,
                        c.path as cimage,
                        r.path as rimage,
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
                        WHERE exp_id=' . $exp_id . ' GROUP BY trial_n');
$trials = $mytrials->get_assoc(false, 'trial_n');

// get image base url
$imagelist = array();
foreach ($trials as $t) {
    if (!empty($t['limage'])) $imagelist[] = $t['limage'];
    if (!empty($t['cimage'])) $imagelist[] = $t['cimage'];
    if (!empty($t['rimage'])) $imagelist[] = $t['rimage'];
    if (!empty($t['xafc'])) {
        $x = explode(';', $t['xafc']);
        foreach ($x as $img) { $imagelist[] = $img; }
    }
}

$common_path = common_path($imagelist);

// exclude empty cells for a trial table
$table_query = 'SELECT trial_n, name';
if (!empty($trials[1]['limage'])) $table_query .= ', l.path as limage';
if (!empty($trials[1]['cimage'])) $table_query .= ', c.path as cimage';
if (!empty($trials[1]['rimage'])) $table_query .= ', r.path as rimage';
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

$trial_table = '<p class="fullwidth">Image path: ' . $common_path . '</p>' . str_replace($common_path, '', $trial_table);

if (substr($expdata['subtype'], 0, 5) == 'adapt') {
    // get adaptation trial info
    $adapt_query = new myQuery('SELECT trial_n, exposure, a.version, name, notes, question,
            li.path as limage,
            ci.path as cimage,
            ri.path as rimage,
            li.type as ltype,
            ci.type as ctype,
            ri.type as rtype
        FROM adapt_trial as a
        LEFT JOIN versions AS v ON a.exp_id=v.exp_id AND a.version = v.version
        LEFT JOIN stimuli AS li ON (li.id=left_img)
        LEFT JOIN stimuli AS ci ON (ci.id=center_img)
        LEFT JOIN stimuli AS ri ON (ri.id=right_img)
        WHERE a.exp_id=' . $exp_id . ' ORDER BY a.version, trial_n');
        
    $adapt_trials = $adapt_query->get_assoc();
}

// get stats on participant completion of the experiment
$version = (substr($expdata['subtype'], 0, 5) == 'adapt') ? ' AND version>0' : '';

if ($expdata['subtype'] == "large_n") {
    $mydata = new myQuery('SELECT COUNT(*) as total_c,
                        COUNT(IF(sex="male",1,NULL)) as total_male,
                        COUNT(IF(sex="female",1,NULL)) as total_female,
                        MAX(dt) as last_completion
                        FROM exp_' . $exp_id . ' LEFT JOIN user USING (user_id)
                        WHERE status>1 AND status<6
                        AND trial=1');
} else {
    $mydata = new myQuery('SELECT COUNT(*) as total_c,
                        COUNT(IF(sex="male",1,NULL)) as total_male,
                        COUNT(IF(sex="female",1,NULL)) as total_female,
                        MAX(endtime) as last_completion
                        FROM exp_' . $exp_id . ' LEFT JOIN user USING (user_id)
                        WHERE status>1 AND status<6' . $version);
}
$data = $mydata->get_one_array();    

$mysets = new myQuery('SELECT set_id, CONCAT(set_id, ": ", name) as si from set_items LEFT JOIN sets ON sets.id=set_id where item_type="exp" and item_id=' . $exp_id);
$setslist = $mysets->get_assoc(false, 'set_id', 'si');    
$insets = new select('insets', 'insets');
$insets->set_options($setslist);
$insets->set_null(false);

// create chart of time taken
if ($expdata['subtype'] == "large_n") {
    $timechart = 'CREATE TEMPORARY TABLE tmp_ln SELECT SUM(rt) as total_time, COUNT(*) as n FROM exp_' . $exp_id . ' GROUP BY user_id HAVING n=' . $expdata['randomx'] . '; CREATE TEMPORARY TABLE tmp_ln2 SELECT * FROM tmp_ln; SELECT @percentile:=t1.val FROM (SELECT @rownum:=@rownum+1 as row_number, total_time as val FROM tmp_ln AS d,  (SELECT @rownum:=0) r ORDER BY total_time ) as t1, (SELECT count(*) as total_rows FROM tmp_ln2 AS d) as t2 WHERE t1.row_number=floor(95*total_rows/100)+1; CREATE TEMPORARY TABLE tmp_score SELECT ROUND(total_time/1000/60,1) as score FROM tmp_ln; SELECT @totalp := COUNT(*) FROM tmp_score WHERE score IS NOT NULL GROUP BY NULL; SELECT "  " as title, "Minutes" as xlabel, "Proportion of Participants" as ylabel, 0 as ymin, 0 as xmin, score as xcat, COUNT(*)/@totalp as dv, "line" as chart_type, "reverse" as reverse, "time_container" as container FROM tmp_score WHERE score IS NOT NULL AND score<=(@percentile/60) AND score>0 GROUP BY score;';

} else {
    $timechart = 'SELECT @percentile:=t1.val FROM (SELECT @rownum:=@rownum+1 as row_number, (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime)) as val FROM exp_' . $exp_id . ' AS d,  (SELECT @rownum:=0) r WHERE (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))>0 ORDER BY (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime)) ) as t1, (SELECT count(*) as total_rows FROM exp_' . $exp_id . ' AS d WHERE (UNIX_TIMESTAMP(d.endtime)-UNIX_TIMESTAMP(d.starttime))>0) as t2 WHERE t1.row_number=floor(95*total_rows/100)+1; CREATE TEMPORARY TABLE tmp_score SELECT ROUND((UNIX_TIMESTAMP(endtime)-UNIX_TIMESTAMP(starttime))/60,1) as score FROM exp_' . $exp_id . '; SELECT @totalp := COUNT(*) FROM tmp_score WHERE score IS NOT NULL GROUP BY NULL; SELECT "  " as title, "Minutes" as xlabel, "Proportion of Participants" as ylabel, 0 as ymin, 0 as xmin, score as xcat, COUNT(*)/@totalp as dv, "line" as chart_type, "reverse" as reverse, "time_container" as container FROM tmp_score WHERE score IS NOT NULL AND score<=(@percentile/60) AND score>0 GROUP BY score;';
}

    
/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_logo(false);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<h2>Exp <?= $expdata['id'] ?>: <?= $expdata['res_name'] ?> <?= $status ?></h2>

<div class='toolbar'>
    <div id="function_buttonset">
        <button id="view-exp">Go</button><?php if ($_SESSION['status'] == 'admin' || $access) { 
            echo '<button id="edit-exp">Edit</button>' ;
            echo '<button id="edit-trials">Trials</button>';
            if (substr($expdata['subtype'], 0, 5) == 'adapt') {
                echo '<button id="edit-adapt">Adaptation</button>';
            }
            echo '<button id="delete-exp">Delete</button>';
            echo '<button id="duplicate-exp">Duplicate</button>';
            echo '<button id="data-exp">Data</button>';
        } ?>
    </div>
</div>

<table id="infotable"><tr><td>
<table id="expinfo"> 
    <tr><td>Name:</td> <td><?= $expdata['name'] ?></td></tr>
    <tr><td>Created on:</td> <td><?= $expdata['create_date'] ?></td></tr>
    <tr><td>Owners:<br><?php if ($_SESSION['status'] == 'admin') { echo '<button class="tinybutton"  id="owner-change">Change</button>'; } ?></td> 
        <td>
            <ul id='owner-edit'>
                <?= $owner_edit ?>
            </ul>
            <?php if ($_SESSION['status'] == 'admin') { ?>
            <input id='owner-add-input' type='text' > (<a id='owner-add'>add</a>)
            <?php } ?>
        </td></tr>
    <tr><td>Labnotes:</td> <td><pre><?= ifEmpty($expdata['labnotes'], '<span class="ui-state-error">Please add labnotes</span>') ?></pre></td></tr>
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
    <tr><td>Time to complete (excluding slowest 5%)</td> <td><div id="time_container"></div></td></tr>
    
    <tr><td>Trials:</td> <td><?= $expdata['randomx'] ?> of <?= count($trials) ?></td></tr>
    <tr><td>Image Path:</td> <td><?= $common_path ?></td></tr>
    
    <tr><td>Type:</td> <td><?= $expdata['subtype'] ?> <?= $expdata['exptype'] ?> with <?= $expdata['orient'] ?> image orientation</td></tr>
    <?php if (count($exposure) > 0) { ?>
        <tr><td>Adapt time:</td> <td><?= implode(', ', array_keys($exposure)) ?> ms per trial</td></tr>
    <?php } 
        if (!empty($expdata['label1'])) { ?>
        <tr><td>Labels:</td> <td>    <?= $expdata['label1'] ?><br />
                                <?= $expdata['label2'] ?><br />
                                <?= $expdata['label3'] ?><br />
                                <?= $expdata['label4'] ?>
                         </td></tr>
    <?php } else if (!empty($expdata['buttons'])) {?>
        <tr><td>Buttons:</td> <td><?= $expdata['buttons'] ?></td></tr>
    <?php } else if (!empty($expdata['low_anchor'])) { ?>
        <tr><td>Anchors:</td> <td>    <?= $expdata['low_anchor'] ?> - <?= $expdata['high_anchor'] ?></td></tr>
    <?php } ?>
    <?php if ($expdata['rating_range']>0) { ?>
        <tr><td>Rating range:</td> <td>    <?= $expdata['rating_range'] ?></td></tr>
    <?php } ?>
    <tr><td>Design:</td> <td><?= $expdata['design'] ?>-subjects</td></tr>
    <tr><td>Order:</td> <td><?= $expdata['trial_order'] ?></td></tr>
    <tr><td>Side:</td> <td><?= $expdata['side'] ?></td></tr>
    <?php if (!empty($expdata['url'])) { ?>
        <tr><td>URL:</td> <td><?= $expdata['url'] ?></td></tr>
    <?php } ?>
    <tr><td>Restrictions:</td> <td><?= $expdata['sex'] ?> who prefer <?= is_null($expdata['sexpref']) ? 'unspecified sex' : $expdata['sexpref'] ?>, 
        ages <?= is_null($expdata['lower_age']) ? 'any' : $expdata['lower_age'] ?> 
        to <?= is_null($expdata['upper_age']) ? 'any' : $expdata['upper_age'] ?> years</td></tr>
    <tr><td>Instructions:</td> <td><?= $expdata['instructions'] ?></td></tr>
    <tr><td>Question:</td> <td><?= ifEmpty($expdata['question'], '<i>Varies by trial</i>') ?></td></tr>
    <tr><td><a href="/exp/feedback?id=<?= $expdata['id'] ?>">Feedback</a>:</td> <td><?= $expdata['feedback_general'] ?><br /><?= $expdata['feedback_specific'] ?></td></tr>
    <?php if (!empty($expdata['forward'])) { ?>
        <tr><td>Forward to URL:</td> <td><a href="<?= $expdata['forward'] ?>"><?= $expdata['forward'] ?></a></td></tr>
    <?php } ?>
</table>

</td><td>
        
    <div id="image_list_toggle">
        <input type="radio" id="image_toggle" name="radio" checked="checked" />
        <label for="image_toggle">Images</label>
        <input type="radio" id="list_toggle" name="radio" />
        <label for="list_toggle">List</label>
    </div>

<?php

if (substr($expdata['subtype'], 0, 5) == 'adapt') {
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
        if (!empty($t['limage'])) echo '            <img src="' . $t['limage'] . '" title="' . $t['limage'] . '" />' . ENDLINE;
        if (!empty($t['cimage'])) echo '            <img src="' . $t['cimage'] . '" title="' . $t['cimage'] . '" />' . ENDLINE;
        if (!empty($t['rimage'])) echo '            <img src="' . $t['rimage'] . '" title="' . $t['rimage'] . '" />' . ENDLINE;
        
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
    if (!empty($t['limage'])) {
        if ($t['ltype'] == 'image') {
            echo '            <img src="' . $t['limage'] . '" title="' . $t['limage'] . '" />' . ENDLINE;
        } else if ($t['ltype'] == 'audio') {
            $audioname = str_replace($common_path, '',$t['limage']);
            echo "            Hi: $audioname<br><audio controls>
                    <source src='{$t['limage']}.ogg' type='audio/ogg' autoplay='false' />
                    <source src='{$t['limage']}.mp3' type='audio/mp3' autoplay='false' />
                </audio><br>" . ENDLINE;
        }
    }
    if (!empty($t['cimage'])) {
        if ($t['ctype'] == 'image') {
            echo '            <img src="' . $t['cimage'] . '" title="' . $t['cimage'] . '" />' . ENDLINE;
        } else if ($t['ctype'] == 'audio') {
            $audioname = str_replace($common_path, '',$t['cimage']);
            echo "            $audioname<br><audio controls>
                    <source src='{$t['cimage']}.ogg' type='audio/ogg' autoplay='false' />
                    <source src='{$t['cimage']}.mp3' type='audio/mp3' autoplay='false' />
                </audio><br>" . ENDLINE;
        }
    }
    if (!empty($t['rimage'])) {
        if ($t['rtype'] == 'image') {
            echo '            <img src="' . $t['rimage'] . '" title="' . $t['rimage'] . '" />' . ENDLINE;
        } else if ($t['rtype'] == 'audio') {
            $audioname = str_replace($common_path, '',$t['rimage']);
            echo "            Lo: $audioname<br><audio controls>
                    <source src='{$t['rimage']}.ogg' type='audio/ogg' autoplay='false' />
                    <source src='{$t['rimage']}.mp3' type='audio/mp3' autoplay='false' />
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

</td></tr></table>

<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script src="/include/js/highcharts/highcharts-<?= HIGHCHARTS ?>.js"></script>
<script src="/include/js/highcharts/<?= (MOBILE) ? 'mobile_' : '' ?>theme.js"></script>    
    
<script>
    var chart;

    $j(function() {
        // get time graph
        if (<?= ifEmpty($data['total_c'],'0') ?> > 0) {
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
    
        $j( "#view-exp" ).click(function() {
            window.location = '/exp?id=<?= $expdata['id'] ?>';
        });
        $j( "#edit-exp" ).click(function() {
            window.location = '/res/exp/builder?id=<?= $expdata['id'] ?>';
        });
        $j( "#edit-trials" ).click(function() {
            window.location = '/res/exp/trials?id=<?= $expdata['id'] ?>';
        });
        $j( "#edit-adapt" ).click(function() {
            window.location = '/res/exp/adapt?id=<?= $expdata['id'] ?>';
        });
        $j( "#data-exp" ).click(function() {
            window.location = '/res/data/?exp_id=<?= $expdata['id'] ?>';
        });
        $j( "#delete-exp" ).click( function() {
            $j( "<div/>").html("Do you really want to delete this experiment?").dialog({
                title: "Delete Experiment",
                position: ['center', 100],
                modal: true,
                buttons: {
                    Cancel: function() {
                        $j( this ).dialog( "close" );
                    },
                    "Delete": function() {
                        $j( this ).dialog( "close" );
                        $j.get("/res/scripts/delete_exp?id=<?= $expdata['id'] ?>", function(data) {
                            if (data == 'deleted') {
                                window.location = '/res/exp/';
                            } else {
                                $j('<div title="Problem with Deletion" />').html(data).dialog();
                            }
                        });
                    },
                }
            });
        });
        
        $j( "#duplicate-exp" ).click( function() {
            $j( "<div/>").html("Do you really want to duplicate this experiment?").dialog({
                title: "Duplicate Experiment",
                position: ['center', 100],
                modal: true,
                buttons: {
                    Cancel: function() {
                        $j( this ).dialog( "close" );
                    },
                    "Duplicate": function() {
                        $j( this ).dialog( "close" );
                        $j.get("?duplicate&id=<?= $expdata['id'] ?>", function(data) {
                            var resp = data.split(':');
                            if (resp[0] == 'duplicated' && parseInt(resp[1]) > 1) {
                                window.location = '/res/exp/info?id=' + resp[1];
                            } else {
                                $j('<div title="Problem with Duplication" />').html(data).dialog();
                            }
                        });
                    },
                }
            });
        });
            
        $j( "#image_list_toggle" ).buttonset();
        $j( "#list_toggle" ).click(function() { 
            $j('#image_table').hide();
            $j('#trial_table').show();
        });
        $j( "#image_toggle" ).click(function() { 
            $j('#image_table').show();
            $j('#trial_table').hide();
        });
        
        $j( "#status" ).css('fontWeight', 'normal').change( function() {
            var $sel = $j(this);
            $sel.css('color', 'red');

            $j.ajax({
                url: '/res/scripts/status',
                type: 'POST',
                data: {
                    type: 'exp',
                    status: $sel.val(),
                    id: <?= $expdata['id'] ?>
                },
                success: function(data) {
                    if (data == 'Status of exp_<?= $expdata['id'] ?> changed to '+ $sel.val() ) {
                        $sel.css('color', 'inherit');
                    } else {
                        growl(data, 30);
                    }
                }
            });
        });
        
        $j('#trial_table').hide();
        
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
                    type: 'exp',
                    id: <?= $expdata['id'] ?>,
                    add: to_add,
                    delete: to_delete
                },
                success: function(data) {
                    if (data) {
                        growl(data);
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