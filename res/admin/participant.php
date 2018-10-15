<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('researcher','admin'), "/res/");

/****************************************************/
/* !Display Page */
/***************************************************/

$title = array(
	'/res/' => loc('Researchers'),
	'/res/admin/' => loc('Admin'),
	'' => loc('Participants')
);

$styles = array(
	'#graph_container' 	=> 	'width: 90%; 
							height: 500px; 
							margin: 1em auto;'
);

if (array_key_exists('tables', $_GET)) {

	// get list of all tables
	$query = new myQuery("SHOW TABLES WHERE LOCATE('exp_', Tables_in_exp) OR LOCATE('quest_', Tables_in_exp)");
	$all_tables = $query->get_assoc(false, 'Tables_in_exp', 'Tables_in_exp'); 

	$sections = array(
		"exp" => "Experiments",
		"quest" => "Questionnaires"
	);
	
	foreach($sections as $section => $sectname) {
		echo "<table class='sortable' id='participant_{$section}_table'>\n";
		echo "<tr><th>ID</th><th>$sectname</th><th>Count</th><th>Last Date</th></tr>\n\n";
		
		$query = new myQuery("SELECT id, res_name FROM $section ORDER BY id DESC");
		$rows = $query->get_assoc();
		
		foreach ($rows as $myrow) {
			if (in_array($section . "_" . $myrow['id'], $all_tables)) {
				$query = sprintf("SELECT count(*) as c, MAX(endtime) as maxdate FROM %s_%d WHERE user_id='%d' GROUP BY NULL", 
					$section, $myrow['id'], $_GET['user_id']
				);
				($result2 = @mysql_query($query, $db)) || myerror($query);
				
				if (@mysql_num_rows($result2) > 0) {
					$myrow2 = @mysql_fetch_assoc($result2);
					echo sprintf("<tr><td><a href='../%s/info?id=%d'>%s</a></td><td>%s</td><td>%s</td><td>%s</td><?tr>\n\n", 
						$section, $myrow['id'], $myrow['id'], $myrow['res_name'], $myrow2['c'], $myrow2['maxdate']
					);
				}
			}
		}
		
		echo "</table>\n\n";
	}
	
	exit;
}

if (array_key_exists('find', $_GET)) {
	$clean = ($_POST);
	if (empty($clean)) $clean = array();
	$status_list = array('test','guest','registered','student','researcher','admin');
    $mystatus = array_search($_SESSION['status'], $status_list);
		
	// get user data
	$id = ($_GET['id']) ? $_GET['id'] : $_POST['id'];
	
	if ($_POST['code'] && $_POST['code'] != $_POST['old_code']) {
		$query = "SELECT user.*, IF(status<={$mystatus}, LEFT(MD5(regdate),10), '') as p FROM user LEFT JOIN code USING (user_id) WHERE code.code='$_POST[code]'";
		$searched_on = "code = $_POST[code]";
	} else if ($_POST['username'] && $_POST['username'] != $_POST['old_username']) {
		if ($_POST['findcontaining'] == 'true') {
			$query = "SELECT user.*, IF(status<={$mystatus}, LEFT(MD5(regdate),10), '') as p FROM user WHERE LOCATE('$_POST[username]', username)";
			$searched_on = "username containing $_POST[username]";
		} else {
			$query = "SELECT user.*, IF(status<={$mystatus}, LEFT(MD5(regdate),10), '') as p FROM user WHERE username='$_POST[username]'";
			$searched_on = "username = $_POST[username]";
		}
	} else {
		$query = "SELECT user.*, IF(status<={$mystatus}, LEFT(MD5(regdate),10), '') as p FROM user WHERE user_id='$id'";
		$searched_on = "user_id = $id"; 
	}
	
	$q = new myQuery($query);
	
	if ($q->get_num_rows() == 0) { // change user form
		echo 'error:Could not find the user with ' . $searched_on;
	} elseif ($q->get_num_rows() > 1) { 
		$userlist = $q->get_assoc();
		$userlist['userlist'] = true;
		echo json_encode($userlist);
	} else {
	
		$userdata = $q->get_assoc(0);
		$query = new myQuery("SELECT * FROM login WHERE user_id='$userdata[user_id]' ORDER BY logintime DESC");
		$myrow = $query->get_assoc(0);
		$login_n = $query->get_num_rows();
		
		$userdata['login_n'] = $login_n;
		$userdata['logintime'] = $myrow['logintime'];
		$userdata['ip'] = $myrow['ip'];
		
		echo json_encode($userdata);
	}
	exit;
}

// reset password
if (array_key_exists("resetpswd", $_GET)) {
	$query = new myQuery("UPDATE user set password=MD5(username) WHERE user_id={$_POST[id]}");
	if ($query->get_affected_rows() > 0) {
		echo "Password changed to username";
	} else {
		echo 'Error--password not changed';
	}
	exit;
}

//change code
if (array_key_exists("changecode", $_GET)) {
	$query = new myQuery("UPDATE user set code='{$_POST[code]}' WHERE user_id={$_POST[id]}");
	if ($query->get_affected_rows() > 0) {
		echo "Code changed to {$_POST[code]}";
	} else {
		echo 'Error--code not changed';
	}
	exit;
}


