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

/****************************************************
 * Get questionnaire Data
 ***************************************************/

 
if (validID($_GET['id'])) {
    $item_id = intval($_GET['id']);
} else {
    header('Location: /res/quest/');
}
    

$myquest = new myQuery('SELECT quest.*
                        FROM quest 
                        WHERE quest.id=' . $item_id . ' GROUP BY quest.id');

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
                           WHERE type="quest" AND id=' . $item_id);
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
                                IF(q.type='radioanchor', CONCAT('radioanchor: 1 (', low_anchor, ') to ', maxlength, ' (', high_anchor, ')'), 
                                    IF(q.type='slider', CONCAT('slider: ', startnum,' (', low_anchor, ') to ', endnum, ' (', high_anchor, ') by ', step), 
                                        IF(q.type='radiorow', 'FWD',  If(q.type='radiorev', '<span class=\"ui-state-highlight\">REV</span>',  
                                            CONCAT(q.type, ': ', low_anchor, ' to ', high_anchor))
                                        )
                                    )
                                )
                            )
                        ) as option_list
                        FROM question as q
                        LEFT JOIN options ON q_id=q.id 
                        WHERE q.quest_id={$item_id}
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

$mysets = new myQuery('SELECT set_id, CONCAT(set_id, ": ", name) as si from set_items LEFT JOIN sets ON sets.id=set_id where item_type="quest" and item_id=' . $item_id);
$setslist = $mysets->get_assoc(false, 'set_id', 'si');  
$insets = new select('insets', 'insets');
$insets->set_options($setslist);
$insets->set_null(false);

// create chart of time taken

$timechart = 'CREATE TEMPORARY TABLE tmp_ln ' . 
             'SELECT endtime-starttime as total_time ' .
             'FROM quest_data ' .
             'WHERE quest_id='.$item_id. ' ' .
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

<input type="hidden" id="item_id" value="<?= $itemdata['id'] ?>" />
<input type="hidden" id="item_type" value="quest" />

<h2>quest <?= $itemdata['id'] ?>: <?= $itemdata['res_name'] ?></h2>

<div class='toolbar'>
    <div id="function_buttonset"><?php
        echo '<button id="view-item">Go</button>';
        echo '<button id="edit-item">Edit</button>';
        echo '<button id="delete-item">Delete</button>';
        echo '<button id="duplicate-item">Duplicate</button>';
        echo '<button id="data-download">Data</button>';
        echo '<button id="get-json">Structure</button>';
    ?></div>
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
<script src="/res/scripts/res.js"></script>

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