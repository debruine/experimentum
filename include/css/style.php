<?php
    require_once $_SERVER['DOCUMENT_ROOT'] .'/include/config.php';
    header("Content-Type: text/css");

/*-------------------------------------------------
CSS for all pages in my php_practice webpage

PAGE COLORS 
-------------------------------------------------*/
    $saturation = "0%";
    $bgcolor = 'hsl(' . THEME_HUE . ",{$saturation},90%)"; // very light theme
    $text = '#222';                     // (very dark grey)
    $theme = 'hsl(' . THEME_HUE . ",{$saturation},10%)"; // dark theme
    $highlight = 'hsl(' . THEME_HUE . ",{$saturation},30%)"; // bright theme
    $text_on_theme = 'white';
    $shade = 'hsl(' . THEME_HUE . ",{$saturation},75%)"; // light theme
    $border_color = 'white';
    
    $border = '3px solid ' . $border_color;
    $round_corners = 'padding: .25em; -khtml-border-radius: .5em; -webkit-border-radius: .5em; border-radius: .5em; ';
    $big_round_corners = 'padding: .25em; -khtml-border-radius: .5em; -webkit-border-radius: .5em; border-radius: .5em;';
    
    function shadow($horiz='2px', $vert='2px', $blur='4px', $color='rgba(0,0,0,.5)') {
        return "box-shadow: $horiz $vert $blur $color;";
    }
    
    function roundCorners($r = '1em') {
        return "-khtml-border-radius: $r; -webkit-border-radius: $r; border-radius: $r;";
    }

?>

/*-------------------------------------------------
FONTS
-------------------------------------------------*/

:root {
    --rainbow-red: hsl(0,100%,30%); 
    --rainbow-orange: hsl(30,100%,35%);
    --rainbow-yellow: hsl(50,100%,35%);
    --rainbow-green: hsl(120,100%,25%);
    --rainbow-blue: hsl(200,100%,30%);
    --rainbow-purple: hsl(280,100%,30%);
}

@font-face{
    font-family: 'Fira Code';
    src: url('fonts/FiraCode-Light.eot');
    src: url('fonts/FiraCode-Light.eot') format('embedded-opentype'),
         url('fonts/FiraCode-Light.woff2') format('woff2'),
         url('fonts/FiraCode-Light.woff') format('woff'),
         url('fonts/FiraCode-Light.ttf') format('truetype');
    font-weight: 300;
    font-style: normal;
}

@font-face{
    font-family: 'Fira Code';
    src: url('fonts/FiraCode-Regular.eot');
    src: url('fonts/FiraCode-Regular.eot') format('embedded-opentype'),
         url('fonts/FiraCode-Regular.woff2') format('woff2'),
         url('fonts/FiraCode-Regular.woff') format('woff'),
         url('fonts/FiraCode-Regular.ttf') format('truetype');
    font-weight: 400;
    font-style: normal;
}

@font-face{
    font-family: 'Fira Code';
    src: url('fonts/FiraCode-Medium.eot');
    src: url('fonts/FiraCode-Medium.eot') format('embedded-opentype'),
         url('fonts/FiraCode-Medium.woff2') format('woff2'),
         url('fonts/FiraCode-Medium.woff') format('woff'),
         url('fonts/FiraCode-Medium.ttf') format('truetype');
    font-weight: 500;
    font-style: normal;
}

@font-face{
    font-family: 'Fira Code';
    src: url('fonts/FiraCode-Bold.eot');
    src: url('fonts/FiraCode-Bold.eot') format('embedded-opentype'),
         url('fonts/FiraCode-Bold.woff2') format('woff2'),
         url('fonts/FiraCode-Bold.woff') format('woff'),
         url('fonts/FiraCode-Bold.ttf') format('truetype');
    font-weight: 700;
    font-style: normal;
}


/*-------------------------------------------------
PAGE BODY and LAYOUT 
-------------------------------------------------*/

/* zero the margins, paddings and borders for all elements */
* { 
    margin:0; 
    padding:0; 
    border:0; 
    line-height: 1.2em;
    box-sizing: border-box;
}

.shadow { <?= shadow() ?> }

.modal {
    display:none;
    z-index:2000;
}

body {
    font-family:"Fira Code", "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", "Lucida", "Trebuchet MS", verdana, helvetica, arial, sans-serif;
    font-size:100%; 
    color:<?= $text ?>;
    background: <?= $bgcolor ?> url(/images/bg/pw_maze_white/pw_maze_white.png) 0 0 repeat;
    width:100%;
}

pre {
    font-family:"Fira Code", "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", "Lucida", "Trebuchet MS", verdana, helvetica, arial, sans-serif;
    font-size:100%; 
    color:<?= $text ?>;
    white-space: pre-wrap;
}
    
textarea, input, select, td { 
    font: inherit;
}

/* Heights for Sticky Footer */
html, body {height: 100%;}
#wrap {min-height: 100%;}

#header {
    clear:both;
    float:left;
    width: 100%;
    padding: 10px 0 15px;
    background-color: <?= $theme ?>;
    color: <?= $text_on_theme ?>;
    /* border-bottom: <?= $border ?>; */
    <?= shadow(); ?>
    margin-bottom: 1em;
    box-shadow: 0 1.5px 0 0px var(--rainbow-purple),
                0 3px 0 0px var(--rainbow-blue),
                0 4.5px 0 0px var(--rainbow-green),
                0 6px 0 0px var(--rainbow-yellow),
                0 7.5px 0 0px var(--rainbow-orange),
                0 9px 0 0px var(--rainbow-red),
                0 9.5px 1px 0px rgba(0,0,0,.5);
}

