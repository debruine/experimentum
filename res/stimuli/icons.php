<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('student', 'researcher', 'admin'));

$title = array(
    '/res/' => 'Researchers',
    '/res/stimuli/' => 'Stimuli',
    '' => 'Icons'
);

$styles = array( 
    '.gallery div' => "
        width: 85px; 
        height: 85px; 
        text-align: center; 
        float: left; 
        margin: 5px; 
        background: " . THEME . " 50% 75% no-repeat; 
        background-size: auto 50%;
        color: white; 
        font-size: 60%; 
        border-radius: 1em; 
        padding: 4px; 
        box-shadow: 2px 2px 4px rgba(0,0,0,.5);",
    '.gallery div:active' => 'box-shadow: 1px 1px 2px rgba(0,0,0,.5);',
    '#minigallery div' => 'width: 70px; height: 70px;',
    '#maincontent' => 'overflow: visible;',
    'h2' => 'margin: 0 auto 0; padding: 1em 0 0 0;'
);

/****************************************************
 * Get icon list
 ***************************************************/


$basedirs = array(
    "linearicons" => "/images/linearicons/"
);

$gallery = array(
    "linearicons" => "",
);

foreach ($basedirs as $section => $basedir) {
    $d = dir($_SERVER['DOCUMENT_ROOT'] . $basedir);
    $images = array();
    
    while (false !== ($f = $d->read())) {
        if (substr($f, -4) == ".php") {
            $name = str_replace('.php', '', $f);
            $images[$name] = $basedir . $f . "?c=FFFFFF";
        }
    }
    
    foreach ($images as $name => $src) {
        $gallery[$section] .= " <div style='background-image: url(\"$src\");' title='$src'>$name</div>\n";
    }
}


/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<div class="toolbar">
    Search: <input type='text' id='search' />
</div>

<h2><a href="https://linearicons.com/free">LinearIcons</a></h2>
<div id='icongallery' class='gallery'>
    <?= $gallery['linearicons'] ?>
</div>


<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script>
    $(function() {
        
        // narrow-down search as you type
        $('#search').keyup( function() { narrowTable('.gallery', this.value); } );
        
        // get url of icon when clicked
        $('.gallery div').click( function() {
            alert($(this).attr('title'));
        });
    });
</script>

<?php

$page->displayFooter();

?>