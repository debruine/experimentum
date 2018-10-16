<?php

/****************************************************
 * User classes
 ***************************************************/
 
class user {
    public $id;
    public $username;
    public $status = 4;  // defaults to registered status
    public $sex;
    public $gender;
    public $birthday;
    public $pquestion;
    public $panswer;
    public $location;
    
    function __construct($id = false) {
        if ($id) { 
            $this->set_id($id);
            $this->get_info();
        }
    }
    
    function set_id($x) { $this->id = $x; }
    function get_id() { return $this->id; }
    function set_username($x) { $this->username = $x; }
    function get_username() { return $this->username; }
    function set_status($x) { $this->status = $x; }
    function get_status() { return $this->status; }
    function set_sex($x) { $this->sex = $x; }
    function get_sex() { return $this->sex; }
    function set_gender($x) { $this->gender = $x; }
    function get_gender() { return $this->gender; }
    function set_birthday($y, $m, $d) { 
        // check dates for plausibility
        $year = ($y>1900 && $y<=date('Y')) ? $y : 0;
        $month = ($m>0 && $m<13) ? $m : 0;
        $day = ($d>0 && $d<32) ? $d : 0;
        $this->birthday = ($year == 0) ? '' : "{$year}-{$month}-{$day}"; 
    }
    function get_birthday() { return $this->birthday; }
    function set_pq_and_a($q, $a) { 
        $this->pquestion = $q; 
        $this->panswer = $a; 
    }
    function get_age() {
        if (empty($this->birthday)) return 0;
        
        $thisyear = date('Y');
        list($birthyear, $month, $day) = explode('-', $this->birthday);
        
        $no_birthday_yet = 0;
        if (date('m') < $month) {
            // current month is before birth month
            $no_birthday_yet = 1;
        } else if (date('m') == $month && date('d') < $day) {
            // current month = birth month and current day is before birth day
            $no_birthday_yet = 1;
        }
        
        $age = ($thisyear - $birthyear) - $no_birthday_yet;
        
        return $age;
    }
    function get_pquestion() { return $this->pquestion; }
    function get_panswer() { return $this->panswer; }
    
    function set_location($locname, $country, $fbid) {
        if (empty($country)) {
            $country = $this->ip2country($_SERVER['REMOTE_ADDR']);
        }
        $this->location = array(
            'locname' => $locname,
            'country' => $country,
            'fbid'      => $fbid
        );
    }
        
    function login($pw) {
        // login a user, return 'login' on success, error message on failure
        $q = sprintf('SELECT user_id FROM user WHERE username="%s" AND password=MD5("%s")',
            $this->username,
            $pw
        );
        
        $login = new myQuery($q);
        
        if (0 == $login->get_num_rows()) {
            // login failed
    
            $query = new myQuery("SELECT '' FROM user WHERE username='{$this->username}'");
            if (0 == $query->get_num_rows()) { 
                // there is no registered user by that name
                return "username:" . sprintf(
                    loc("Sorry %s, that is not a current username. Maybe you need to sign up for an account first?"), 
                    $this->username);
                exit;
            } else {
                // there is a registered user, but the password is wrong
                return "password:" . sprintf(
                    loc("Sorry %s, that is the wrong password"),
                    $username);
                exit;
            }
        } else {
            // login succeeded, add entry to login table and set user variables
            $user = $login->get_assoc();
            $this->id = $user[0]['user_id'];
            $this->login_table();
            $this->get_info();
            $this->set_session_variables();
            return 'login';
        }
    }
    
    function login_table() {
        // add entry to the login table
        $salt = '$2y$10$' . substr(md5($_SERVER['REMOTE_ADDR'] . IP_SECRET), 0, 21) . '$';
        $ip_encrypted = crypt($_SERVER['REMOTE_ADDR'], $salt);
        
        $q = sprintf("INSERT INTO login (user_id, logintime, ip, browser, referer) 
            VALUES ('%s', NOW(), '%s', '%s', '%s')",
            $this->id,
            $ip_encrypted,
            $_SERVER['HTTP_USER_AGENT'],
            isset($_SESSION['referer']) ? $_SESSION['referer'] : ''
        );
        $query = new myQuery($q);
    }
    