#header.minimal {
    padding-bottom: 10px;
    font-size: 75%;
}

#header.minimal .fb_profile_pic { display: none; }

#breadcrumb { 
    float: left; 
    text-align: left; 
    font-size: 1.2em;
    /*width: 65%; */
    padding: 0 0 0 20px; 
    margin: 0;
    color: <?= $text_on_theme ?>;
}

#breadcrumb li { 
    display: inline; 
    padding: 0; 
    margin: 0;
}

ul#login_info {
    font-size: smaller;
    float: right;
    height: 50px;
    /*width: 30%;*/
    text-align: right;
    padding: 0 20px 0 0;
    margin: 0;
}

#header_username {
    position:relative; /* for fb_profile_pic positioning */
}

div.fb_profile_pic {
    vertical-align: text-top;
    width:50px;
    height:50px;
    position: absolute;
    left:-50px;
    top:0;
    background: no-repeat center center;
}


#login_info li { 
    /*display: block; 
    float: left;*/
    margin: 0 0 5px 0;
    padding: 0 .5em 0 .5em;
    /*white-space: nowrap;*/
}

ul#login_info li label { display: block; text-align: left; }
ul#login_info li input { display: block; text-align: left; }

#loginbox {
    text-align: left;
    display: none;
}

#loginbox label {
    font-size:smaller;
    display: block;
}

#loginbox input {
    width: 100%;
    font-size: 200%;
}

#login_error {
    display: none;
    width: 95%;
    margin: 1em auto;
}

/* Facebook Connect button styles */

#fb-login {
    height: 2em;
    width: 10em;
    overflow: hidden;
}

a:hover.fb_button, a:hover.fb_button_rtl {
    text-decoration: none;
    border: none;
}

.users_online {
    font-size: x-small; 
    float: right; 
    clear: right; 
    padding: .5em 20px 0 0;
    color: <?= $bgcolor ?>;
}

#contentmask {
    position:relative;              /* This fixes the IE7 overflow hidden bug */
    clear:both;
    float:left;
    width:100%;                     /* width of whole page */
    overflow:hidden;                /* This chops off any overhanging divs */
    padding-bottom: 55px;           /* must be same height as the footer */ 
}

#content {
    float:left;
    width:100%;
    position:relative;
    right: 0;
}

#maincontent, #menu {
    float:left;
    position:relative;
}

#maincontent {
    box-sizing:border-box;
    -moz-box-sizing:border-box;     /* Firefox */
    -webkit-box-sizing:border-box;  /* Safari */
    width: 100%;
    min-height: 600px;
    padding-right: 280px;
    padding-left: 50px;
    overflow:auto;
    padding-bottom: 1em;                
}

#menu {
    position: absolute;
    width: 220px;
    right: 50px;
    min-width: 220px;   
}


.nomenu #content {
    right:0;                        /* altered widths with no right menu */
}

.nomenu #maincontent {
    padding-right: 50px;        
}

#footer {
    position: relative;
    margin-top: -55px;              /* negative value of footer height */
    height: 55px;                   /* footer height */
    clear:both;
    float:left;
    width:100%;
    padding:5px 0;
    /*border-top: <?= $border ?>;*/
    background-color: <?= $theme ?>;
    color: <?= $text_on_theme ?>;
    text-align: center;
    <?= shadow(); ?>
    box-shadow: 0 -1.5px 0 0px var(--rainbow-purple),
                0 -3px 0 0px var(--rainbow-blue),
                0 -4.5px 0 0px var(--rainbow-green),
                0 -6px 0 0px var(--rainbow-yellow),
                0 -7.5px 0 0px var(--rainbow-orange),
                0 -9px 0 0px var(--rainbow-red),
                0 -9.5px 1px 0px rgba(0,0,0,.5);
}

#footer a, #footer a:visited, th a, th a:visited, tr.radiorow_values a, tr.radiorow_values a:visited { 
    color: <?= $text_on_theme ?>; 
    border-color: <?= $text_on_theme ?>;
}

#footer a:hover, #footer a:active, th a:hover,th a:active, tr.radiorow_values a:hover, tr.radiorow_values a:active { 
    background-color: <?= $text_on_theme ?>; 
    color: <?= $theme ?>; 
}

tr.radiorow_values a {
    border:none;
}

.disclaimer {
    color:#444;
    font-size:x-small;
    width:auto;
    max-width:100%;
    text-align:center;
}


/***** RIGHT COLUMN MENU *****/

#menu ul, #menu #fb_facepile, #menu #fb_rec, #menu #google_ad {
    width:220px;
}

#menu #google_ad {
    height: 200px;
    margin-top: 1em;
}

#menu > ul, #dash { 
    float: none; 
    border: <?= $border ?>;
    <?= $round_corners ?>
    <?= shadow() ?>
    color: white;
    padding: .5em 1em;
    margin: 1em 0;
    width: 220px;
}

#menu > ul {
    background-color: <?= $theme ?>;
}

#dash { 
    float: left;
    margin: 0em 0.5em 0.5em 0;
    background-color: hsl(0,100%,20%);
}

