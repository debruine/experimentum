<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin', 'res'), "/res/");

if (array_key_exists('add', $_GET)) {
	$types = array('exp','quest','sets','project');
	if (validID($_GET['user_id']) && validID($_GET['id']) && in_array($_GET['type'], $types)) {
	
		$query = sprintf('REPLACE INTO access (user_id, id, type) VALUES (%d, %d, "%s")',
			$_GET['user_id'],
			$_GET['id'],
			$_GET['type']
		);

		$q = new myQuery($query);
		
		if ($q->get_affected_rows() == 1) {
			echo 'added';
		} else if ($q->get_affected_rows() == 2) {
			echo 'duplicate';
		} else {
			echo $query;
		}
	} else {
		print_r($_GET);
	}
			
	exit;
}

if (array_key_exists('delete', $_GET)) {
	$types = array('exp','quest','sets','project');
	if (validID($_GET['user_id']) && validID($_GET['id']) && in_array($_GET['type'], $types)) {
	
		$query = sprintf('DELETE FROM access WHERE user_id=%d AND id=%d AND type="%s"',
			$_GET['user_id'],
			$_GET['id'],
			$_GET['type']
		);

		$q = new myQuery($query);
		
		if ($q->get_affected_rows() == 1) {
			echo 'deleted';
		} else {
			echo $query;
		}
	} else {
		print_r($_GET);
	}
	
	exit;
}

$styles = array(
	'.lists' => 'float: left; max-width: 60%; margin-right: 1em;',
	'#ress ul, #access ul' => 'overflow:auto;',
	'.lists li' => 'border: 1px solid transparent;',
	'li.selected' => 'background-color: hsl(60, 100%, 90%);',
	'.lists li.acceptDrop' => 'border: 1px solid red;',
	'#mytrash' => 'width: 60px; height: 80px; background: transparent center center no-repeat url(/images/finder/trash); display: none;',
	'#mytrash.acceptTrash' => 'background-image: url(/images/finder/trash);'
);

$title = array(
	'/res/' => loc('Researchers'),
	'/res/admin/' => loc('Admin'),
	'' => loc('Access')
);

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

