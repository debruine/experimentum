<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin'), "/res/");

if (!empty($_POST['function'])) {

	if ($_POST['function'] == 'update_codes') {
		$q = new myQuery("REPLACE INTO code SELECT user_id, CONCAT('oc_',q7117) FROM quest_298 LEFT JOIN user USING (user_id) WHERE status>1 AND status<6;");
		$oc = $q->get_affected_rows();
		
		$q = new myQuery("REPLACE INTO code SELECT user_id, CONCAT('hm_',q8068) FROM quest_388 LEFT JOIN user USING (user_id) WHERE status>1 AND status<6;");
		$hm = $q->get_affected_rows();
		
		$q = new myQuery("REPLACE INTO code SELECT user_id, CONCAT('ks_',q8363) FROM quest_416 LEFT JOIN user USING (user_id) WHERE status>1 AND status<6;");
		$ks = $q->get_affected_rows();
		
		echo "KS Project: $ks entries<br>OC project: $oc entries<br>HM Project: $hm entries<br>";
		
		$q = new myQuery("SELECT user_id, username, sex, regdate, code.code 
							FROM code 
							LEFT JOIN user USING (user_id) 
							WHERE LEFT(code.code,2) IN ('oc','hm','ks') 
							ORDER BY code.code;");
		echo $q->get_result_as_table();
	} else {
		echo "The function " . $_POST['function'] . " does not exist.";
	}
	
	exit;
}


$title = array(
	"/res/" => loc("Researchers"),
	"/res/admin/" => loc("Admin"),
	"/res/admin/merge" => loc("Periodic Functions")
);
$styles = array();


$page = new page($title);
$page->set_logo(true);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<h2>Periodic Functions</h2>

<ul>
	<li><button id="update_codes">Update Code Table</button></li>
</ul>

<p id="feedback"></p>

<script>
	$j('#update_codes').button().click( function() {
		$j("#feedback").html('<img src="/images/loaders/loading.gif">');
	
		$j.post('functions.php', { 'function': 'update_codes' },  function(feedback) {
			$j("#feedback").html( feedback );
			stripe('tbody');
		});
	});
</script>

<?php


$page->displayFooter();

?>