#myfavs { min-height: 20em; }

#dash li {
    padding: 0 0 10px 35px; 
    min-height: 30px; 
    text-align: left; 
    background-position: 0 0;
}

#dash li a {
    width:100%; 
    text-align: left; 
    border: 0; 
    color: white;
}

#dash li a:hover { text-decoration: underline; }

#dash li a:active { text-shadow: 0 0 .2em black; }

.new-owner { color: red; }
.delete-owner {
    text-decoration: line-through; 
    color: red;
}

#menu li { 
    font-weight: bold; 
    padding: .55em 0 .55em 40px;
    background-repeat: no-repeat;
    background-position: left center; 
    background-size: auto 70%;
}

li.home       { background-image: url("/images/linearicons/home?c=FFFFFF"); }
li.exp        { background-image: url("/images/linearicons/chart-bars?c=FFFFFF"); }
li.project    { background-image: url("/images/linearicons/briefcase?c=FFFFFF"); }
li.quest      { background-image: url("/images/linearicons/list?c=FFFFFF"); }
li.faq        { background-image: url("/images/linearicons/star?c=FFFFFF"); }
li.res        { background-image: url("/images/linearicons/graduation-hat?c=FFFFFF"); }
li.my         { background-image: url("/images/linearicons/user?c=FFFFFF"); }

#menu a { 
    border:none;
    font-weight:bold;
}
#menu a:link, #menu a:visited { 
    color:#FFF;
}
#menu a:hover, #menu a:active, #menu a:focus {
    color: white; /* <?= $theme ?>; */
    background-color:transparent;
    border-bottom: 1px solid white;
}


/***** HEADERS AND TEXT *****/

h1, h2, h3, h4, h5, h6 { 
    font-size:110%; 
    color:<?= $theme ?>;
    text-align:center; 
    padding:.5em 0;
    clear: both;
    max-width:40em;
    margin: 1em auto;
}

div > h1:first-child, 
div > h2:first-child,
div > h3:first-child,
div > h4:first-child,
div > h5:first-child,
div > h6:first-child {
    margin-top: 0;
}

h1 {
    text-align:left;
    font-variant:small-caps;
    padding:.5em;   
}

h3, h4, h5, h6 { font-size:90%; }

p {
    margin:1em auto;
    text-align:left;
    line-height:1.5;
    max-width:40em;
}

p.fullwidth { width:auto; max-width:100%; }

.modal { display: none; }

/* feature boxes for making information stand out */
.feature {
    max-width:30em;
    background:<?= $theme ?>;
    color:<?= $text_on_theme ?>;
    border: <?= $border ?>;
    text-align:center;
    margin:1em auto;
    <?= $big_round_corners ?>
    <?= shadow() ?>
}

.main {
    width:42%;
    min-width:200px;
    margin:0;
    float:left;
    text-align: left;
    margin: 1em 0 1em 3%;
}

.main h2 { margin: 0; }

.main ul li { padding: .25em 0; }

.feature h2 {
    background-color:<?= $theme ?>;
    color:<?= $text_on_theme ?>;
    padding:0;
}

.feature a:link, .feature a:visited {
    color:<?= $text_on_theme ?>;
    border-color: <?= $text_on_theme ?>;
}

.feature a:hover, .feature a:active {
    color:<?= $theme ?>;
    background-color:<?= $text_on_theme ?>;
}

strong { 
    color:<?= $theme ?>; 
    font-style:normal; 
    font-weight:bold; 
}

.sub {
    font-size: 65%;
    vertical-align: sub;
}

.reference {
    font-size: 75%;
    width:auto; max-width:100%;
}

.journal { font-style: italic; }

hr { 
    clear:both; 
    color:<?= $text ?>;
    background-color:<?= $text ?>;
    width:100%;
    height:2px;
    margin:.5em 0;
}

hr.invisible { height:0; margin:0; }

.hidden {
    display:none;
}

/***** TEXT LINKS *****/
a { 
    outline: none;
}
a:link, a:visited, a:hover, a:active, #menu a:focus {
    text-decoration:none; 
    border-bottom: .1em solid <?= $theme ?>;
}
a:link { color:#000; }
a:visited { color:#000; }
a:hover, a:focus { 
    color:<?= $theme ?>; 
}
a:active { 
    background-color:<?= $theme ?>; 
    color:<?= $text_on_theme ?>; 
    outline:none;
}

/***** SPECIAL LINKS *****/

#header a:link, #header a:visited {
    <?= $round_corners ?>
    padding: 0 .25em;
    color: <?= $text_on_theme ?>;
    text-decoration: underline;
}

#header a:hover, #header a:active, #header a:focus {
    color:<?= $theme ?>;
    background-color:<?= $text_on_theme ?>;
    text-decoration: none;
}

/***** IMAGE LINKS *****/
a img, a:hover img, a.no_underline {
    border: none;
}

/***** BASIC LISTS *****/
ul {
    list-style:none;
    padding:0;
    margin:.5em;
    /* float: left; */
}
li {
    background-repeat:no-repeat;
    background-position:0 5px;
    padding:2px 10px 2px 20px;
}

dl { margin: 1em 0; }
dt { 
    float: left; 
    clear: left; 
    width: 9em; 
    margin-left: 1em;
    text-align: right; 
    font-weight: bold;
    overflow: hidden;
    
}
dd {
    margin: 0 1em .5em 10.5em;
}

