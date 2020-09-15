<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/Parsedown.php';
auth(0);

// !Next item in within_adapt, if within_adapt exists
if (array_key_exists('within_adapt_number', $_SESSION) && array_key_exists('within_adapt', $_SESSION)) {
    $_SESSION['within_adapt_number']++;
    $next_in_list = $_SESSION['within_adapt'][$_SESSION['within_adapt_number']];
    if (!empty($next_in_list)) {
        // send to next item in the set list
        header('Location: /slideshow?id=' . $_GET['id'] . '&v=' . $next_in_list);
        exit;
    } else {
        // reset set_list and show feedback if next item is empty
        unset($_SESSION['within_adapt']);
        unset($_SESSION['within_adapt_number']);
    }
}

// !Next item in set, if set list exists
if (array_key_exists('set_item_number', $_SESSION) && array_key_exists('set_list', $_SESSION)) {
    // record credit
    if (array_key_exists('credit', $_SESSION)) {
        // account for feedback item if present
        $total = count($_SESSION['set_list']);
        if (substr($_SESSION['set_list'][$total-1], 0, 4) == '/fb?') { $total--; }
        
        $percent_complete = round(100*($_SESSION['set_item_number']+1)/$total);
        $q = new myQuery();
        $q->prepare("UPDATE credit SET percent_complete = ? WHERE credit = ? AND project_id = ?",
                    array('isi', $percent_complete, $_SESSION['credit'], $_SESSION['project_id']));
    }
    
    // increment
    $_SESSION['set_item_number']++;
    $next_in_list = $_SESSION['set_list'][$_SESSION['set_item_number']];
    if (!empty($next_in_list)) {
        // send to next item in the set list
        header('Location: ' . $next_in_list);
        exit;
    } else {
        // reset set_list and show feedback if next item is empty
        unset($_SESSION['set_list']);
        unset($_SESSION['set_item_number']);
    }
}

// record end of session
if (!empty($_SESSION['session_id'])) {
    $q = new myQuery();
    $q->prepare("UPDATE session SET endtime = NOW() WHERE user_id = ? AND id = ?",
               array('ii', $_SESSION['user_id'], $_SESSION['session_id']));
    $session = $_SESSION['session_id'];
    unset($_SESSION['session_id']);
} else {
    $session = 0;
}

// record end of credit
if (array_key_exists('credit', $_SESSION)) {
    $q = new myQuery();
    $q->prepare("UPDATE credit SET percent_complete = 100 WHERE credit = ? AND project_id = ?",
                array('si', $_SESSION['credit'], $_SESSION['project_id']));
}



// !if ineligible to do that item, return to homepage
if (array_key_exists('ineligible', $_GET)) {
    header('Location: /');
    exit;
}

// !Get feedback
if (is_numeric($_GET['id']) 
        && $_GET['id']>0 
        && in_array($_GET['type'], array('exp','quest','sets'))) {
    $q = new myQuery('SELECT name, feedback_general, feedback_specific, feedback_query 
                        FROM ' . $_GET['type'] . ' WHERE id=' . $_GET['id']);
    $fbdata = $q->get_assoc(0);
    
    // project/credit feedback
    $q->prepare("SELECT id, url, contact FROM project where id = ?",
                array('i', $_SESSION['project_id']));
    $pi = $q->get_one_row();
    $project_fb = "";
    $credit_fb = "";
    if (empty($pi['contact'])) { $pi['contact'] = ADMIN_EMAIL; }
    $project_fb = sprintf('<p>Please contact <a href="mailto:%s?subject=Experimentum project %s (%s)">%s</a> 
                           with any questions about this study.</p>', 
                               $pi['contact'], $pi['url'], $pi['id'], $pi['contact']);
    
    if (array_key_exists('credit', $_SESSION)) {
        $credit_fb = sprintf("<p class='feature'>" . CREDITFB . "</p>\n\n", $_SESSION['credit'], $pi['contact']);
    }
    
    
    // general feedback
    //$general_fb = parsePara($fbdata['feedback_general']);
    $Parsedown = new Parsedown();
    $general_fb= $Parsedown->text($fbdata['feedback_general']);
    
    // specific feedback
    if (!empty($fbdata['feedback_specific'])) {
        if (!empty($fbdata['feedback_query'])) {
            $me = new myQuery("SET @uid={$_SESSION['user_id']}");
            $sess = new myQuery("SET @mysess={$session}");
            $fb = new myQuery($fbdata['feedback_query'], true);
            $myfb = $fb->get_assoc(0);
        }
    
        //$spec_trans = parsePara($fbdata['feedback_specific']);
        $Parsedown = new Parsedown();
        $spec_trans = $Parsedown->text($fbdata['feedback_specific']);
    
        $number_of_replacements = substr_count($spec_trans, "\$s") + substr_count($spec_trans, "\$d");
        if (!is_array($myfb)) $myfb = array(); // make sure $myfb is an array
        $myfb = array_pad($myfb, $number_of_replacements, "**"); // pad this out to the right number
            
        $specific_fb =  vsprintf($spec_trans, $myfb);
    }
} else {
    header('Location: /');
    exit;
}

/****************************************************/
/* !Display Page */
/***************************************************/
 
$title = array(
    'Feedback',
    $fbdata['name']
);
$page = new page($title);
$page->set_menu(false);

$page->displayHead();
$page->displayBody();

echo "<div class='fb_text'>\n";
echo $credit_fb;
echo $general_fb;
echo $specific_fb;
echo $project_fb;
 
?>

</div>

<!--<div class="buttons"><button id="home">Back</button></div>-->
<script>
    // prevent back button
    history.pushState(null, null, location.href); 
    history.back(); 
    history.forward(); 
    window.onpopstate = function () { history.go(1); };
    
    $(function() {
        $('#home').button().click( function() { 
            window.location = '<?= (!empty($_SESSION['project'])) ? '/project?' . $_SESSION['project'] : '/' ?>'; 
        } );
    });
</script>
    
<?php 
    
$page->displayFooter();

?>