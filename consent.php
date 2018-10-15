<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

$_SESSION['return_to'] = $_SERVER['HTTP_REFERER'];

$title = loc('Informed Consent');
$page = new page($title);
$page->set_menu(true);

$page->displayHead();
$page->displayBody();

?>

<p>The studies on our website are all ethically approved by the University of Glasgow Institute of Neuroscience and Psychology or the principal investigator's relevant institution.</p>

<p>Please read the &lsquo;Statement of Informed Consent&rsquo; below and indicate whether you consent.</p>

<h2>Statement of Informed Consent</h2>
<ol style='margin: 1em auto; width: 35em;'>
	<li>I understand the general purpose of the tests and questionnaires on this website.</li>
	<li>I understand that I can withdraw from a study at any time by simply closing my web browser.</li>
	<li>I understand that I may skip any questions that I am uncomfortable answering.</li>
	<li>I understand that my responses are anonymous.</li>
	<li>I agree to participate in the studies on this website.</li>
</ol>

<div class='buttons'>
	<a href='/register'>I Agree</a>
	<a href='/'>I Do Not Agree</a>
</div>

<?php

$page->displayFooter();

?>