dd > dl {
    margin: 2em 0 0 -10.5em;
    border: 3px solid <?= THEME ?>;
}

dd > dl dd { margin-right: 0; }

ul.bigbuttons {
    margin-top: 1em;
}

.bigbuttons > li {
    display: inline;
    float: left;
    margin: 0 .5em .5em 0;
    padding: 0;
}
.bigbuttons > li a {
    display: block; 
    word-wrap: break-word;
    position: relative; 
    overflow: hidden;
    width: 15vw;
    height: 15vw; 
    min-width: 8em;
    min-height: 8em;
    max-width: 10em;
    max-height: 10em;
    background: <?= $theme ?> center 90% no-repeat;
    background-size: auto 40%;
    color: <?= $text_on_theme ?>;
    text-align: center;
    border: <?= $border ?>;
    <?= $big_round_corners ?>
    <?= shadow() ?>
}

.bigbuttons li.hide, .bigbuttons li.test, .bigbuttons li.inactive {
    display: none;
}

.bigbuttons li.done a {
    background-color: #73848C;
    background-color: hsl(200, 10%, 50%);
}

.bigbuttons li.hide a, .bigbuttons li.hide.done a {
    background-color: #990000;
    background-color: hsl(0, 100%, 30%);
}

.bigbuttons li.inactive a, .bigbuttons li.test a {
    background-color: #809900;
    background-color: hsl(70, 100%, 30%);
}

.bigbuttons li.inactive a:hover, .bigbuttons li.test a:hover {
    background-color: #AACC00 !important;
    background-color: hsl(70, 100%, 40%) !important;
}

.bigbuttons li a .corner {
    font-size: 70%; 
    position: absolute; 
    bottom: 1em; 
    right: -3em; 
    color: <?= $theme ?>;
    background-color: white; 
    display: block; 
    width: 10em;
    text-align: center;
    -webkit-transform: rotate(-45deg); 
    -moz-transform: rotate(-45deg);
}

.bigbuttons li.done a .corner { color: #AFC5CF; color: hsl(200, 10%, 50%); }
.bigbuttons li.hide a .corner { color: #990000; color: hsl(0, 100%, 30%); }


.bigbuttons li a.exp     { background-image: url("/images/linearicons/chart-bars?c=FFF"); }
.bigbuttons li a.quest   { background-image: url("/images/linearicons/list?c=FFF"); }
.bigbuttons li a.set     { background-image: url("/images/linearicons/layers?c=FFF"); }
.bigbuttons li a.project { background-image: url("/images/linearicons/briefcase?c=FFF"); }
.bigbuttons li a.stimuli { background-image: url("/images/linearicons/picture?c=FFF"); }
.bigbuttons li a.admin   { background-image: url("/images/linearicons/graduation-hat?c=FFF"); }

.fav { 
    display: inline-block;
    width: 25px; height: 25px;
    border: 1px solid <?= $theme ?>;
    <?= roundCorners('20px') ?>
    color: transparent;
    background: white no-repeat url() center center;
    background-size: 70% 70%;
    <?= shadow('1px','1px','2px') ?>
}

.fav:active {
    <?= shadow('0','0','1px') ?>
}

.fav:hover {
    background-image: url("/images/linearicons/heart?c=222");
}
.fav.heart, .fav.heart:hover {
    background-image: url("/images/linearicons/heart?c=cd0a0a"); /* cd0a0a for red */
}

.bigbuttons li a:hover {
    background-color: <?= $highlight ?>;
}
.bigbuttons li a:active {
    <?= shadow('1px','1px','2px') ?>
}

.bigbuttons li.disabled a, .bigbuttons li.disabled a:hover, .bigbuttons li.disabled a:active {
    <?= shadow('1px','1px','2px') ?>
    background-color: gray;
}

.bigbuttons li a .biginit {
    font-size: 4em;
    background-position: 50% 50%; 
    background-repeat: no-repeat;
}


/***** BUTTONS *****/

.buttons { 
    padding:1em 6px;
    text-align:center;
    vertical-align: middle;
    clear:both;
}

form > .buttons:first-child {
    padding-top: 0;
}

button.tinybutton {
    font-size: 70% !important;
    padding: 3px !important;
    border-width: 2px;
    border-radius: 5px;
    <?= shadow('1px','1px','2px') ?>
}

img.loading { 
    width:200px;
    height:200px;
    background: url(/images/loaders/loading.gif) no-repeat;
}

div.themeloader {
    display: block;
    width: 200px;
    height: 200px;
    margin: .5em auto;
    background: transparent center center no-repeat url('/images/loaders/loading.gif');
}

.searchline {
    text-align: center;
}

.toolbar { margin-bottom: 1em; }
.toolbar-line { margin: .5em 0; }
.toolbar .ui-button span { 
    font-size: 90%;
    padding: 0 .5em; 
}

.toolbar .ui-button-icon-only span {
    padding: 0;
}

/***** TABLES *****/

table {
    border-collapse: separate;
    border-spacing:0;
    <?= $round_corners ?>
    padding:0;
    margin: auto;
}

table .ui-buttonset .ui-button span {
    font-size: 85%; 
    padding: .1em .5em;
}

thead {
    background-color: <?= $theme ?>;
    color: <?= $text_on_theme ?>;
    text-align: center;
}

tfoot {
    background-color: <?= $theme ?>;
    color: <?= $text_on_theme ?>;
}

table.sortable thead th:hover { 
    background-color: <?= $highlight ?>;
    color: <?= $text_on_theme ?>; 
}   

th {
    border-bottom: <?= $border ?>;
}

td, th { 
    padding: .25em; 
    vertical-align: top;
}

table.expTable { width: 90%; }

table.expTable td + td + td {
    text-align: right;
}

table.chosen_table {
    width: 40vw;
}

table.chosen_table td {
    width: 50%;
}

table.chosen_table img {
    width: 100%;
}

tr.chosen0 img {
    border: 5px dotted red;
}
tr.chosen1 img {
    border: 5px solid green;
}

/**** Finder ****/

#finder {
    position: relative; 
    min-height: 200px; 
    margin-right: 350px; 
    overflow: auto;
    background-color: white; 
    font-size: 90%; 
    border: 1px solid gray;
}

#finder ul {
    margin: 0; 
    height: 100%; 
    position: absolute; 
    top: 0;
}

