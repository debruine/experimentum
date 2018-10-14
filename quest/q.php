<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(1);


/****************************************************
 * AJAX Responses
 ***************************************************/

if (array_key_exists('submit', $_GET)) {
	// save data and return nextpage
	$clean = my_clean($_POST);
	
	$questions = array();
	$answers = array();
	foreach ($clean as $q => $a) {
		if (substr($q, 0, 1) == 'q' && is_numeric(substr($q, 1))) {
			$questions[] = $q;
			$answers[] = '"' . $a . '"';
		}
	}
	
	// record data
	$q = sprintf('INSERT INTO quest_%d (%s starttime, endtime, user_id) VALUES (%s "%s", "%s", "%d")',
		$clean['quest_id'],
		(count($questions) > 0) ? implode(',', $questions) . ',' : '',
		(count($answers) > 0) ? implode(',', $answers) . ',' : '',
		$clean['starttime'],
		date('Y-m-d H:i:s'),
		$_SESSION['user_id']
	);
	$q = str_replace('"NULL"', 'NULL', $q);
	$query = new myQuery($q);
	
	// send to feedback page
	echo 'url;/feedback/fb?type=quest&id=' . $clean['quest_id'];

	exit;
}

/****************************************************
 * Display Questionnaire
 ***************************************************/


// set up questionnaire
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';

$quest_id=$_GET['id'];
$q = new questionnaire($quest_id);

if (!$q->check_exists()) {
	header('Location: /'); 
	exit;
}

if (!$q->check_eligible()) { 
	if ($_SESSION['status'] > 3) {
		$ineligible = "<p class='ui-state-error'>You would not be able to see this questionnaire because of your age or sex if you were a non-researcher.</p>";
	} else {
		header('Location: /feedback/fb?ineligible&type=quest&id=' . $quest_id); exit;
	}
}

$title = array(
	'/quest/' => 'Questionnaires',
	$q->get_name()
);

$styles = array(
	'#dialog-confirm' => 'display: none;'
);

$page = new page($title);
$page->set_logo(false);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

echo $ineligible;

$q->print_form();

?>

<div id='dialog-confirm'>
	<p>You have not answered some questions. Are you sure you want to submit?</p>
</div>

<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script>

	$j(function() {

		// cancel empty alert on change for select field
		$j('#qTable select, #qTable input, #qTable textarea').change( function() {
			if ($j(this).val() != "NULL") {
				$j(this).closest('#qTable > tbody > tr').removeClass('emptyAlert');
			}
		});
		
		/* !specific questionnaire functions */
		
		// partner Q (quest_7) hide partner-specific questions from people without a partner
		$j('#qTable #q20_1').click( function() {
			$j('#q21_row, #q22_row, #q23_row, #q24_row, #q25_row, #q26_row, #q27_row, #q28_row').show();
		});
		$j('#qTable #q20_0').click( function() {
			$j('#q21_row, #q22_row, #q23_row, #q24_row, #q25_row, #q26_row, #q27_row, #q28_row').hide();
		});
	
	});
	
	// form is submitted
	function submitQ(quest_id) {	
	
		// check for empty questions
		var fields = {};
		$j.each($j('#maincontent form').serializeArray(), function(index,value) {
			fields[value.name] = value.value;
		});
		
		var emptyFields = 0;
		// look through visible questionnaire rows (only rows that have id and not ranking rows) for empty variables
		$j('#qTable > tbody > tr[id]:not(.ranking):visible').each( function(i) {
			$j(this).removeClass('emptyAlert');
			var qid = $j(this).attr('id').replace('_row','');

			if (fields[qid] == '' || fields[qid] == null || fields[qid] == 'NULL') {
				$j(this).addClass('emptyAlert');
				emptyFields++;
			}
		});
		if (emptyFields > 0) {
			$j('#dialog-confirm').dialog({
				resizable: false,
				modal: true,
				title: "Missing Data",
				show: 'fade',
				buttons: {
					"Submit with missing info": function() {
						$j(this).dialog("close");
						
						recordAnswers();
					},
					"Go back to questionnaire": function() {
						$j(this).dialog("close");
					}
				}
			});	 
		} else {
			recordAnswers();
		}
	}
	
	function recordAnswers() {
		var theData = $j('#maincontent form').serialize();
		
		// record answers
		$j.ajax({
			type: 'POST',
			url: '/quest/q?submit',
			data: theData,
			success: function(response) {
				parsedResponse = response.split(';');
				if (parsedResponse[0] == 'url') {
					window.location.href=parsedResponse[1];
				} else {
					$j('<div />').html(response).dialog();
				}
			}
		});
	}

</script>

<?php

$page->displayFooter();

?>