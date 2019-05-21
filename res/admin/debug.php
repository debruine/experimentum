<?php

error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

/****************************************************/
/* !Display Page */
/***************************************************/

$title = array(
	"/res/" => loc("Researchers"),
	"/res/admin/" => loc("Admin"),
	"/res/admin/debug" => loc("Debug")
);
$page = new page($title);
$page->set_menu(false);

$page->displayHead();
$page->displayBody();

$q = new myQuery('SELECT NOW()');
$mysql_time = $q->get_one();
$php_time = date('Y-m-d H:i:s');

$_SESSION['debug'] = true;

// prepare test
$query = "SELECT * FROM res WHERE user_id = ? AND firstname = ?";
$params = array('is', 1, 'Lisa');
$q = new myQuery();
$q->prepare($query, $params);
echo $q->get_result_as_table();


$q->prepare('SELECT * FROM res WHERE firstname = ?', array('s', 'Ben'));
echo $q->get_result_as_table();

?>

<ul>
    <li>All Statuses: <?= implode(", ", $ALL_STATUS) ?></li>
    <li>Researcher Statuses: <?= implode(", ", $RES_STATUS) ?></li>
    <li>MySQL time: <?= $mysql_time ?></li>
    <li>PHP time: <?= $php_time ?></li>
    <li>max_input_vars: <?= ini_get('max_input_vars') ?></li>
    <li>Memory limit: <?= ini_get('memory_limit') ?></li>
    <li>User agent: <?= $_SERVER['HTTP_USER_AGENT'] ?></li>
    <li>Window width: <span id="wwidth"></span></li>
    <li>Window height: <span id="wheight"></span></li>
    <li>HTTP_HOST: <?= $_SERVER['HTTP_HOST'] ?></li>
</ul>

<script>
    function windowdim() {
        $('#wwidth').html($(window).width());
        $('#wheight').html($(window).height());
    }
    
    window.onresize = windowdim;
    
    windowdim();
</script>

<h2>$_SESSION Variables</h2>

<?php
htmlArray($_SESSION);

    
if ($_SESSION['status'] == 'admin') {
    echo '<h2>$_SERVER Variables</h2>';
    htmlArray($_SERVER);
}
        


if (isset($_GET['phpinfo'])) {
	echo "<h2>PHP Info</h2>";
	phpinfo(); 
} else {
    echo "<a href='debug?phpinfo'>Show PHP Info</a>";
}

$page->displayFooter();

?>