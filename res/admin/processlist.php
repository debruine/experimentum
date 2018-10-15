<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin','researcher'), "/res/");

if ($_GET['id'] > 0) {
	$q = new myQuery('KILL ' . intval($_GET['id']));
}


$styles = array(
);

$title = array(
	'/res/' => loc('Researchers'),
	'/res/admin/' => loc('Admin'),
	'' => loc('MySQL Processlist')
);

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>
	
<?php

// redo so that ownerless items are included
$q = new myQuery('SHOW PROCESSLIST');
$pl = $q->get_assoc();

echo "<table id='processlist'><thead>\n";
foreach ($pl[0] as $header => $v) {
	echo "	<th>$header</th>";
}
echo "</thead><tbody>\n";
foreach ($pl as $n => $p) {
	echo "	<tr>\n";
	foreach ($p as $h => $d) {
		if ($h == 'Id') {
			echo "		<td><a href='processlist?id=$d'>$d</a></td>\n";
		} else {
			echo "		<td>$d</td>\n";
		}
	}	
	echo "	</tr>\n";
}
echo "</tbody></table>";
?>


<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script>

$j(function() {
});

</script>

<?php

$page->displayFooter();

?>