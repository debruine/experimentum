<?php

/****************************************************
 * Configuration file
 ***************************************************/

    /*** Constants ***/
    
    define('ENDLINE', "\n");
    define('ENDTAG', "\n\n");
    define('THEME_HUE', '280');  // theme hue 
    define('THEME', 'hsl('. THEME_HUE . ',100%,20%)'); // theme colour 
    
    define('SITETITLE', '** CHANGE TO TITLE OF YOUR WEBSITE **');
    define('FOOTERTEXT', '** CHANGE TO TEXT FOR THE WEBSITE FOOTER **');
    
    $ALL_STATUS = array('test','guest','registered','student','res','admin');
    $RES_STATUS =  array('student','res','admin');
    
    /*** Localisation ***/
    
    date_default_timezone_set('Europe/London');
    
    /*** error reporting ***/
    
    // error_reporting(E_ALL & ~E_NOTICE); // for debugging
    error_reporting(E_ERROR); // for runnning
    
    /*** MySQL Variables ***/
    
    define('MYSQL_DB', '**CHANGE TO YOUR DATABASE NAME**');
    define('MYSQL_HOST', '**CHANGE TO YOUR HOST**');
    if (in_array($_SESSION['status'], $RES_STATUS)) {
            // login for researchers (has permission to add and drop tables)
        define('MYSQL_USER', '**CHANGE TO MY ADMIN USERNAME**');
        define('MYSQL_PSWD', '**CHANGE TO MY ADMIN PASSWORD**');
    } else {
        // login for others
        define('MYSQL_USER', '**CHANGE TO MY USER USERNAME**');
        define('MYSQL_PSWD', '**CHANGE TO MY USER PASSWORD**');
    }
    
    // check if on a mobile device (fix this)
    $mobile = strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone');
    $mobile = $mobile || strpos($_SERVER['HTTP_USER_AGENT'], 'Android');
    if ($mobile) {
        define('MOBILE', true);
    } else {
        define('MOBILE', false);
    }
    
    /*** Versions of External Libraries ***/
    
    if ($_SERVER['SERVER_NAME'] == 'exp.test-no') {
        define('JQUERY', "/include/js/jquery.js");
        define('JQUERYUI', "/include/js/jquery-ui.js");
    } else {
        define('JQUERY', "https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
        define('JQUERYUI', "https://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js");
    }
    
    define('HIGHCHARTS', '2.2.5');
    
    
    /*** Code for anonymising IP addresses ***/
    define('IP_SECRET', '** CHANGE TO ANY STRING YOU WANT **');

?>