<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(0);

// set up experiment
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/exp.php';

$exp_id=$_GET['id'];

$expC = new expChooser($exp_id);

if (!$expC->check_exists()) { header('Location: /'); exit; }

if (!$expC->check_eligible()) { 
	if (in_array($_SESSION['status'], $RES_STATUS)) {
		$ineligible = "<p class='warning'>You would not be able to see this study because of your age or sex if you were a non-researcher.</p>";
	} else {
		header('Location: /fb?ineligible&type=exp&id=' . $exp_id); exit;
	}
}

/****************************************************/
/* !Display Page */
/***************************************************/

$title = array();

$styles = array(
);

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

$exp = $expC->get_exp();
echo $exp->get_experiment();

echo $ineligible;

?>

<script>
    // prevent back button
    history.pushState(null, null, location.href); 
    history.back(); 
    history.forward(); 
    window.onpopstate = function () { history.go(1); };
    
    function maxstimsize() {
        var w = $(window).width();
        var h = $(window).height();
        var qdiv = $('#question').outerHeight();
        var ii = $('tr.input_interface').outerHeight();
        var ft = $('#footer').outerHeight();
        
        var maxheight = h-qdiv-ft-ii-30;
        
        $('video, tr.exp_images img').css('max-height', maxheight);
    }
    
    window.onresize = maxstimsize;
    
    maxstimsize();
</script>

<?php

$page->displayFooter();

?>