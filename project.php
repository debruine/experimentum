<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/Parsedown.php';

/****************************************************
 * Get Project Info
 ***************************************************/
 


if (count($_GET) > 0) {
    $keys = array_keys($_GET);
    $projectname = $keys[0];
    
    $projexcl = array();
    if ($_SESSION['age'] > 0) {
        $projexcl[]    = '(lower_age <= ' . ($_SESSION['age']) . ' OR lower_age IS NULL)'; 
        $projexcl[]    = '(upper_age >= ' . ($_SESSION['age']) . ' OR upper_age IS NULL)';
    } else {
        // only show items with no age limits for people without an age
        $projexcl[]    = '(lower_age IS NULL AND upper_age IS NULL)';
    }
    
    if ($_SESSION['sex'] == 'male') {
        $projexcl[]    = '(sex!="female")';
    } else if ($_SESSION['sex'] == 'female') {
        $projexcl[]    = '(sex!="male")';
    }
    $projexcl = implode(' AND ', $projexcl);
    
    if (in_array($_SESSION['status'], $RES_STATUS)) {
        # res users can see all studies, no demog restrictions
        $myproject = new myQuery('SELECT * FROM project WHERE url="' . $projectname . '"');
    } else if ($_SESSION['status'] == 'test') {
        # test users can see non-archive studies, with demog restrictions
        $myproject = new myQuery('SELECT * FROM project WHERE status!="archive" AND url="' . $projectname . '" AND ' . $projexcl);
    } else if (in_array($_SESSION['status'], array('guest', 'registered'))) {
        # guest and registered users can see active studies, with demog restrictions
        $myproject = new myQuery('SELECT * FROM project WHERE status="active" AND url="' . $projectname . '" AND ' . $projexcl);
    } else if (array_key_exists('test', $_GET)) {
        # non-logged in test can see non-archive studies, no demog restrictions yet
        $myproject = new myQuery('SELECT * FROM project WHERE status!="archive" AND url="' . $projectname . '"');
    } else {
        # non-logged in non-test can see active studies, no demog restrictions yet
        $myproject = new myQuery('SELECT * FROM project WHERE status="active" AND url="' . $projectname . '"');
    }
    
    // check if a project was returned or exit
    if ($myproject->get_num_rows() == 0) {
        $title = loc('Project not found');
        $page = new page($title);
        $page->set_menu(true);
        
        $page->displayHead();
        $page->displayBody();
        
        $proj = new myQuery('SELECT status FROM project WHERE url="' . $projectname . '"');
        if ($proj->get_num_rows() == 0) {
            echo '<p>' . loc('Sorry, this project does not exist.'). '</p>';
        } else if (in_array($proj->get_one(), array('test', 'archive')) ) {
            echo '<p>' . loc('Sorry, this project is not active.'). '</p>';
        } else {
            echo '<p>' . loc('Sorry, this project is not available. It may have gender or age restrictions.'). '</p>';
        }
        echo '<p>' . loc('Double-check the link and contact the researcher if this seems to be an error.'). '</p>';
        
        $page->displayFooter();
        
        exit;
    }
    
    // project found, check for showable items
    $_SESSION['project'] = $projectname;
    $project = $myproject->get_one_array();
    // set new session if project has changed
    if ($_SESSION['project_id'] != $project['id']) { unset($_SESSION['session_id']); }
    $_SESSION['project_id'] = $project['id'];
    
    /*
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
    */
                            
    $myitems = new myQuery(
        'SELECT item_type, item_id, icon,
                IF(item_type="exp", exp.name, 
                    IF(item_type="quest", quest.name, 
                        IF(item_type="set", sets.name, "Mystery Item"))) as name,
                IF(item_type="exp", exp.status, 
                    IF(item_type="quest", quest.status, 
                        IF(item_type="set", sets.status, NULL))) as the_status,
                IF(item_type="exp", exp.sex, 
                    IF(item_type="quest", quest.sex, 
                        IF(item_type="set", sets.sex, NULL))) as the_sex,
                IF(item_type="exp", exp.lower_age, 
                    IF(item_type="quest", quest.lower_age, 
                        IF(item_type="set", sets.lower_age, NULL))) as the_lower_age,
                IF(item_type="exp", exp.upper_age, 
                    IF(item_type="quest", quest.upper_age, 
                        IF(item_type="set", sets.upper_age, NULL))) as the_upper_age
          FROM project_items as p
     LEFT JOIN exp ON (exp.id=item_id) AND item_type="exp" 
     LEFT JOIN quest ON (quest.id=item_id) AND item_type="quest"
     LEFT JOIN sets ON (sets.id=item_id) AND item_type="set" 
         WHERE p.project_id=' . $project['id'] . '
      ORDER BY item_n');
    /*
    echo $myitems->get_query();
    */
                        
    $items = $myitems->get_assoc();
} else {
    # no project in GET
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
    '#logresguest' => 'max-width: 22em; margin: 1em auto;',
    '#logres' => 'max-width: 22em; margin: 1em auto;',
    '#guest' => 'max-width: 11em; margin: 1em auto;',
    '.bigbuttons li a.registerbutton' => 'background-image: url(/images/linearicons/pencil?c=FFF);',
    '.bigbuttons li a.loginbutton' => 'background-image: url(/images/linearicons/lock?c=FFF);',
    '.bigbuttons li a.autobutton' => 'background-image: url(/images/linearicons/0676-ghost-hipster?c=FFF);',
    '.bigbuttons li a.testbutton' => 'background-image: url(/images/linearicons/rocket.php?c=FFFFFF);',
    '#itembuttons' => "margin: 1em auto; max-width: {$buttonwidth}em;"
);
$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

// warn if not eligible for any parts of the study
if (isset($_SESSION['status']) && in_array($_SESSION['status'], $ALL_STATUS)) {
    $visitems = false;
    foreach ($items as $i) { $visitems = $visitems || !empty($i['the_status']); }
    if (!$visitems) {
        echo "<h3 class='error'>You are not eligible for any items in this study.<br>
                They might be restricted by age or gender.</h3>";
    }
}

$Parsedown = new Parsedown();
echo $Parsedown->text($project['intro']);

if (array_key_exists("test", $_GET) && $_SESSION['status'] != 'test') {
    # logs out a user and logs them in as test
    ?>
    <script>
        testLogin('<?= $projectname ?>');
    </script>
    <?php
} else if (!empty($_SESSION['status'])) {
    $class = in_array($_SESSION['status'], $RES_STATUS) ? 'resuser' : '';
    if ($_SESSION['status'] == 'test') { $class = ' testuser'; }
    // participant is logged in
    echo '<ul class="bigbuttons '.$class.'" id="itembuttons">';
    $url = array(
        'exp'   => '/exp',      
        'quest' => '/quest',
        'set'   => '/include/scripts/set'
    );
    foreach ($items as $i) {
        $hide = 'hide';
        if ($i['the_status'] == 'active') { $hide = ''; } 
        if ($_SESSION['status'] == 'test' && $i['the_status'] == 'test') { $hide = ''; }
        
        $age_sex = '';
        if ($i['the_sex'] == 'male' && $_SESSION['sex'] == 'female') { $age_sex = 'restricted'; }
        if ($i['the_sex'] == 'female' && $_SESSION['sex'] == 'male') { $age_sex = 'restricted'; }
        if ($i['the_lower_age'] > 0 && $_SESSION['age'] < $i['the_lower_age']) { $age_sex = 'restricted'; }
        if ($i['the_upper_age'] > 0 && $_SESSION['age'] > $i['the_upper_age']) { $age_sex = 'restricted'; }
        
        printf('<li id="%s_%s" class="%s %s %s"><a class="%s" href="%s?id=%s" style="%s">%s</a></li>' . ENDLINE,
            $i['item_type'],
            $i['item_id'],
            $i['the_status'],
            $age_sex,
            $hide,
            $i['item_type'],
            $url[$i['item_type']],
            $i['item_id'],
            (!empty($i['icon'])) ? "background-image: url({$i['icon']}?c=FFF)" : "",
            ifEmpty($i['name'], $i['item_type'] . "_" . $i['item_id'] . "<span class='corner'>hidden</span>")
        );
    }
    echo '</ul>';
    
    if (!in_array($_SESSION['status'], array('guest', 'registered'))) {
        echo "<h3>Your responses will not count towards research as a test user or researcher</h3>\n";
        echo "<h4><ul>\n";
        echo "    <li>White borders indicate active sections</li>\n";
        echo "    <li>Yellow borders indicate test sections</li>\n";
        if (in_array($_SESSION['status'], $RES_STATUS)) {
            echo "    <li>Blue borders indicate archived sections</li>\n";
            echo "    <li>Purple backgrounds indicate age- or gender-restricted sections.</li>\n";
        }
        echo "</ul></h4>\n";
    }
    
    
    // start project session id if not started
    if (empty($_SESSION['session_id'])) {
        $q = new myQuery();
        $q->prepare("INSERT INTO session (project_id, user_id, dt) VALUES (?, ?, NOW())",
                    array('ii', $_SESSION['project_id'], $_SESSION['user_id']));
        $_SESSION['session_id'] = $q->get_insert_id();
    }
    
    // insert credit id
    if (array_key_exists('credit', $_GET)) {
        $_SESSION['credit'] = $_GET['credit'];
        $q = new myQuery();
        $q->prepare("INSERT IGNORE INTO credit (credit, project_id) VALUES (?, ?)",
                    array('si', $_GET['credit'], $_SESSION['project_id']));
    }
} else if (array_key_exists("auto", $_GET)) {
    ?>
    <script>
        guestLogin('<?= $projectname ?>');
    </script>
    <?php
} else if (array_key_exists("autond", $_GET)) {
    ?>
    <script>
        guestLoginNodemog('<?= $projectname ?>');
    </script>
    <?php

} else if (array_key_exists("guest", $_GET)) {
    // participant should be auto-logged in
    ?>
    <ul class="bigbuttons" id="guest">
		<li><a class="autobutton" href="javascript: guestLogin('<?= $projectname ?>');">Login as a Guest</a></li>
	</ul>
	<?php
} else if (array_key_exists("all", $_GET)) {
    ?>
    <ul class="bigbuttons" id="logresguest">
        <li><a class="loginbutton" href="javascript: modalLogin('<?= $projectname ?>');">Login</a></li>
        <li><a class="registerbutton" href="/consent">Register</a></li>
		<li><a class="autobutton" href="javascript: guestLogin('<?= $projectname ?>');">Login as a Guest</a></li>
		<li><a class="testbutton" href="javascript: testLogin('<?= $projectname ?>');">Test</a></li>
	</ul>
	<?php
} else {
    // participant is not logged in yet
    ?>
    
    <p>Please login or register if you do not yet have an account.</p>
    
    <ul class="bigbuttons" id="logres">
        <li><a class="loginbutton" href="javascript: modalLogin('<?= $projectname ?>');">Login</a></li>
        <li><a class="registerbutton" href="/consent">Register</a></li>
    </ul>
    
    
    <?php
}


$page->displayFooter();

?>