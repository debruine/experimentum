<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(1);

// set up experiment
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/exp.php';

$exp_id=$_GET['id'];
$version=$_GET['v'];

$exp = new experiment($exp_id, $version);

if (!$exp->check_exists()) { header('Location: /'); exit; }

$query = new myQuery("SELECT trial_n, exposure,
		lefti.path as left_img,
		centeri.path as center_img,
		righti.path as right_img
		FROM adapt_trial 
		LEFT JOIN stimuli AS lefti ON adapt_trial.left_img=lefti.id
		LEFT JOIN stimuli AS centeri ON adapt_trial.center_img=centeri.id
		LEFT JOIN stimuli AS righti ON adapt_trial.right_img=righti.id
		WHERE exp_id=$exp_id AND version='$version' ORDER BY RAND()");
$myimagelist = $query->get_assoc();
		
$q = new myQuery("SELECT question FROM versions WHERE exp_id=$exp_id AND version=$version");
$version_question = $q->get_one();
	

/****************************************************/
/* !Display Page */
/***************************************************/

$title = array();

$styles = array(
	'#question' => 'text-align: center;'
);

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script>
	$(function() {
	
<?php
	$waittime = 0;
	foreach($myimagelist as $myimages) {
		if (!empty($myimages['left_img'])) {
			echo sprintf("		setTimeout(\"\$('#left_img').attr('src', '%s');\", %d);\n", 
				$myimages['left_img'], $waittime);
			$left_img_count++;
		}
		if (!empty($myimages['center_img'])) {
			echo sprintf("		setTimeout(\"\$('#center_img').attr('src', '%s');\", %d);\n", 
				$myimages['center_img'], $waittime);
			$center_img_count++;
		}
		if (!empty($myimages['right_img'])) {
			echo sprintf("		setTimeout(\"\$('#right_img').attr('src', '%s');\", %d);\n", 
				$myimages['right_img'], $waittime);
			$right_img_count++;
		}
		$waittime += $myimages['exposure'];
	}
	$testurl = sprintf("/exp?go&id=%d&v=%d", $exp_id, $version);
	echo "	setTimeout(\"window.location.href = '{$testurl}';\", $waittime);\n";
	
	
?>
	});
</script>

<div id='question'><?= loc($version_question) ?></div>
<table class='slideshow'>
	<tr>

<?php

	if ($left_img_count>0) 
		echo "		<td><img id='left_img' src='' /></td>\n";
	if ($center_img_count>0) 
		echo "		<td><img id='center_img' src='' /></td>\n";
	if ($right_img_count>0) 
		echo "		<td><img id='right_img' src='' /></td>\n";
?>
	
	</tr>
</table>

<?php 

$page->displayFooter();

?>