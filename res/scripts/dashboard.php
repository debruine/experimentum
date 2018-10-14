<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('student', 'researcher', 'admin'));

if (array_key_exists('add', $_GET)) {
	$from = array(
		'query' => 'name FROM queries',
		'exp' => 'res_name FROM exp',
		'quest' => 'res_name FROM quest',
		'set' => 'res_name FROM sets',
		'project' => 'name FROM project'
	);

	$myname = new myQuery('SELECT ' . $from[$_GET['type']] . ' WHERE id=' . intval($_GET['id']));
	$name = $myname->get_one();

	$query = sprintf('REPLACE INTO dashboard (user_id, name, id, type, dt) VALUES (%s, "%s", %s, "%s", NOW())',
		$_SESSION['user_id'],
		$name,
		intval($_GET['id']),
		$_GET['type']
	); 
} elseif (array_key_exists('delete', $_GET)) {
	$query = sprintf('DELETE FROM dashboard WHERE user_id=%s AND id=%s AND type="%s"',
		$_SESSION['user_id'],
		intval($_GET['id']),
		$_GET['type']
	);
} else {
	header('Location: /');
}

$mydash = new myQuery($query);

?>