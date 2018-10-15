<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('student', 'researcher', 'admin'));

$title = array(
    '/res/' => loc('Researchers'),
    '/res/data/' => loc('Data')
);

$styles = array(
    '#top_buttons, #bottom_buttons, #stat_buttons' => 'text-align: center; font-size: 100%',
    '#query' => 'max-height: 600px;',
    '#data_table' => 'max-width: 100%; min-height: 300px; overflow: auto;',
);

if (MOBILE) {
    $styles['div'] = 'float: none; clear: left;';
    $styles['#name'] = 'width: 100%;';
}


/****************************************************
 * AJAX Responses
 ***************************************************/


if (array_key_exists('exp_id', $_GET) && validID($_GET['exp_id'])) {
    $query = new myQuery('SELECT res_name, exptype, subtype FROM exp WHERE id=' . $_GET['exp_id']);
    $exp = $query->get_one_array();

    $query = new myQuery('SELECT trial.*, COUNT(*) as c 
                            FROM trial 
                            LEFT JOIN xafc USING (exp_id, trial_n) 
                           WHERE exp_id=' . $_GET['exp_id'] . ' 
                           GROUP BY trial_n 
                           ORDER BY trial_n');
    $trials = $query->get_assoc();
    
    $clean['query'] = 'SELECT user_id, sex, status,' . ENDLINE; 
    
    if ($exp['subtype'] == 'adapt') {
        $clean['query'] .= 'ROUND(DATEDIFF(pre.endtime, REPLACE(birthday, "-00","-01"))/365.25, 1) AS age,' . ENDLINE;
        foreach(array('pre', 'post') as $t) {
            $total = array();
            foreach ($trials as $trial) {
                if ($exp['exptype'] == 'motivation') {
                    $clean['query'] .= "{$t}.up{$trial['trial_n']}-{$t}.down{$trial['trial_n']} AS `{$t}_{$trial['name']}`," . ENDLINE;
                    $total[] = "({$t}.up{$trial['trial_n']}-{$t}.down{$trial['trial_n']})";
                } else if ($exp['exptype'] == 'sort') {
                    $images_per_trial = $trial['c'];
                    for ($i = 1; $i <= $images_per_trial; $i++) {
                        $clean['query'] .= "{$t}.t{$trial['trial_n']}_{$i} AS `{$t}_{$trial['name']}_{$i}`," . ENDLINE;
                    }
                    $total[] = "{$t}.moves{$trial['trial_n']}";
                } else {
                    $clean['query'] .= "{$t}.t{$trial['trial_n']} AS `{$t}_{$trial['name']}`," . ENDLINE;
                    $total[] = "{$t}.t{$trial['trial_n']}";
                }
            }
            $clean['query'] .=  implode('+', $total) . " as {$t}_total," . ENDLINE;
            
            if ($exp['exptype'] == 'jnd') {
                $total = array();
                foreach ($trials as $trial) {
                    $clean['query'] .= "({$t}.t{$trial['trial_n']}>3) AS `{$t}_{$trial['name']}_fc`," . ENDLINE;
                    $total[] = "({$t}.t{$trial['trial_n']}>3)";
                }
                $clean['query'] .=  implode('+', $total) . " as {$t}_total_fc," . ENDLINE;
            }
            $clean['query'] .= "{$t}.version as {$t}_version," . ENDLINE;
        }
        $clean['query'] .=  "TIMEDIFF(pre.endtime, pre.starttime) as pre_time_taken," .ENDLINE .
                            "pre.starttime as pre_starttime, pre.endtime as pre_endtime," . ENDLINE . 
                            "TIMEDIFF(post.endtime, post.starttime) as post_time_taken," . ENDLINE . 
                            "post.starttime as post_starttime, post.endtime as post_endtime" . ENDLINE . 
                            "FROM exp_{$_GET['exp_id']} as pre" . ENDLINE .
                            "LEFT JOIN exp_{$_GET['exp_id']} as post USING (user_id)" . ENDLINE;
        $where = "AND pre.version=0 AND post.version>0";
        $groupby = "GROUP BY user.user_id, pre.id, post.id";
    } else if ($exp['subtype'] == 'large_n') {
        $clean['query'] .= 'ROUND(DATEDIFF(dt, REPLACE(birthday, "-00","-01"))/365.25, 1) AS age,' . ENDLINE;
        $clean['query'] .=  "name as trial, trial_n, rt, dv, side, `order`, dt as datetime" . ENDLINE .
                            "FROM exp_{$_GET['exp_id']} AS e" . ENDLINE .
                            "LEFT JOIN trial ON e.trial=trial_n AND exp_id={$_GET['exp_id']}" . ENDLINE;
        $where = '';
        $groupby = "GROUP BY user.user_id, dt ORDER BY user.user_id, dt";
                        
    } else {
        $clean['query'] .= 'ROUND(DATEDIFF(endtime, REPLACE(birthday, "-00","-01"))/365.25, 1) AS age,' . ENDLINE;
        if ($exp['subtype'] == 'adapt_nopre') {
            $clean['query'] .= "version," . ENDLINE;
        }
    
        $clean['query'] .=  "exp.*, TIMEDIFF(endtime, starttime) as time_taken, starttime, endtime" . ENDLINE .
                            "FROM exp_{$_GET['exp_id']} as exp" . ENDLINE;
        $where = '';
        $groupby = "GROUP BY user.user_id, id";
    }
    

    $clean['query'] .=  "LEFT JOIN user USING (user_id)" . ENDLINE .
                        "WHERE status>0 {$where}" . ENDLINE .
                        "{$groupby}";
                        
    $clean['name'] = str_replace(" ",  "-", $exp['res_name']) . 
                     '_exp_' . $_GET['exp_id'] . 
                     '_' . date('Y-m-d_Hi');
} else if (array_key_exists('quest_id', $_GET) && validID($_GET['quest_id'])) {
    $query = new myQuery('SELECT res_name FROM quest WHERE id=' . $_GET['quest_id']);
    $quest = $query->get_one_array();
    
    $query = new myQuery('SELECT * FROM question WHERE quest_id=' . $_GET['quest_id'] . ' ORDER BY n');
    $trials = $query->get_assoc();
    $total = array();
    
    $clean['query'] = 'SELECT user_id, sex, status,' . ENDLINE .
                      'ROUND(DATEDIFF(endtime, REPLACE(birthday, "-00","-01"))/365.25, 1) AS age,' . ENDLINE;
                      
    foreach ($trials as $trial) {
        $clean['query'] .= "q{$trial['id']} AS `{$trial['name']}`," . ENDLINE;
        $total[] = "q{$trial['id']}";
    }
    $clean['query'] .=  //implode('+', $total) . " as total" . ENDLINE .
                        "TIMEDIFF(endtime, starttime) as time_taken, starttime, endtime" . ENDLINE .
                        "FROM quest_{$_GET['quest_id']}" . ENDLINE .
                        "LEFT JOIN user USING (user_id)" . ENDLINE .
                        "WHERE status>0";
    $clean['name'] = str_replace(" ",  "-", $quest['res_name']) .
                     '_quest_' . $_GET['quest_id'] . 
                     '_' . date('Y-m-d_Hi');
}

if (array_key_exists('show', $_GET)) {
    $query = new myQuery($_POST['query'], true);
    echo '<h2>', $query->get_num_rows(), ' participants</h2>', ENDLINE;
    $rotated = ($_POST['rotate']=='yes') ? true : false;
    echo $query->get_result_as_table(true, false, $rotated);
    exit;
}


/****************************************************
 * Set up forms
 ***************************************************/
 
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';
 
$input = array();
$input_width = (MOBILE) ? 300 : 500;

$input['id'] = new hiddenInput('id', 'id', $clean['id']);

$input['user_id'] = new hiddenInput('user_id', 'user_id', $_SESSION['user_id']);

$input['csv'] = new hiddenInput('csv', 'csv', 'false');

// query name
$input['name'] = new input('name', 'name', $clean['name']);
$input['name']->set_question('Name');
$input['name']->set_width($input_width);
//$input['name']->set_eventHandlers(array('onkeyup' => 'unsavedChanges();'));

// query
$input['query'] = new hiddenInput('query', 'query', $clean['query']);
#$input['query']->set_dimensions($input_width, 100, true, 100, 0);
#$input['query']->set_question('Query');

//$input['query']->set_eventHandlers(array('onkeyup' => 'unsavedChanges();'));

// rotated
#$input['rotate'] = new radio('rotate', 'rotate', 'no');
#$input['rotate']->set_question('Rotate?');
#$input['rotate']->set_orientation('horiz');
#$input['rotate']->set_options(array('no'=>'no', 'yes'=>'yes'));

// set up form table
$q = new formTable();
$q->set_table_id('myQuery');
$q->set_action('/include/scripts/download');
$q->set_questionList($input);
$q->set_method('post');

// set up stats table
$s = new formTable(); 
$s->set_table_id('myStats');
$s->set_questionList($statTable);
$s->set_method('post');

/****************************************************/
/* !Display Page */
/***************************************************/
 
$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<div id='top_buttons'>
    <button class='showData'>Show Data</button>
    <button class='downloadCSV'>Download</button>
</div>

<?= $q->print_form(); ?>

<div id="data_table"></div>


<div id="help" title="Query Help">
    <ul>
        <li>Set &ldquo;Rotate&rdquo; to yes and click &ldquo;Show Data&rdquo; to view the data table rotated 90 degrees. This is often useful for calculating inter-rater agreement.</li>
    </ul>
</div>

<!--**************************************************
 * Javascripts for this page
 ***************************************************-->


<script>

    $j(function() {
        setOriginalValues('myQuery');

        // set up main button functions

        $j( ".showData" ).button().click(function() { 
            if ($j('#query').val() == '') return false;
            $j('#data_table').html('<img src="/images/loaders/loading.gif" style="display: block; margin: 0 auto;" />');
            $j.ajax({
                url: './?show',
                type: 'POST',
                data: $j('#myQuery_form').serialize(),
                success: function(data) {
                    $j('#data_table').html(data);
                    stripe('#data_table tbody');
                    
                    var theHeight = $j(window).height() - $j('#data_table').offset().top - 30 - $j('#footer').height();
                    $j('#data_table').css({
                        'height': theHeight
                    });
                    console.log(theHeight);
                }
            });
        });
        $j( ".downloadCSV" ).button().click(function() { downloadCSV(); });

        <?= $default ?> 
    });
    
    function downloadCSV() {
        if ($j('#query').val() == '') return false;
        $j('#csv').val(true);
        $j('#myQuery_form').submit();
    }

</script>

<?php

$page->displayFooter();

?>