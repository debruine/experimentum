<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin'), "/res/");

$clean = my_clean($_GET);
if (empty($clean)) $clean = array();

$title = array(
	"/res/" => loc("Researchers"),
	"/res/admin/" => loc("Admin"),
	"/res/admin/merge" => loc("Merge Users")
);
$styles = array(".samedata" => "text-align: center;");


$page = new page($title);
$page->set_logo(true);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

if (($clean['username1'] && $clean['username2']) || ($clean['userid1'] && $clean['userid2'])) {

	if (!empty($clean['username1']) && !empty($clean['username2'])) {
		$q = new myQuery("SELECT user_id FROM user WHERE username='$clean[username1]'");
		$user1 = $q->get_one();
		$q = new myQuery("SELECT user_id FROM user WHERE username='$clean[username2]'");
		$user2 = $q->get_one();
	} else {
		$q = new myQuery("SELECT username FROM user WHERE user_id='$clean[userid1]'");
		$clean['username1'] = $q->get_one();
		$q = new myQuery("SELECT username FROM user WHERE user_id='$clean[userid2]'");
		$clean['username2']= $q->get_one();
		$user1 = intval($clean['userid1']);
		$user2 = intval($clean['userid2']);
	}
	
	if (empty($user1) && is_numeric($clean['username1'])) { 
		$user1 = $clean['username1']; 
		$q = new myQuery("SELECT username FROM user WHERE user_id='$user1'");
		$clean[username1] = $q->get_one();
	}
	if (empty($user2) && is_numeric($clean['username2'])) { 
		$user2 = $clean['username2']; 
		$q = new myQuery("SELECT username FROM user WHERE user_id='$user2'");
		$clean[username2] = $q->get_one();
	}
	
	if ($user1 != $user2 && $user1 > 2 && $user2 > 2) {
		$q = new myQuery("SELECT * FROM user WHERE user_id='$user1'");
		$userdata[0] = $q->get_assoc();
		
		$q = new myQuery("SELECT * FROM user WHERE user_id='$user2'");
		$userdata[1] = $q->get_assoc();
		
		$samedata = array(
			"sex",
			"birthday",
			"status",
			"code",
			"password"
		);
		
		$getdata = sprintf("&amp;username1=%s&amp;username2=%s%s%s%s%s%s",
			$user1,
			$user2,
			$clean['sex'] ? "&amp;sex=$clean[sex]" : "",
			$clean['birthday'] ? "&amp;birthday=$clean[birthday]" : "",
			$clean['status'] ? "&amp;status=$clean[status]" : "",
			$clean['code'] ? "&amp;code=$clean[code]" : "",
			$clean['password'] ? "&amp;password=$clean[password]" : ""
		);
		
		foreach ($samedata as $col) {
			if ($userdata[0][$col] != $userdata[1][$col] && empty($clean[$col])) {
				echo "<h2><strong>$col</strong> are not the same, which one do you want to keep?</h2>\n";
				echo "<ul class='samedata'>\n";
				echo sprintf("	<li><a href='merge?%s=%s%s'>%s - %s</a></li>\n",
					$col,
					$userdata[0][$col],
					$getdata,
					$clean['username1'],
					$userdata[0][$col]
				);
				echo sprintf("	<li><a href='merge?%s=%s%s'>%s - %s</a></li>\n",
					$col,
					$userdata[1][$col],
					$getdata,
					$clean['username2'],
					$userdata[1][$col]
				);
				echo "</ul>\n\n";
				
				$page->displayFooter();
				exit;
			} 
		}
		
		if (array_key_exists("merge", $_GET)) {
			// set user1 to the chosen sex and birthday
			$query = sprintf("UPDATE user SET sex=%s, birthday=%s, status=%s, code=%s, password=%s WHERE user_id=%s",
				$clean['sex'] ? "'" . $clean['sex'] . "'" : "sex",
				$clean['birthday'] ? "'" . $clean['birthday'] . "'" : "birthday",
				$clean['status'] ? "'" . $clean['status'] . "'" : "status",
				$clean['code'] ? "'" . $clean['code'] . "'" : "code",
				$clean['password'] ? "'" . $clean['password'] . "'" : "password",
				$user1
			);
			$q = new myQuery($query);
			
			// change all experiments user2 to user1
			$q = new myQuery("SHOW tables WHERE Tables_in_exp != 'user'");
			$tables = $q->get_assoc();
			$affected_tables = array();
			foreach ($tables as $t) {
				$table = $t['Tables_in_exp'];
				// update tables if user_id column exists
				$q2 = new myQuery("SHOW COLUMNS FROM $table WHERE Field='user_id'");

				if ($q2->get_num_rows() == 1 && $table != "user") {
					$q3 = new myQuery("UPDATE $table SET user_id=$user1 WHERE user_id=$user2");
					if ($q3->get_affected_rows() > 0 ) $affected_tables[] = $table;
				}
			}
			
			// set username2 to unused
			$q = new myQuery("DELETE FROM user WHERE user_id=$user2");
		
			echo sprintf("<h2>User %s merged into user %s (%s)</h2>", 
				$clean['username2'],
				$clean['username1'],
				$user1
			);
			
			echo "<h3>" . count($affected_tables) . " tables affected</h3>\n";
			if (count($affected_tables) > 0) echo htmlArray($affected_tables);
		} else {
			echo sprintf("<p>Are you sure you want to merge %s (%s) into %s (%s)? ", 
				$clean['username2'],
				$user2,
				$clean['username1'],
				$user1
			); 
			
			echo "<input type='button' class='inline' onclick=\"window.location.href='merge';\" value='Cancel'>\n";
			echo "<input type='button' class='inline' onclick=\"window.location.href='merge?merge$getdata';\" value='Merge'>\n";		
		}
	} else {
		echo sprintf("<h2>Unable to merge %s and %s</h2>", $clean['username1'], $clean['username2']);
		if (!($user1 > 2)) echo "<h3>$clean[username1] does not exist</h3>\n";
		if (!($user2 > 2)) echo "<h3>$clean[username2] does not exist</h3>\n";
	}
} else  {
?>

<h2>Merge User Data</h2>
<h3>Data from username 2 will be attributed to username 1 and username 2 will be deleted</h3>

<form action='' method='get'>
<input type='hidden' name='user_id' value='<?= $clean['user_id'] ?>' />

<table class='smallform'>
	<tr><td>Username 1 (keep)</td><td><input type="text" name="username1" /></td></tr>
	<tr><td>Username 2 (delete)</td><td><input type="text" name="username2" /></td></tr>
</table>

<h1>or</h1>

<table class='smallform'>
	<tr><td>User ID 1 (keep)</td><td><input type="text" name="userid1" /></td></tr>
	<tr><td>User ID 2 (delete)</td><td><input type="text" name="userid2" /></td></tr>
</table>

<input type='submit' class='submit' value='Merge' />

</div>
</form>

<?php

}


$page->displayFooter();

?>