#finder ul ul {
    border-left: 1px solid gray;
}

#finder li, #finder li:hover {
    padding-left: 30px; 
    background: hsl(200, 100%, 20%) 5px center no-repeat url("/images/finder/folder_arrow_grey"); 
    color: white;
}

#finder li.image:hover, #finder li.folder.closed:hover {
    background-color: rgb(212,212,212);
}

#finder li.folder.closed {
    background: transparent 5px center no-repeat url("/images/finder/folder_arrow_grey"); 
    color: #333;
}

#finder li.folder.closed ul {
    display: none;
}

#finder li.image {
    background: transparent 5px center no-repeat url("/images/finder/imgicon?h=<?= THEME_HUE ?>");
    background-size: contain;
    color: #333; 
}

#finder li.image.selected {
    background-color: hsl(60, 100%, 90%);
}

#imagebox {position: fixed; right: 50px; }
#imagebox img {max-width: 300px; max-height: 400px;}
#imagebox #imageurl {width: 300px; margin-bottom: .5em; }


/***** Tables *****/

tbody tr.odd, li.odd {
    background-color: hsl(<?= THEME_HUE ?>,0%,80%);
}

tbody tr.even, li.even {
    background-color: hsl(<?= THEME_HUE ?>,0%,90%);
}

table.nostripe tbody tr.odd {
    background-color: transparent;
}

table.nostripe tbody tr.even {  
    background-color: transparent;
}

tbody tr.odd.emptyAlert {   
    background-color: #FBFBB6 !important;
}

tbody tr.even.emptyAlert {  
    background-color: #FCFCCF !important;
}

.expTable tbody tr>td:first-child {
    padding-left: 28px; 
    background: 5px center no-repeat url(/images/linearicons/star?c=<?= THEME ?>);
    background-size: auto 70%;
}

.expTable tbody tr.done>td:first-child {
    background-image: url(/images/linearicons/star?c=<?= THEME ?>);
    background-size: auto 70%;
}

tr.done, tr.done a:link, tr.done a:visited { 
    color: #475F6B;
    color: hsl(<?= THEME_HUE ?>, 0%, 35%); 
}
tr.done a:hover { color: <?= $theme ?>; }
tr.done a:active { color: white; }

/***** FORMS *****/
form {
    width: 80%;
    max-width: 800px;
    margin: 1em auto;
}

table.questionnaire, table.query, table.fb_chart {
    border: <?= $border ?>;
    <?= shadow() ?>
    margin: 1em auto 5px auto;
    max-width: 795px;
    clear: both;
}

table.questionnaire td.input select {
    max-width: 400px;
}

.radiorow_options {
    background-color: <?= $theme ?> !important;
    color: <?= $text_on_theme ?>;
    font-size: smaller;
}

.radiorow_options th {
    padding: 5px;
    color: <?= $text_on_theme ?>;
}

tr + tr.radiorow_options th {
    border-top: <?= $border ?>;
    background-color: <?= $theme ?>;
    color: <?= $text_on_theme ?>;
}

.radiopage td + td {
    text-align: center;
    vertical-align: middle;
}

.radiopage td.question {
    width: 50%;
}

.instructions {
    font-size: 100%;
    max-width: 795px;
}

table.questionnaire td.question {
    padding-right: 1em;
    text-align: left;
}

input, select, textarea {
    border:1px dotted <?= $theme ?>;
}

table.ranking {
    font-size: 125%;
}

tr.ranking {
    cursor: url(/images/linearicons/arrow-up), move;
    -moz-user-select: none; -webkit-user-select: none; -ms-user-select: none;
}

td.handle {
    padding: .25em 1em;
    text-align: right;
}

input:focus, select:focus, textarea:focus {
    <?= shadow('2px', '2px', '4px') ?>
    border-color: #660000;
}

input[type=number] { text-align: right; }
input[type=search] { <?= roundCorners('1em') ?> }

ul.radio, ul.vertical_radio { margin:0;}

ul.radio li { display:inline; }

ul.radio li, ul.vertical_radio li { padding:0; }

img.radio {
    width: 25px; height: 25px;
    background: top left no-repeat url("/images/linearicons/circle?c=<?= THEME ?>");
}

