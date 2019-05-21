<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/Parsedown.php';
unset($_SESSION['session_id']);

/****************************************************
 * Get Project Info
 ***************************************************/

if (count($_GET) > 0) {
    $keys = array_keys($_GET);
    $projectname = $keys[0];
    if (in_array($_SESSION['status'], $RES_STATUS)) {
        $myproject = new myQuery('SELECT * FROM project WHERE url="' . $projectname . '"');
    } else {
        $myproject = new myQuery('SELECT * FROM project WHERE status="active" AND url="' . $projectname . '"');
    }
    
    // check if a project was returned or exit
    if ($myproject->get_num_rows() == 0) {
        header('Location: /');
        exit;
    }
    
    $_SESSION['project'] = $projectname;
    $project = $myproject->get_one_array();
    $_SESSION['project_id'] = $project['id'];
    
    $exclusions = array();
    if ($_SESSION['age'] > 0) {
        $exclusions['exp'][]    = '(exp.lower_age <= ' . ($_SESSION['age']) . ' OR exp.lower_age IS NULL)'; 
        $exclusions['quest'][]  = '(quest.lower_age <= ' . ($_SESSION['age']) . ' OR quest.lower_age IS NULL)';
        $exclusions['sets'][]   = '(sets.lower_age <= ' . ($_SESSION['age']) . ' OR sets.lower_age IS NULL)';
        $exclusions['exp'][]    = '(exp.upper_age >= ' . ($_SESSION['age']) . ' OR exp.upper_age IS NULL)';
        $exclusions['quest'][]  = '(quest.upper_age >= ' . ($_SESSION['age']) . ' OR quest.upper_age IS NULL)';
        $exclusions['sets'][]   = '(sets.upper_age >= ' . ($_SESSION['age']) . ' OR sets.upper_age IS NULL)';
    } else {
        // only show items with no age limits for people without an age
        $exclusions['exp'][]    = 'exp.lower_age IS NULL AND exp.upper_age IS NULL';
        $exclusions['quest'][]  = 'quest.lower_age IS NULL AND quest.upper_age IS NULL';
        $exclusions['sets'][]   = 'sets.lower_age IS NULL AND sets.upper_age IS NULL';
    }
    
    if ($_SESSION['sex'] == 'male') {
        $exclusions['exp'][]    = '(exp.sex!="female")';
        $exclusions['quest'][]  = '(quest.sex!="female")';
        $exclusions['sets'][]   = '(sets.sex!="female")';
    } else if ($_SESSION['sex'] == 'female') {
        $exclusions['exp'][]    = '(exp.sex!="male")';
        $exclusions['quest'][]  = '(quest.sex!="male")';
        $exclusions['sets'][]   = '(sets.sex!="male")';
    }
    
    $myitems = new myQuery('SELECT item_type, item_id, icon,
                            IF(item_type="exp", exp.name, 
                                IF(item_type="quest", quest.name, 
                                    IF(item_type="set", sets.name, "Mystery Item"))) as name,
                            IF(item_type="exp", exp.status, 
                                IF(item_type="quest", quest.status, 
                                    IF(item_type="set", sets.status, NULL))) as the_status,
                            IF(item_type="exp", exp.lower_age, 
                                IF(item_type="quest", quest.lower_age, 
                                    IF(item_type="set", sets.lower_age, NULL))) as the_lower_age,
                            IF(item_type="exp", exp.upper_age, 
                                IF(item_type="quest", quest.upper_age, 
                                    IF(item_type="set", sets.upper_age, NULL))) as the_upper_age       
                            FROM project_items as p
                            LEFT JOIN exp ON (exp.id=item_id) AND item_type="exp" AND ' . implode(' AND ', $exclusions['exp']) . '
                            LEFT JOIN quest ON (quest.id=item_id) AND item_type="quest" AND ' . implode(' AND ', $exclusions['quest']) . '
                            LEFT JOIN sets ON (sets.id=item_id) AND item_type="set" AND ' . implode(' AND ', $exclusions['sets']) . '
                            WHERE p.project_id=' . $project['id'] . '
                            ORDER BY item_n');
                        
    $items = $myitems->get_assoc();
} else {
    header('Location: /');
    exit;
}


/****************************************************/
/* !Display Page */
/***************************************************/   

$title = loc($project['name']);

$buttonwidth = count($items) * 11;
$styles = array(
    '#fb-login' => 'display: none;',
    '#logresauto' => 'max-width: 33em; margin: 1em auto;',
    '#logres' => 'max-width: 22em; margin: 1em auto;',
    '#auto' => 'max-width: 11em; margin: 1em auto;',
    '.bigbuttons li a.registerbutton' => 'background-image: url(/images/linearicons/pencil?c=FFF);',
    '.bigbuttons li a.loginbutton' => 'background-image: url(/images/linearicons/lock?c=FFF);',
    '.bigbuttons li a.autobutton' => 'background-image: url(/images/linearicons/0676-ghost-hipster?c=FFF);',
    '#itembuttons' => "margin: 1em auto; max-width: {$buttonwidth}em;"
);
$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

// warn if not eligible for any parts of the study
$visitems = false;
foreach ($items as $i) { $visitems = $visitems || !empty($i['the_status']); }
if (!$visitems) {
    echo "<h3 class='error'>You are not eligible for this study.<br>
            It might be restricted by age or gender.</h3>";
}

$Parsedown = new Parsedown();
echo $Parsedown->text($project['intro']);

if (!empty($_SESSION['status'])) {
    $res = in_array($_SESSION['status'], $RES_STATUS) ? ' res' : '';
    // participant is logged in
    echo '<ul class="bigbuttons" id="itembuttons">';
    $url = array(
        'exp'   => '/exp',      
        'quest' => '/quest',
        'set'   => '/include/scripts/set'
    );
    foreach ($items as $i) {
        printf('<li id="%s_%s" class="%s%s"><a class="%s" href="%s?id=%s" style="%s">%s</a></li>' . ENDLINE,
            $i['item_type'],
            $i['item_id'],
            ifEmpty($i['the_status'], 'hide'),
            $res,
            $i['item_type'],
            $url[$i['item_type']],
            $i['item_id'],
            (!empty($i['icon'])) ? "background-image: url({$i['icon']}?c=FFF)" : "",
            ifEmpty($i['name'], $i['item_type'] . "_" . $i['item_id'] . "<span class='corner'>hidden</span>")
        );
    }
    echo '</ul>';
} else if (array_key_exists("all", $_GET)) {
    // not logged in
    ?>
    <ul class="bigbuttons" id="logresauto">
        <li><a class="loginbutton" href="/login">Login</a></li>
        <li><a class="registerbutton" href="/register">Register</a></li>
        <li><a class="autobutton" href="javascript: guestLogin('<?= $projectname ?>');">Login as a Guest</a></li>
    </ul>
	<?php
} else if (array_key_exists("noguest", $_GET)) {
    // participant is not logged in yet
    ?>
    
    <p>Please login or register if you do not yet have an account.</p>
    
    <ul class="bigbuttons" id="logres">
        <li><a class="loginbutton" href="/login">Login</a></li>
        <li><a class="registerbutton" href="/register">Register</a></li>
    </ul>
    
    
    <?php
} else {
    // participant should be auto-logged in
    ?>
    <ul class="bigbuttons" id="auto">
		<li><a class="autobutton" href="javascript: guestLogin('<?= $projectname ?>');">Login as a Guest</a></li>
	</ul>
    <?php
}


$page->displayFooter();

?>