    function get_info() {
            $fbname = array_key_exists("fbname", $_SESSION) ? $_SESSION['fbname'] : "user";
        // get and set user information from user table
        $info = new myQuery("SELECT *, status+0 as status_n, 
            IF(username IS NULL,'{$fbname}', username) as name
            FROM user 
            WHERE user.user_id={$this->id}");
        if (0 == $info->get_num_rows()) {
            // invalid user id
            return false;
        } else {
            // valid user_id, set user variables
            $user = $info->get_one_array();
            
            $this->id       = $user['user_id'];
            $this->username = $user['name'];
            $this->status   = $user['status'];
            $this->sex      = $user['sex'];
            $this->pquestion= $user['pquestion'];
            $this->panswer  = $user['panswer'];
            
            $bday = explode('-', $user['birthday']);
            $this->set_birthday($bday[0], $bday[1], $bday[2]);
            
            return true;
        }
    }
    
    function register($password) {
        // create a new user and return the new user_id
        $q = sprintf('INSERT INTO user (user_id, username, password, regdate, 
            sex, gender, birthday, pquestion, panswer, status)
            VALUES (NULL, "%s", MD5("%s"), NOW(), 
            "%s", "%s", "%s", "%s", "%s", "%s")',
            $this->username,
            $password,
            $this->sex,
            $this->gender,
            $this->birthday,
            $this->pquestion,
            $this->panswer,
            $this->status
        );
        $q = str_replace(array('"NULL"', '""'), 'NULL', $q);
        
        $register = new myQuery($q);
        $this->set_id($register->get_insert_id());
        if (0 < $this->id) {
            // registration succeeded, so login
            $this->login($password);
            return $this->id;
        } else {
            // registration failed
            return false;
        }
    }
    
    function set_session_variables() {
        $_SESSION['user_id']    = $this->id;
        $_SESSION['username']   = $this->username;
        $_SESSION['sex']        = $this->sex;
        $_SESSION['status']         = $this->status;
        $_SESSION['age']        = $this->get_age();
    }
}


/****************************************************
 * Page Display classes
 ***************************************************/
 
class page {
    public $title = array();
    public $description = 'Psychology experiments at the University of Glasgow';
    public $language = 'en';
    public $menu = false;       // display menu
    
    function __construct($t) {
        $this->set_title($t);
    }
    
    function set_title($x) { 
        if (is_array($x)) {
            $this->title = $x;
        } else {
            $this->title = array($x);
        }
    }
    function get_title() { return $this->title; }
    function set_description($x) { $this->description = $x; }
    function get_description() { return $this->description; }
    function set_menu($x) { $this->menu = ($x === true) ? true : false; }
    function get_menu() { return $this->menu; }
    
    function displayHead($styles = false, $header = false) {
        $head_title = SITETITLE . (empty($this->title) ? '' : ': ') . implode(': ', $this->title);
?><!DOCTYPE html>

<html lang="<?= $this->language ?>">
<head>
    <title><?= $head_title ?></title>
    <meta charset="utf-8">
    <meta name="author" content="Lisa DeBruine" />
    <meta name="description" content="<?= $this->description ?>" />
    <meta name="keywords" content="psychology,experiments,research,online psychology research experiments" />
    <meta property="og:site_name" content="<?= SITETITLE ?>"/>
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <link rel="apple-touch-startup-image" href="/images/logos/logo.png" />
    <link rel="apple-touch-startup-image" sizes="640x920" href="/images/logos/logo@2x.png" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <link rel="shortcut icon" href="/images/logos/favicon.ico" />
    <link rel="apple-touch-icon-precomposed" href="/images/logos/apple-touch-icon-precomposed.png" />
    <link rel="stylesheet" type="text/css" href="/include/js/jquery-ui-1.12.1.custom/jquery-ui.min.css">
    <link rel="stylesheet" type="text/css" href="/include/css/style.php">
    <!--<link rel="alternate" type="application/rss+xml" title="<?= SITETITLE ?> via RSS" href="/rss" />-->
    <script src="<?= JQUERY ?>"></script> 
    <script src="<?= JQUERYUI ?>"></script>
    <script src="/include/js/jquery.ui.touch-punch.min.js"></script>
    <script src="/include/js/myfunctions.js"></script>  

<?php
        if ($header) {
            if (is_array($header)) {
                foreach ($header as $h) {
                    echo "\t$h\n";
                }
            } else {
                echo "\t$header\n";
            }
        }

        if ($styles) {
            echo '  <style type="text/css">', ENDLINE;
            foreach ($styles as $selector => $style) {
                echo "      $selector {{$style}}", ENDLINE;
            }
            echo '  </style>', ENDLINE;
        }
        
        if (MOBILE) {
            echo '  <meta name="MobileOptimized" content="width" />' . ENDLINE;
            echo '  <meta name="HandheldFriendly" content="True" />' . ENDLINE;
            echo '  <meta http-equiv="cleartype" content="on" />' . ENDLINE;
            //echo '    <script>$(document).ready(function() { loadPage(); alert();});</script>' . ENDLINE;
        }

        echo '</head>', ENDTAG;

    }
    