.shade { background-color:<?= $shade ?> !important; }
.highlight { background: url(/images/linearicons/star?c=<?= THEME ?>) no-repeat 6px 3px; }
.highlight td.question { padding-left: 27px; }

.delete_icon {
    width:17px; height:17px;
    background: url(/images/linearicons/trash?c=<?= THEME ?>) no-repeat center center;
}

label {
    line-height:1.2;
}

.note, small { 
    font-size:10px; 
    display: block;
}

#footer small { display: inline; }

.note a {
    border-width: 1px;
}

.formlist {
    clear:both;
}
.formlist li { 
    clear:both;
    padding:3px;
}
.formlist li>label:first-child {
    float:left;
    text-align:right;
    width:35%;
}
.formlist li input, .formlist li select, .formlist li ul {
    float:right;
    text-align:left;
    width:60%;
    margin:0;
    padding:0;
}

.radiopage input[type=radio], .radioanchor input[type=radio] { display: none; }
/*.radiopage input[type=radio] + label:before,
.radioanchor input[type=radio] + label:before {
    content: "";
    display: inline-block; 
    width: 20px; height:20px; padding: 0;
    <?= roundCorners('10px') ?>
    background-color: transparent;
    box-shadow: inset 0px 0px 1px rgba(0,0,0,.8);
}*/
.radiopage input[type=radio] + label,
.radioanchor input[type=radio] + label { 
    display: inline-block; 
    width: 20px; height:20px; padding: 0;
    background-color: transparent; 
    border: 3px solid white; 
    <?= roundCorners('10px') ?>
    color: transparent; font-size: 20px; overflow: hidden;
    box-shadow: 1px 1px 2px rgba(0,0,0,.8);
}
.radiopage input[type=radio]:checked + label,
.radioanchor input[type=radio]:checked + label { 
    background-color: hsl(<?= THEME_HUE ?>, 20%, 30%); }

.radiopage input[type=radio] + label:hover,
.radioanchor input[type=radio] + label:hover { 
    background-color: hsl(<?= THEME_HUE ?>, 20%, 40%); 
}
.radiopage input[type=radio] + label:active,
.radioanchor input[type=radio] + label:active   { box-shadow: 0 0 1px rgba(0,0,0,.5); }
    

.radiogroup, .checkboxgroup { list-style:none; }
.formlist li .checkboxgroup *, .formlist li .radiogroup * {
    float:none;
}

.radioanchor {
    width: 100%;
    margin: 0;
    padding: 0;
}

#low_anchor, #high_anchor {
    max-width: 10em;
    display: inline-block;
}

.buttonrow #low_anchor { text-align: right; }
.buttonrow #high_anchor { text-align: left; }

.radioanchor tr { background-color: transparent !important; }

img.radio {
    width: 25px; height:25px;
}

.radioanchor td, .radioanchor td+td { text-align: center; }

td.anchor { 
    width: 10em !important; 
    font-size: 80%;
}

.labnotes { font-size: 65%; }

.unsaved {
    background: #FAFAD1;
    border-color: yellow;
}

#help { display: none; }

.helpbutton {
    float: right;
    position: relative;
    left: -60px;
    width: 44px;
    height: 44px;
    text-align: center;
    font-size: 70%;
    line-height: 40px;
    border: 1px solid white;
    background-color: <?= $highlight ?>;
    <?= roundCorners('25px') ?>
    <?= shadow('2px','2px','4px') ?>
}

.helpbutton:hover, .helpbutton:active {
    background-color: var(--rainbow-red);
}

.helpbutton:active {
    <?= shadow('1px','1px','1px') ?>
}

#help ul { list-style: circle url("/images/linearicons/star?c=<?= THEME ?>") outside; }
#help ul li { padding-left: 0; margin-left: 1em;}

#graph_container {
    width: 90%; 
    max-width: 1000px;
    height: 500px; 
    margin: 1em auto;
}

/* old graph styles */

table.fb_chart { 
}

.fb_chart td { 
    vertical-align: bottom;
    text-align: center !important;
}

.fb_chart td img { 
    width: 50px; 
    vertical-align: bottom; 
}

.graph0 { background-color: #FFF; }
.graph1 { background-color: #C00; }
.graph2 { background-color: #CC7800; }
.graph3 { background-color: #FC0; }
.graph4 { background-color: #060; }
.graph5 { background-color: #06C; }
.graph6 { background-color: #00C; }
.graph7 { background-color: #609; }

/*-------------------------------------------------
EXPERIMENT STYLES
-------------------------------------------------*/

#experiment {
    margin-top: 1em;
    text-align: center;
}

#experiment table {
    margin: 0 auto;
    table-layout: fixed;
    width: 100%;
    max-width: 1200px;
}

#experiment table.xafc, #experiment table.sort {
    max-width: none;
}

#experiment table tr {
    background: none;
}

#experiment #question {
    text-align: center;
    padding-bottom: .5em;
    font-size: 125%;
}

#experiment #question h3 {
    padding: 0;
    margin: 0;
    font-size: 150%;
    color: red;
    text-shadow: 0px 0px 1px #000000;
}

#continue_button input {
    display: block;
    margin: 1em auto;
}

#recording {
    font-size: 150%;
    line-height: 300px;
}

