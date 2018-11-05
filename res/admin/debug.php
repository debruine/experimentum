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


?>

<ul>
    <li>All Statuses: <?= implode(", ", $ALL_STATUS) ?> is array <?= is_array($ALL_STATUS) ?></li>
    <li>Researcher Statuses: <?= implode(", ", $RES_STATUS) ?></li>
    <li>MySQL time: <?= $mysql_time ?></li>
    <li>PHP time: <?= $php_time ?></li>
    <li>max_input_vars: <?= ini_get('max_input_vars') ?></li>
    <li>Memory limit: <?= ini_get('memory_limit') ?></li>
    <li>User agent: <?= $_SERVER['HTTP_USER_AGENT'] ?></li>
    <li>Window width: <span id="wwidth"></span></li>
    <li>Window height: <span id="wheight"></span></li>
</ul>

<script>
    $('#wwidth').html($(window).width());
    $('#wheight').html($(window).height());
</script>

<h2>$_SESSION Variables</h2>

<?php
htmlArray($_SESSION);


if (isset($_GET['phpinfo'])) {
	echo "<h2>PHP Info</h2>";
	phpinfo(); 
} else {
    echo "<a href='debug?phpinfo'>Show PHP Info</a>";
}

$page->displayFooter();

?>