    function displayBody() {
        // set classes for the body
        $bodyclass = array();
        if (!$this->menu) $bodyclass[] = 'nomenu';
        if ($this->logo) $bodyclass[] = 'logo';
        
        // set title for the header
        $headertitle = array('<li><a href="/">'.SITETITLE.'</a></li>');
        foreach ($this->title as $link => $t) {
            if (!empty($t)) {
                if (is_numeric($link)) $headertitle[] = "<li>/$t</li>";
                else $headertitle[] = "<li>/<a href='$link'>$t</a></li>";
            }
        }
        
        // Start Display Body Text
?>

<!-- START BODY -->

<body class="<?= implode(' ', $bodyclass) ?>">

<div id="wrap"> <!-- start div for sticky footer -->

<!-- START HEADER -->
    
<header id="header">

    <ul id="breadcrumb">
        <?= implode("", $headertitle) ?>
    </ul>
    
    <ul id='login_info'>

<?php       
        // logged in or logged out displays
        if (isset($_SESSION['username'])) {
?>
        <li id='header_username'>
            <a href='/my'><?= ifEmpty($_SESSION['username'], 'My account') ?></a>
        </li>
        <li id='logout'><a href='javascript: logout();'><?= loc("Logout") ?></a></li>
<?php       
        } else {
?>
        <li id='login'><a href='javascript: startLogin();'><?= loc("Login/Sign up") ?></a></li>
<?php
        } 
?>
    </ul> 
    
</header>

<!-- END OF HEADER -->

<!-- START CONTENT -->

<div id="contentmask"><div id="content">
<div id="maincontent">

<?php
        // show alert if $_SESSION['alert'] is set
        if (!empty($_SESSION['alert'])) {
            echo "<div id='alerts' onclick='remove($(this))'>{$_SESSION['alert']}</div>\n";
            $_SESSION['alert'] = '';
        }
    }
    