#image_loader, #recording { 
    background: transparent center center no-repeat url(/images/loaders/loading.gif); 
    background-size: contain;
    width: 300px; 
    height: 300px; 
    text-align: center; 
    margin: 1em auto;
    position: relative;
}   
#image_loader span { 
    position: absolute;
    top: 0;
    left: 0;
    display: block;
    width: 300px;
    font-size: 50px; 
    line-height: 300px; 
}
#image_loader div { 
    width: 100%;
    position: absolute;
    top: 0;
    font-size: 16px;
}
#image_loader img, #image_loader audio, #image_loader video { 
    visibility: hidden; 
    width: 1px; 
    height: 1px;
}
#image_loader img#loader { visibility: visible; width:300px; height:300px;}

.audio {
    width: 5em; 
    font-size: 300%;
    margin: 1em auto;
    -moz-user-select: none; -webkit-user-select: none; -ms-user-select: none;
    text-align: center; 
}
    
.audio span.play {
    height: 1.4em; 
    line-height: 1.4em;
    <?= roundCorners('3em') ?>
    border: 5px solid white; 
    color: white; 
    background-color: <?= $theme ?>; 
    box-shadow: 4px 4px 6px rgba(0,0,0,.5); 
    display: block; 
}
.audio span.play:active, .audio span.choose:active { box-shadow: 2px 2px 4px rgba(0,0,0,.5); }
.audio.played span.play { 
    background-color: hsl(<?= THEME_HUE ?>, 10%, 50%); 
}
.audio.playing span.play { 
    background-color: hsl(<?= THEME_HUE ?>, 100%, 30%); 
}
.audio span.choose { 
    display: block;
    font-size: 70%;
    width: 4em;
    margin: 0 auto;
    color: hsl(<?= THEME_HUE ?>,100%,30%);
    background-color: white;
    padding: 0 .5em;
    border: 3px solid hsl(<?= THEME_HUE ?>, 100%, 30%);
    border-bottom-left-radius: 1em; 
    border-bottom-right-radius: 1em; 
    box-shadow: 4px 4px 6px rgba(0,0,0,.5); 
}

table.jnd {
    /* border: 2px solid <?= $theme ?>; */
    <?= roundCorners('0') ?>
}

.jnd .input_interface td { 
    font-size: 90%; 
    border: 2px solid <?= $theme ?>; 
    width: 12.5% !important;
    min-height: 4em;
    vertical-align: middle;
    padding: .5em .25em;
    background-color: <?= $shade ?>;
    -moz-user-select: none; -webkit-user-select: none; -ms-user-select: none;
}

.jnd tr.exp_images td {
    border: 2px solid <?= $theme ?>; 
}

img#left_image { margin: 0 0 0 auto; }
img#right_image { margin: 0 auto 0 0; }
img#center_image, .jnd img#left_image, .jnd img#right_image { margin: 0 auto; }

.jnd .input_interface td:active { 
    color: <?= $text_on_theme ?>;
    background-color: <?= $theme ?>; 
}


.buttons .input_interface input {
    font-size: 150%;
}

input.rating {
    font-size: 200%;
    width: 2.5em;
    text-align: center;
}

.exp_images td {
    /* border: 2px solid <?= $border_color ?>; */
    background-color: transparent;
    text-align: center;
}

.jnd .exp_images td {
    background-color: rgba(255,255,255,1);  
}

.exp_images td img {
    display: block;
    margin: 0 auto;
    max-width: 100%;
    max-height: 800px;
}

table.sort .input_interface input[type=text] { width: 2em; }

table.xafc tr.exp_images td img, table.sort tr.exp_images td img { 
    display: inline-block;
    min-width: 150px;
}

table.sort tr.exp_images td img.sort_placeholder { 
    min-height: 150px;
    border: 2px solid <?= $theme ?>;
    background-color: <?= $theme ?>;
}

table.tafc tr.exp_images td img, table.xafc tr.exp_images td img {
    border: 2px solid white;
}

table.tafc tr.exp_images td img:active, table.xafc tr.exp_images td img:active {
    border: 2px solid <?= $theme ?>;
}

table.tafc tr.exp_images td#center_image img:active {
    border: 2px solid white;
}

img.motivation {
    width: 300px !important; 
    display: inline-block;
}

#motivation-container {
    width: 450px; 
    margin: 0 auto;
}

table.motivation #spacebar {
    text-align: center; 
    line-height: 400px; 
}

table.motivation #countdown {
    height: 400px; 
    margin-right: 20px; 
    float: left;
}

table.motivation #countdownlabels {
    float: left;
    line-height: 200px;
}

table.motivation .ui-slider { width: 25px; }
table.motivation .ui-slider-handle { display: none; }

.motivation + .trialcounter { display: none; }

#feedback_averages {
    margin: 1em auto;
    width: 650px;
}
#feedback_averages img {
    width: 300px;
    height: 400px;
}