/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);

$page->displayBody();

?>

<dl class='usersearch'>
	<dt><label for='username'>username</label>:</dt>	
		<dd>
			<input type='text' id='username' name='username' value='' maxlength='32' size='16' />
			<input type='checkbox' id='findcontaining' name='findcontaining' value='true' /> <label for='findcontaining'>containing</label>
		</dd>
	<dt><label for='id'>id</label>:</dt>				
		<dd><input type='text' id='id' name='id' value='' maxlength='8' size='8' /></dd>
	<dt><label for='code'>code</label>:</dt>			
		<dd><input type='text' id='code' name='code' value='' maxlength='16' size='8' /></dd>
</dl>
<div class="buttonset">
	<button id='finduser'>find user</button>
	<button id='resetpswd'>reset password</button>
	<button id='changecode'>change code</button>
	<?php if ($_SESSION['status'] >=8) { echo "<button id='changestatus'>change status</button>"; } ?>
</div>
	
<dl id="userinfo">
	<dt>Sex:</dt><dd id='sex'></dd>
	<dt>Birthdate:</dt><dd id='birthdate'></dd>
	<dt>Status:</dt><dd id='status'></dd>
	<dt>Autologin link:</dt><dd id='autologin'></dd>
</dl>

<button id="show_comp_tables">Show Completed Studies</button>
<div id="completed_tables">
	
</div>


<!--*************************************************
 * Javascripts for this page
 *************************************************-->

<script>

$j(function() {
	$j('#userinfo').hide();
	$j('.buttonset').buttonset();
	$j('#finduser').click( function() {
		if ($j('#finduser span').html() == 'reset search') {
			$j('#id,#code,#username').val('');
			$j('#userinfo,#show_comp_tables').hide();
			$j('#finduser span').html('find user');
			$j('#resetpswd,#changecode').button({disabled: true});
			$j('#completed_tables').html('');
			$j('#findcontaining').attr('checked', false);
			$j('#username, #findcontaining, label[for="findcontaining"]').show();
			$j('#userlist').remove();
		} else {
			$j.ajax({
				url: 'participant?find',
				data: $j('.usersearch input').serialize(),
				type: 'POST',
				dataType: 'json',
				success: function(data) {
					if (data.userlist) {
						var userlist = $j('<select id="userlist" />');
						
						var n = 0;
						$j.each(data, function(user) {
							if (user != 'userlist') {
								userlist.append('<option value="' + data[user].username + '">'+ data[user].username + '</option>');
								n++;
							}
						});
						userlist.prepend('<option value="" selected="selected">' + n + ' matching usernames found</option>');
						$j('#username').after(userlist);
						$j('#userlist').change( function() {
							$j('#username').val($j('#userlist').val());
							$j('#username, #findcontaining, label[for="findcontaining"]').show();
							$j('#finduser span').html('find user');
							$j('#finduser').click();
							$j('#userlist').remove();
						});
						$j('#findcontaining').attr('checked', false);
						$j('#finduser span').html('reset search');
						$j('#username, #findcontaining, label[for="findcontaining"]').hide();
					} else {
						$j('#id').val(data.user_id);
						$j('#code').val(data.code);
						$j('#username').val(data.username);
						$j('#sex').html(data.sex);
						$j('#birthdate').html(data.birthday);
						$j('#status').html(data.status);
						if (data.p == '') {
    						$j('#autologin').html('You do not have authorisation to get the autologin for ' + data.status + 's');
                        } else {
						    $j('#autologin').html('<a href="/include/scripts/login?u=' + data.user_id + '&p=' + data.p + '&url=/">http://faceresearch.org/include/scripts/login?u=' + data.user_id + '&p=' + data.p + '&url=/</a>');
						}
						$j('#userinfo').show();
						$j('#resetpswd').button({disabled: false});
						$j('#changecode').button({disabled: false});
						$j('#show_comp_tables').show();
						$j('#finduser span').html('reset search');
					}
				}
			});
		}
	});
	
	$j('#changestatus').click( function() {
    	alert('Function under development');
    });
	
	$j('#resetpswd').click( function() {
		$j.ajax({
			url: 'participant?resetpswd',
			data: $j('#id').serialize(),
			type: 'POST',
			success: function(data) {
				growl(data, 2000);
			}
		});
	}).button({disabled: true});
	
	$j('#changecode').click( function() {
		$j.ajax({
			url: 'participant?changecode',
			data: $j('.usersearch input').serialize(),
			type: 'POST',
			success: function(data) {
				growl(data, 2000);
			}
		});
	}).button({disabled: true});

	$j('#show_comp_tables').button().click(function() {
		$j('#completed_tables').html("<img src='/images/loaders/loading.gif' />").load('participant?tables&user_id=' + $j('#id').val(), function() {
			stripe('#completed_tables tbody');
			
			$j('#completed_tables table.sortable').each(function() {
				var t = $j(this).get(0);
				sorttable.makeSortable(t);
			});
			$j('#show_comp_tables').hide();
		});
	}).hide();
});

</script>

<script src="/include/js/sorttable.js"></script>

<?php

$page->displayFooter();

?>