    function displayFooter() {
        // make menu
        $menu = '';
        if ($this->menu) {
            $menu = '<nav id="menu"> <!-- start of menu -->' . ENDLINE;
            
            $menuList = array(
                '/'             => 'Home',
                '/studies/'     => 'Studies',
                '/faq'          => 'About Us (FAQ)'
            );
            $menuClasses = array(
                '/'             => 'home',
                '/studies/'     => 'exp',
                '/faq'          => 'faq',
                '/res/'         => 'res',
                '/my'           => 'my'
            );
            
            $res_list = array('student','researcher','admin');
            $status_list = array('test','guest','registered','student','researcher','admin');
            if (in_array($_SESSION['status'], $res_list)) $menuList['/res/'] = 'Researchers';
            if (in_array($_SESSION['status'], $status_list)) $menuList['/my'] = 'My Account';
            
            $menu .= '  <ul>' . ENDLINE;
            foreach($menuList as $url => $item) {
                if ($url . 'index.php' == $_SERVER['PHP_SELF'] || $url == $_SERVER['PHP_SELF']) {
                    $menu .= sprintf("      <li class='this_section %s'><a href='javascript: return false;'>%s</a></li>\n", 
                        $menuClasses[$url],
                        str_replace(' ', '&nbsp;', $item));
                } else {
                    $menu .= sprintf("      <li class='%s %s'><a href='%s'>%s</a></li>\n", 
                        $menuClasses[$url],
                        (strpos($_SERVER['PHP_SELF'], $url) === false || $url == '/') ? '' : ' class="this_section"',
                        $url, 
                        str_replace(' ', '&nbsp;', $item)
                    );
                }
            }
            $menu .= '  </ul>' . ENDLINE;
            $menu .= '</nav> <!-- end of menu -->' . ENDLINE;
        }
                
?>

<!-- Modal dialog box for logging in -->

<div id='loginbox' class="modal" title='Login'>
            <input  id='login_username'
                    name='login_username'  
                    type='text' 
                    maxlength='32' 
                    onchange='login();' />
            <label for='login_username'><?= loc("Username") ?></label>

            <input  id='login_password' 
                    name='login_password'
                    type='password' 
                    maxlength='32' 
                    onchange='login();' />
            <label for='login_password'><?= loc("Password") ?></label>

    <div id='login_error' class='ui-state-highlight'></div>
    <br>
    <div class="note">This website requires cookies to allow you to log in. Logging in indicates you agree to this.</div>
</div>

<div id='guestloginbox' class="modal" title='Login as a Guest'>
    Some studies are only available to people in a certain age range or of a certain sex,
    so you can optionally fill in that information to be able to participate in those studies.
<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';
    
    $input = array();
    $input_width = 250;
    
    // sex
    $input['sex'] = new select('sex', 'sex');
    $input['sex']->set_question('Sex');
    $input['sex']->set_options(array(
        'male'=>loc('male'),
        'female'=>loc('female'),
        'nonbinary'=>loc('these options do not apply to me (e.g., nonbinary)'),
        'na' => loc('prefer not to answer')
    ));  
    
    // birthday
    //$input['birthday'] = new dateMenu('birthday', 'birthday');
    $input['birthday'] = new formElement('birthday', 'birthday');
    $input['birthday']->set_question('Birthday <small>Year required for some studies</small>');
    $year = new selectnum('year','year',0);
    $year->set_options(array(0 => "----"), date("Y"), date("Y") - 120);
    $year->set_null(false);
    $month = new select('month','month',0);
    $month->set_options(array(
        0 => "----------",
        1 => loc("January"),
        2 => loc("February"),
        3 => loc("March"),
        4 => loc("April"),
        5 => loc("May"),
        6 => loc("June"),
        7 => loc("July"),
        8 => loc("August"),
        9 => loc("September"),
        10 => loc("October"),
        11 => loc("November"),
        12 => loc("December")
    )); 
    $month->set_null(false);
    $day = new selectnum('day','day',0);
    $day->set_options(array(0=>"--"), 1, 31);
    $day->set_null(false);
    
    $input['birthday']->set_custom_input( $year->get_element() . ' ' .
                                          $month->get_element() . ' ' .
                                          $day->get_element() );
                                          
    // set up form table
    $q = new formTable();
    $q->set_table_id('guestreg');
    $q->set_title('');
    $q->set_action('');
    $q->set_questionList($input);
    $q->set_method('post');
    $q->set_buttons(array(
        'Register' => 'register();'
    ));
    $q->set_button_location('bottom');
    
    $q->print_form();
?>
    <div id='login_error' class='ui-state-highlight'></div>
    <br>
    <div class="note">This website requires cookies to allow you to log in. Logging in indicates you agree to this.</div>
</div>
        
</div> <!-- END OF maincontent -->

<?= $menu ?>
        
</div></div> <!-- END OF content AND contentmask -->

<!-- END OF CONTENT -->

</div> <!-- end of div for sticky footer -->

<!-- START FOOTER -->

<footer id="footer">
    <small><?= FOOTERTEXT ?></small>
</footer> 

<!-- END OF FOOTER -->

</body>
</html>

<?php
    }

}
 
?>