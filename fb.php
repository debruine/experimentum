<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/Parsedown.php';
auth(1);

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
    
    // general feedback
    //$general_fb = parsePara($fbdata['feedback_general']);
    $Parsedown = new Parsedown();
    $general_fb= $Parsedown->text($fbdata['feedback_general']);
    
    // specific feedback
    if ('**AVERAGE**' == substr($fbdata['feedback_query'],0,11) && $_GET['type'] == 'exp') {
        // average image feedback
        $avg_vars = explode(';', trim(substr($fbdata['feedback_query'],11)));
        $avg_n = intval($avg_vars[0]); // get number of top and bottom images to average
        $highLabel = trim($avg_vars[1]);
        $lowLabel = trim($avg_vars[2]);
        
        // get this participant's top and bottom images
        $mquery = array(
            sprintf("SELECT @myid := MAX(id), @me := user_id FROM exp_%d WHERE user_id='%d' GROUP BY user_id", $_GET['id'], $_SESSION['user_id']),
            sprintf("SELECT * FROM exp_%d WHERE id=@myid", $_GET['id'])
        );
        $query = new myQuery($mquery);
        
        $answers = array();
        foreach ($query->get_assoc(0) as $field => $response) {
            if (substr($field,0,1) == 't') {
                $trial_n = trim(substr($field, 1));
                $answers[$trial_n] = $response;
            } elseif (substr($field,0,2) == 'up') {
                $trial_n = trim(substr($field, 2));
                $answers[$trial_n] += $response;
            } elseif (substr($field,0,4) == 'down') {
                $trial_n = trim(substr($field, 4));
                $answers[$trial_n] -= $response;
            }
        }
        asort($answers);
        $low = array_slice($answers, 0, $avg_n, true);
        $high = array_slice($answers, (-1 * $avg_n), $avg_n, true);
        
        // get image urls
        $query = new myQuery('SELECT trial_n, path, center_img FROM trial LEFT JOIN stimuli ON center_img=id WHERE trial.exp_id=' . $_GET['id']); 
        $trialImages = array();
        foreach ($query->get_assoc() as $t) {
            //$trialImages[$t['trial_n']] = $t['center_img'];   
            $trialImages[$t['trial_n']] = $t['path'];   
        }
        
        $highList = '';
        foreach ($high as $t => $rank) {
            //$highList .= "&images0=i" . $trialImages[$t] .".jpg";
            $highList .= "&images0=" . urlencode($trialImages[$t]);
        }
        $lowList = '';
        foreach ($low as $t => $rank) {
            //$lowList .= "&images1=i" . $trialImages[$t] . ".jpg";
            $lowList .= "&images1=" . urlencode($trialImages[$t]);
        }
        
        list($width, $height) = getimagesize(DOC_ROOT . $trialImages[1] . '.jpg');
        if ($width<10) $width = 300;
        if ($height<10) $height = 400;
        
        $url = "/tomcat/psychomorph/averageImages?subfolder=&html=&width0={$width}&height0={$height}&texture0=false&width1={$width}&height1={$height}&texture1=false&count=2" . $highList . $lowList;
        
        $specific_fb  = "<table id='feedback_averages'>\n";
        $specific_fb .= "   <tr><td><img src='/images/loaders/loading.gif' id='average0' /></td>\n";
        $specific_fb .= "       <td><img src='/images/loaders/loading.gif' id='average1' /></td></tr>\n";
        $specific_fb .= "   <tr><th>$highLabel</th><th>$lowLabel</th></tr>\n";
        $specific_fb .= "</table>\n";
        
        $specific_fb .= '<script>
    $(function() {
        $.get("' . $url . '", function(data) {
            var response = data.split(";");
            var avgImages = response[0].split(",");
            $("#average0").attr("src", "/tomcat/psychomorph/uploads/" + avgImages[0]);
            $("#average1").attr("src", "/tomcat/psychomorph/uploads/" + avgImages[1]);
            $("#average0,#average1").css("width", "auto");
        });
    });
</script>';
    } else {
        if (!empty($fbdata['feedback_query'])) {
            $me = new myQuery("SET @me={$_SESSION['user_id']}");
            if ($_GET['type'] != 'set') {
                $myid = new myQuery("SELECT @myid := MAX(id) FROM {$_GET['type']}_{$_GET['id']} WHERE user_id=@me");
            }
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

echo $general_fb;
echo $specific_fb;
    
$page->displayFooter();

?>