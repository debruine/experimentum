<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

function checkCookies() {
	return true;
}


$styles = array(
	'h2' => 'text-align: left; padding-left: 2em;',
	'#qtbutton' => 'width: 88px; height: 31px; display: block; margin: 1em auto;',
	'.ui-accordion-content' => 'max-width: 654px;',
	'h2.ui-accordion-header a' => 'margin-left: 1em;'
);

$title = loc('FAQ');
$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();

/****************************************************
 * FAQ menu
 ***************************************************/

?>

<div id="faq">

<h2><a href="#">Who runs this website?</a></h2>
<div>
    <p>This website runs studies for the <a href="http://psysciacc.org">Psychological Science Accelerator</a>.</p>
    <p>It is hosted by the University of Glasgow School of Psychology and Institute of Neuroscience and Psychology.</p>
</div>

<h2><a href="#">How do you use cookies?</a></h2>
<div>
<p>We save one &ldquo;cookie&rdquo; to your browser when you visit our site. This 
    tracks your session; we need it so you don't have to log in on every single 
    page. It is only ever used on our website and only contains a session number 
    that our server uses to confirm your login. We are committed to using cookies 
    fairly and in accordance with your privacy rights.</p>
</div>

<h2><a href="#">Can I change my password or information?</a></h2>
<div>
<p>Yes. Just click on <a href="/my">My Account</a>. You can change your username 
    to any unused name. This will not affect the experiments you&#39;ve already done. 
    The form allows you to change your password. You can also change your birthdate 
    or sex if you entered them incorrectly when registering. Having accurate 
    information about your age and sex are very important to our scientific research.</p>
</div>


<h2><a href="#">Can I retrieve my password if I forgot it?</a></h2>
<div>	
<p>No. We protect user anonymity, so we do not collect email or IP addresses. Therefore,
    there is no way to securely retrieve your account details. If you are participating 
    in a long-term study, please contact the experimenter. Otherwise, you can just 
    make a new account.</p>
</div>

<h2><a href="#">Where do you get your great icons?</a></h2>
<div>
<p>We use the free version of <a href="https://linearicons.com/">Linearicons</a>
    by <a href="https://perxis.com">Perxis</a>, which is licensed under the 
    CC BY-SA 4.0 license.</p>
</div>


<h2><a href="#">Do I need a particular browser to view your website?</a></h2>
<div>
<p>We have worked hard to make sure that this website is accessible to 
    all people using visual browsers (we are working on better support for 
    audio-only browsers to access the parts of this website that do not 
    require the viewing of images). If the website looks strange in your 
    favourite browser, please e-mail us and let us know. If you are looking for 
    a new web browser, <a href="http://www.getfirefox.com/">FireFox</a> is an 
    excellent free browser for Windows, Mac OS X and Linux.</p>
</div>


<h2><a href="#">Why do I need JavaScript enabled to do experiments and demos?</a></h2>
<div>
<p>We use JavaScript to perform some calculations needed to randomise trial order 
    which prevents you having to load a new web page for every trial and greatly 
    speeds up the experiments. It also allows us to collect more accurate data on 
    how long the experiments take.</p>
</div>


<h2><a href="#">Can you host my experiment?</a></h2>
<div>
<p>Sorry, we cannot host your experiments unless you are a member of the 
    Psychological Science Accelerator. However, the code used to run 
    this website is open source at 
    <a href="https://github.com/debruine/experimentum">GitHub</a>.</p>
</div>


<h2><a href="#">What do we do with your data?</a></h2>
<div>
<p>All of your responses are confidential and anonymous. Your data are 
    stored on a secure server and this website will never ask for identifying data, 
    such as your email address, in a way that can link it to your user data. If you 
    have any concerns about the security or the use of your data, do not hesitate 
    to contact us.</p>
</div>

</div> <!-- END OF FAQ -->


<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script>
	
	$(function() {
		$('#faq').accordion({ 
			autoHeight: false,
			active: false
		});
	});
	
</script>

<?php

$page->displayFooter();

?>