$q = new myQuery('SELECT user_id, CONCAT(lastname, ", ", firstname) as name, status, COUNT(access.user_id) as c 
FROM res LEFT JOIN user USING (user_id) 
LEFT JOIN access USING (user_id)
GROUP BY user_id ORDER BY c DESC');
$ress = $q->get_assoc();
?>

<h2 id="active_item"></h2>

<span class="lists">
	<span id="reset">Reset</span>
	<div id="mytrash"></div>
</span>

<span class="lists" id="ress">
	<input id="res_search" placeholder="ress" />
	<ul>

<?php

foreach ($ress as $res) {
	echo sprintf('		<li userid="%d" class="%s">%s (<span class="n">%d</span>)</li>' . ENDLINE,
		$res['user_id'],
		$res['status'],
		$res['name'],
		$res['c']
	);
}

?>

	</ul>
</span>

<span class="lists" id="access">
	<input id="access_search" placeholder="items" />
	<ul>
	
<?php

$q = new myQuery('CREATE TEMPORARY TABLE tmp_access SELECT a.type,
	a.id, GROUP_CONCAT(a.user_id SEPARATOR ":") as users,
	GROUP_CONCAT(CONCAT(lastname, " ", firstname) SEPARATOR ", ") as usernames, 
	res_name as name,
	create_date as cd
FROM exp
LEFT JOIN access AS a USING (id)
LEFT JOIN res AS r ON (a.user_id = r.user_id)
WHERE a.type = "exp"
GROUP BY exp.id;

INSERT INTO tmp_access SELECT a.type,
	a.id, GROUP_CONCAT(a.user_id SEPARATOR ":") as users,
	GROUP_CONCAT(CONCAT(lastname, " ", firstname) SEPARATOR ", ") as usernames, 
	res_name as name,
	create_date as cd
FROM quest
LEFT JOIN access AS a USING (id)
LEFT JOIN res AS r ON (a.user_id = r.user_id)
WHERE a.type = "quest"
GROUP BY quest.id;

INSERT INTO tmp_access SELECT a.type,
	a.id, GROUP_CONCAT(a.user_id SEPARATOR ":") as users,
	GROUP_CONCAT(CONCAT(lastname, " ", firstname) SEPARATOR ", ") as usernames, 
	res_name as name,
	create_date as cd
FROM sets
LEFT JOIN access AS a USING (id)
LEFT JOIN res AS r ON (a.user_id = r.user_id)
WHERE a.type = "sets"
GROUP BY sets.id;

INSERT INTO tmp_access SELECT a.type,
	a.id, GROUP_CONCAT(a.user_id SEPARATOR ":") as users,
	GROUP_CONCAT(CONCAT(lastname, " ", firstname) SEPARATOR ", ") as usernames, 
	res_name as name,
	create_date as cd
FROM project
LEFT JOIN access AS a USING (id)
LEFT JOIN res AS r ON (a.user_id = r.user_id)
WHERE a.type = "project"
GROUP BY project.id;

SELECT * FROM tmp_access ORDER BY IF(users="",0,1), cd DESC;', true);


$access = $q->get_assoc();


foreach ($access as $a) {
	echo sprintf('<li users=";%s;" theType="%s" theID="%s" class="%s">%s_%d: %s [%s]</li>' . ENDLINE,
		$a['users'],
		$a['type'],
		$a['id'],
		$a['type'],
		$a['type'],
		$a['id'],
		$a['name'],
		$a['usernames']
	);
}

?>

	</ul>
</span>

<div id="help" title="Help Managing Access">
	<ul>
		<li>Use the search bars to search for a researcher and/or an item.</li>
		<li>Drag a researcher to an item or an item to a researcher to add access.</li>
		<li>Click on a researcher to view the items they have access to.</li>
		<li>Click on an item to view the researchers who have access to that item.</li>
		<li>Drag a researcher or an item to the trash to disassociate it from the selected item or researcher.</li>
	</ul>
</div>

<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script>

$(function() {
	
	$('#reset').button().click( function() {
		location.reload();
		/*
		$('#access, #ress').show();
		$('#access_search, #res_search').val('');
		stripe('.lists ul');
		$('#mytrash').hide();
		$('#active_item').text('');
		*/
	});

	stripe('.lists ul');

	$('#res_search').keyup( function() { narrowTable('#ress ul', this.value); } );
	$('#access_search').keyup( function() { narrowTable('#access ul', this.value); } );
	
	$('#ress li').click( function() {
		$('#ress').hide();
		$('#access').show();
		$('#access_search').val('');
		$('#active_item').html($(this).html());
		
		$('#access li').hide();
		var userid = $(this).attr('userid');
		$('#access li[users*=";'+userid+';"]').show();
		stripe('.lists ul');
		$('#mytrash').show().attr('userid', userid).removeAttr('theID'). removeAttr('theType');
	}).draggable({
		helper: 'clone',
	}).droppable({
		hoverClass: 'acceptDrop',
		tolerance: 'pointer',
		drop: function(e, ui) { 
			var type = ui.draggable.attr('theType');
			var id = ui.draggable.attr('theID');
			var userid = $(this).attr('userid');
			var user = $(this);
			
			$.get('access?add&type='+type+'&user_id='+userid+'&id='+id, function(data) {
				if (data == 'added') { 
					// increment user's number of items
					var oldN = user.find('.n').text();
					var newN = parseInt(oldN) + 1;
					user.find('.n').text(newN);
					
					// add user_id to item
					var users = ui.draggable.attr('users');
					ui.draggable.attr('users', users + user.attr('userid') + ';');
				} else if (data == 'duplicate') {
					// do nothing
				} else {
					$('<div />').html(data).dialog(); 
				}
				stripe('.lists ul');
			});
		}
	});	
	
	$('#access li').click( function() {
		$('#access').hide();
		$('#ress').show();
		$('#res_search').val('');
		$('#active_item').html($(this).html());
		
		$('#ress li').hide();
		var users = $(this).attr('users').split(':');
		$.each(users, function(i,v) {
			$('#ress li[userid="'+v+'"]').show();
		});
		stripe('.lists ul');
		$('#mytrash').show().attr('theType', $(this).attr('theType')).attr('theID', $(this).attr('theID')). removeAttr('userid');
	}).draggable({
		helper: 'clone',
	}).droppable({
		hoverClass: 'acceptDrop',
		tolerance: 'pointer',
		drop: function(e, ui) { 
			var type = $(this).attr('theType');
			var id = $(this).attr('theID');
			var userid = ui.draggable.attr('userid');
			var item = $(this);
			
			$.get('access?add&type='+type+'&user_id='+userid+'&id='+id, function(data) {
				if (data == 'added') { 
					// increment user's number of items
					var oldN = ui.draggable.find('.n').text();
					var newN = parseInt(oldN) + 1;
					ui.draggable.find('.n').text(newN);
					
					// add user_id to item
					var users = item.attr('users');
					item.attr('users', users + ui.draggable.attr('userid') + ':');
				} else if (data == 'duplicate') {
					// do nothing
				} else {
					$('<div />').html(data).dialog(); 
				}
				stripe('.lists ul');
			});
		}
	});
	
	$('#mytrash').droppable({
		//scope: '#access li, #res li',
		hoverClass: 'acceptTrash',
		tolerance: 'touch',
		drop: function(e, ui) { 
			var type = $(this).attr('theType') ? $(this).attr('theType') : ui.draggable.attr('theType');
			var id = $(this).attr('theID') ? $(this).attr('theID') : ui.draggable.attr('theID');
			var userid = $(this).attr('userid') ? $(this).attr('userid') : ui.draggable.attr('userid');
			
			$.get('access?delete&type='+type+'&user_id='+userid+'&id='+id, function(data) {
				if (data == 'deleted') { 
					var oldN = $('#active_item').find('.n').text();
					var newN = parseInt(oldN) - 1;
					$('#active_item').find('.n').text(newN);
					ui.draggable.hide();
				} else {
					$('<div />').html(data).dialog(); 
				}
				stripe('.lists ul');
			});
		}
	});		
		
	sizeToViewport();
	
	window.onresize = sizeToViewport;
});


function sizeToViewport() {
	var ul_height = $(window).height() - $('#ress ul').offset().top - $('#footer').height()-40;
	$('#maincontent ul').height(ul_height);
}
	
</script>

<?php

$page->displayFooter();

?>