/*-------------------------------------------------
MOBILE STYLES
-------------------------------------------------*/
@media screen and (max-width: 600px) {
    body {
        font-family: "Fira Code", Helvetica;
        -webkit-text-size-adjust:none;
        font-size:16px; 
        background-image: none;
    }
    
    body.logo {
        background-image: none;
    }
    
    img { max-width: 100%; height: auto; }
    
    .nomenu #maincontent, #maincontent { 
        padding: 5px;
        min-height: 10px;
    }
    
    #content { right:0; }
    
    #contentmask { padding-bottom: 0; }
    
    #breadcrumb { padding-left: 5px; }
    
    #breadcrumb li { 
        display: block; 
    }
    
    .fb_profile_pic { 
        display: none; 
        left: 0;
    }
    
    .helpbutton {
        left: 0;
        top: 4em;
        margin-right: 5px;
        float: right;
    }
    
    ul#login_info {
        font-size: 100%;
        position: absolute;
        top: 10px;
        right: 0;
        padding-right: 0px;
    }
    
    #header {
        margin-bottom: 0;
    }
    
    #menu {
        display: block;
        position: relative;
        width: 100%;
        min-height: 10px;
        right: 0;
        min-width: none;
        padding: 0px;   
    }
    
    #menu ul {
        background-color: transparent;
        border: none;
        <?= roundCorners('0') ?>
        box-shadow: none;
        width: auto;
        margin: 0;
        padding: 0;
        font-size: 17px;
        margin: 5px;
    }
    
    #menu li {
        display: block;
        width: auto;
        color: black;
        background-image: none !important;
        padding: 0;
        text-align: center;
    }
    
    #menu ul li a, #menu ul li a:link, #menu ul li a:visited {
        display: block;
        width: auto;
        margin-bottom: -1px;
        padding: 12px 10px;
        color: black;
        background-color: white;
        border: 1px solid #999;
    }
    
    #menu ul li a:active {
        border-bottom: 1px solid #999;
        background-color: <?= THEME ?>;
        color: white;
    }
    
    #menu ul li:first-child a {
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }
    #menu ul li:last-child a {
        border-bottom-left-radius: 8px;
        border-bottom-right-radius: 8px;
    }
    
    #footer {
        margin-top: 0;
        height: auto;
    }
    
    p {
        margin:.5em 0;
        line-height:1.3;
        max-width:100%;
    }
    
    .bigbuttons {
        margin: 1em 0;
    }
    
    .bigbuttons li {
        font-size: 17px;
        margin: 0 5px 5px 0;
        width: 100%;
    }
    
    .bigbuttons li a {
        <?= roundCorners('0.5em') ?>
        border-width: 2px;
        width: 100%;
        height: 2.5em; 
        min-width: 100%;
        min-height: 2.5em;
        max-width: 100%;
        max-height: auto;
        padding-left: 2em;
        background: <?= $theme ?> 10px no-repeat;
        background-size: 1.5em auto;
    }
    
    form {
        margin: 0;
        width: 100%;
        min-width: 100%;
        max-width: 100%;
    }
    
    table.questionnaire, table.query, table.fb_chart {
        margin: 0;
        width: 100%;
        max-width: 100%;
        clear: both;
        border: none;
        <?= roundCorners('0') ?>
        box-shadow: none;
    }
    
    table.ranking {
        width:90%;
        margin: 0 10% 0 0;
    }
    
    td.handle {
        padding: .25em .25em;
    }
    
    table.questionnaire td.input select, select {
        max-width: 100%;
    }
    
    tr.mobile_radiorow_div, tr.mobile_radiorow_div td { height: 0; padding: 0; margin: 0; }
    
    td.question, td.input { 
        width: 100%;
        overflow: visible;
        display: block;
    }
    
    table.questionnaire input, table.questionnaire select, table.questionnaire ul.radio { 
        font-size: 150%; 
        max-width: 100%;
        width: 100%;
    }
    
    input, select, ul.radio { 
        font-size: 150%; 
        max-width: 100%;
    }
    
    .selectnum { font-size: 200%; }
    
    dt { 
        display: block;
        float: none; 
        width: auto; 
        margin-left: 0;
        text-align: left; 
    }
    dd {
        display: block;
        margin: 0 0 0 1em;
    }
    
    .ui-button { font-size: 120%; }
    .ui-dialog { max-width: 100%; width: 95%; }

    #dash {
        width: 100%;
        min-width: 100%;
        max-width: 100%;
        background-color: transparent;
        border: none;
        box-shadow: none;
        padding: 0;
        color: <?= $theme ?>;
    }
    
    #myfavs { min-height: auto; }
    
    #dash ul {
        width: 100%;
        margin: 0;
        padding: 0;
    }
    
    #dash li {
        width: 100%;
        height: auto;
        border: 2px solid <?= $border_color ?>;
        background-color: hsl(0,100%,20%);
        background-position: 10px center;
        background-size: 1.5em auto;
        <?= roundCorners('0.5em') ?>
        <?= shadow() ?>
        padding: 0.5em 0.5em 0.5em 2em;
    }
    
    #dash li a, #dash li a:active {
        padding: 0.5em;
        background-color: transparent;
    }
    
    #low_anchor, #high_anchor {
        max-width: 75%;
        display: block;
    }
    #low_anchor, {
        text-align: left;
    }
    #high_anchor {
        float: right;
        text-align: right;
    }
    .buttons .input_interface input[type=button] {
        padding-left: 0.1em !important;
        padding-right: 0.1em !important;
        margin: 0;
    }
    
    table.xafc tr.exp_images td img, table.sort tr.exp_images td img { 
        min-width: 100px;
    }
    table.tafc tr.exp_images td img, table.xafc tr.exp_images td img,
    table.tafc tr.exp_images td img:active, table.xafc tr.exp_images td img:active,
    table.tafc tr.exp_images td#center_image img:active  {
        border-width: 1px;
    }

}