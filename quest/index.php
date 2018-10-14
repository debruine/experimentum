<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';	

/****************************************************
 * Set up questionnairet list
 ***************************************************/

//if ($_SESSION['user_id'] > 0 ) { 
	// exclusions
	$exclusions = array();
	if ($_SESSION['age'] > 0) {
		$exclusions[] = '(lower_age <= ' . ($_SESSION['age']) . ' OR lower_age IS NULL)';
		$exclusions[] = '(upper_age >= ' . ($_SESSION['age']) . ' OR upper_age IS NULL)';
	} else {
		// only show experiment with no age limits for people without an age
		$exclusions[] = 'lower_age IS NULL';
		$exclusions[] = 'upper_age IS NULL';
	}
	
	if ($_SESSION['sex'] == 'male') $exclusions[] = '(sex!="female")';
	if ($_SESSION['sex'] == 'female') $exclusions[] = '(sex!="male")';
	
	$excList = implode(' AND ', $exclusions);
	
	// get data from questionnaires where user is not excluded due to age, sex
	$q = new myQuery('SELECT id, quest.name
						FROM quest
						WHERE status="active" AND ' . $excList . ' 
						GROUP BY quest.id
						ORDER BY create_date DESC');
	$q->execute_query();
	$qList = $q->get_assoc();
	
	//echo $q->get_query(); exit;
	
	$expList  = '<table class="expTable query sortable">' . ENDLINE;
	$expList .= '	<thead><tr>' . ENDLINE;
	$expList .= tag('Questionnaire Name', 'th');
	$expList .= tag('Median Length', 'th');
	$expList .= tag('Participants', 'th');
	$expList .= '	</tr></thead><tbody>' . ENDLINE;
	
	$idList = array();
	foreach ($qList as $qL) {		
		$idList[] = $qL['id'];
		
		$expList .= sprintf('	<tr id="row_%d">
		<td><a class="explink" href="q?id=%d">%s</a></td>
		<td id="time_%d">...</td>
		<td id="n_%d">...</td>
	</tr>'. ENDLINE,
			$qL['id'],
			$qL['id'],
			$qL['name'],
			$qL['id'],
			$qL['id']
		);
	}
	$expList .= '</tbody></table>' . ENDLINE;
//}

/****************************************************/
/* !Display Page */
/***************************************************/

$title = loc('Questionnaires');
$page = new page($title);
$page->set_logo(true);
$page->set_menu(true);

$page->displayHead();
$page->displayBody();

if (empty($_SESSION['user_id'])) {
	echo tag('Please <a href="javascript: startLogin();">login or sign up</a> to participate in the questionnaires.');
}

echo $expList;

?>

<script src="/include/js/sorttable.js"></script>
<script>

	$j(function() {
	
		<?php if (empty($_SESSION['user_id'])) { ?>
		
		$j('a.explink').each( function() {
			$j(this).replaceWith($j(this).text());
		});
		
		<?php } ?>
		
		allIDs = [ <?= implode(',', $idList) ?> ];
		
		$j.each(allIDs, function() {
			var timerow = '#time_' + this;
			var nrow = '#n_' +  this;
			var done = '#done_' + this;
			var therow = '#row_' + this;
			
			$j(timerow).html('...').load('/include/scripts/check?quest&id=' + this, function(responseText){  
				if ('nodisplay' == responseText) {
					$j(therow).hide();
				} else {
					var parsedResponse = responseText.split(";");
					$j(timerow).html(parsedResponse[1] + ' minutes');
					$j(nrow).html(parsedResponse[2]);
					if (1 == parsedResponse[0]) {
						$j(therow).addClass('done');
					}
				}
        	});
		});
	});
	
</script>

<?php

$page->displayFooter();

?>