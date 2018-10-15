<?php

error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page('Debug');
$page->set_logo(true);
$page->set_menu(false);

$page->displayHead();
$page->displayBody();

$q = new myQuery('SELECT NOW()');
$mysql_time = $q->get_one();
$php_time = date('Y-m-d H:i:s');


echo "<p>max_input_vars: " . ini_get('max_input_vars') . '</p>';

echo "Errors reported";

echo "<h2>MySQL time: $mysql_time; PHP time: $php_time</h2>";

echo 'Memory limit: ' . ini_get('memory_limit');

// session Variables
$_SESSION['debug'] = true;
echo "<h2>\$_SESSION Variables</h2>\n";

htmlArray($_SESSION);


if (isset($_GET['phpinfo'])) {
	echo "<h2>PHP Info</h2>";
	phpinfo(); 
}

$page->displayFooter();

?>