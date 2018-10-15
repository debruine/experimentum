<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin'), "/res/");

$title = array(
	'/res/' => loc('Researchers'),
	'/res/admin/' => loc('Admin'),
	'' => 'Yoke'
);

$styles = array(
	"table.query input" => "max-width: 6em;",
	"textarea" => "height: 1.5em; width: 30em;"
);

if (array_key_exists('new_entries', $_POST)) {

	$e = trim($_POST['new_entries']);
	
	$rows = explode("\n", $e);
	
	$affected_rows = 0;
	
	foreach ($rows as $i => $r) {
		$cols = explode("\t", trim($r));
		
		$query[$i] = sprintf("REPLACE INTO yoke (user_id, type, id, self, other) VALUES (%d, '%s', %d, '%s', '%s')",
			intval($cols[0]),
			my_clean($cols[1]),
			intval($cols[2]),
			my_clean($cols[3]),
			my_clean($cols[4])
		);
		
		$q = new myQuery($query[$i]);
		
		if ($q->get_affected_rows() > 0) { $affected_rows++; }
	}
	
	//echo "<ol><li>" . implode("</li><li>", $query) . "</li></ol>";
	echo $affected_rows . " entries were updated. Reload the page to see updates.";
	exit;

}

$exp = empty($_GET['id']) ? 'IS NOT NULL' : 'IN(' . $_GET['id'] . ')';

$q = new myQuery("SELECT 
					#CONCAT('<button user_id=\"',user_id,'\" type=\"', type, '\" id=\"', yoke.id, '\">edit</button>') AS edit,
					user_id, 
					username,
					sex,
					CONCAT_WS(', ', 
								user.code,
								GROUP_CONCAT(DISTINCT code.code), 
								GROUP_CONCAT(DISTINCT CONCAT('oc_', q7117)) 
								,GROUP_CONCAT(DISTINCT CONCAT('hm_', q8068))
							 ) as codes,
					CONCAT(type, '_', yoke.id) as exp,
					self,
					other
					FROM yoke
					LEFT JOIN user USING (user_id)
					LEFT JOIN code USING (user_id)
					LEFT JOIN quest_298 as oc USING (user_id)
					LEFT JOIN quest_388 as hm USING (user_id)
					WHERE yoke.id {$exp}
					GROUP BY user_id, exp
					ORDER BY codes, exp");


/****************************************************/
/* !Display Page */
/***************************************************/


$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>
<!--

1	exp	1	lisa	ben
2	exp	1	ben	lisa
3	exp	1	ben	three
4	exp	1	ben	two
5	exp	1	ben	lisa	toomany

-->
<p style="text-align: right;">Search: <input id="search" type="text"/> </p>

<p>Paste new or replacement entries from Excel in the format:</p>

<table style="border-collapse: separate; border-spacing: 2px;"><thead><tr><td>user_id</td><td>type</td><td>id</td><td>self</td><td>other</td></tr></thead></table>

<p><textarea id="new_entries"></textarea> <button id="upload">Upload</button></p>

<?= $q->get_result_as_table() ?>

<script>

	$j('#search').keyup( function() { narrowTable('table.query tbody', this.value); } );
	
	$j('#upload').button().click( function() {
		var entries = $j('#new_entries').val().trim();
		$j('#new_entries').val('');
		
		if (entries == '') { return false; }
		
		var e_rows = entries.split("\n");
		var problems = '';
		var $table = $j("<table />");
		$table.append("<thead><tr><td>user_id</td><td>type</td><td>id</td><td>self</td><td>other</td></tr></thead>");
		
		$j.each(e_rows, function(i, r) {
			var e_cols = r.trim().split("\t");
			if (e_cols.length != 5) {
				problems += "Row " + (i+1) + " has " + e_cols.length + " columns<br/>";
			} else {
				var $tr = $j('<tr />');
				$j.each(e_cols, function(j,c) {
					$tr.append("<td>" + c + "</td>");
				});
				$table.append($tr);
			}
		});
		
		if (problems != '') {
			$j('<div />').dialog().html(problems);
		} else {
			$j('<div />').append($table).dialog({
				title: "Add " + e_rows.length + " New Entries?",
				modal: true,
				buttons: {
					Cancel: function() { $j(this).dialog("close"); },
					"Add": {
						text: 'Add',
						click: function() {
							$j(this).dialog("close");
							
							$j.ajax({
								type: 'POST',
								url: "/res/admin/yoke", 
								data: { new_entries: entries }, 
								success: function(data) {
									$j("<div />").append(data).dialog();
								}
							});
						}
					}
				}
			});
		}
	});
	
	$j('table.query button').button({text: false, icons: {primary: "ui-icon-pencil"}}).click( function() {
		// alert('Editing ' + $j(this).attr('user_id') + ' for ' + $j(this).attr('type') + '_' + $j(this).attr('id'));
		
		var $row = $j(this).closest('tr');
		var $self = $row.find('td:nth-last-child(2)');
		var $other = $row.find('td:last');
		
		if ($row.hasClass('editing')) { 
			$row.removeClass('editing');
			$j(this).button("option", "icons", {primary: "ui-icon-pencil"});
		} else {
			$row.addClass('editing');
			$j(this).button("option", "icons", {primary: "ui-icon-disk"});
			
			var origself = $self.text();
			var origother = $other.text();
			
			$self.html("<input type='text' name='self' value='" + origself + "' />");
			$other.html("<input type='text' name='self' value='" + origother + "' />");
		}

	});

</script>

<?php

$page->displayFooter();

?>