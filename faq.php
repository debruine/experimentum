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
<p>The University of Glasgow School of Psychology and Institute of Neuroscience and Psychology.</p>
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
    The form allows you to change your password or your password retrieval question 
    and answer. You can also change your birthdate or sex if you entered them 
    incorrectly when registering. Having accurate information about your age and sex 
    are very important to our scientific research.</p>
</div>


<h2><a href="#">Can I retrieve my password if I forgot it?</a></h2>
<div>	
<p>Yes. Just fill out this <a href="password.php">password retrieval form</a>. 
    You will have to correctly answer your password retrieval question.</p>
<p>If you have not yet set your password retrieval question and answer, you can 
    do so at <a href="/my">My Account</a> if you are logged in. Unfortunately, 
    this won&#39;t help if you can&#39;t remember your password. You can e-mail 
    us with your username and IP address and we can see what we can do. Otherwise, 
    you may just have to register using a different username.</p>
</div>

<h2><a href="#">Where do you get your great icons?</a></h2>
<div>
<p>We use the free version of <a href="https://linearicons.com/">Linearicons</a>
    by <a href="https://perxis.com">Perxis</a>, which is licensed under the 
    CC BY-SA 4.0 license.</p>
</div>



<h2><a href="#">Do I need a particular browser to view your website?</a></h2>
<div>
<p>Some people are reporting problems with questionnaires on some versions of 
    Chrome for Windows. We are working to fix this, but you should be able to 
    access the studies with FireFox in the meantime.</p>
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
    University of Glasgow School of Psychology. However, the code used to run 
    this website is open source at 
    <a href="https://github.com/debruine/experimentum">GitHub</a>.</p>
</div>


<h2><a href="#">What do we do with your data?</a></h2>
<div>
<p>All of your responses are confidential and anonymous. Although we will 
    present average results for our tests, we will never make the answers of a 
    single person public in a way where they can be identified. Your data are 
    stored on a secure server and we will never ask for identifying data, such 
    as your email address, in a way that can link it to your user data. If you 
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