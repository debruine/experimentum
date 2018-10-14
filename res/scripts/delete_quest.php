<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(4);

if (validID($_GET['id'])) {
	$id = $_GET['id'];

	$query = new myQuery('SELECT COUNT(*) as c FROM quest_' . $id  . ' LEFT JOIN user USING (user_id) WHERE status>1 AND status<6');
	$non_researchers = $query->get_one();
	
	if ($non_researchers == 0) {
		// no non-researchers have completed the experiment, so OK to delete
		$query = new myQuery('DELETE FROM quest WHERE id=' . $id);
		$query = new myQuery('DELETE FROM question WHERE quest_id=' . $id);
		$query = new myQuery('DELETE FROM options WHERE quest_id=' . $id);
		$query = new myQuery('DELETE FROM radiorow_options WHERE quest_id=' . $id);
		$query = new myQuery('DROP TABLE IF EXISTS quest_' . $id);
		$query = new myQuery('DELETE FROM dashboard WHERE type="quest" AND id=' . $id);
		$query = new myQuery('DELETE FROM access WHERE type="quest" AND id=' . $id);
		$query = new myQuery('DELETE FROM set_items WHERE item_type="quest" AND item_id=' . $id);
		
		echo "deleted";
	} else {
		// there is data from non-researchers
		echo "There is data from $non_researchers non-researchers, so this questionnaire has not been deleted.";
	}
} else {
	echo "ID $id is not recognised.";
}

exit;

?>