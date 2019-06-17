<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

$title = array(
    '/res/' => 'Researchers',
    '/res/tutorial/' => 'Tutorials'
);

/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();

?>

<p>Help and announcements are on the 
    <a href="https://experimentum-web.slack.com/signup" target="_blank">Experimentum slack forum</a>. 
    Any email address ending in @glasgow.ac.uk or @student.gla.ac.uk can sign up.</p>

<p>You can make new experiments or questionnaires at the 
    <a href="/res/exp/">Experiment</a>  or <a href="/res/quest/">Questionnaire</a> lists above. 
    Chain them together by making new sets at the <a href="/res/set/builder">Set Builder</a>.
    Make a project page with the <a href="/res/project/builder">Project Builder</a> 
    so you can direct participants to your project with a custom URL. 
    Browse our <a href="/res/stimuli/browse">open-access stimuli</a> or 
    <a href="/res/stimuli/upload">upload your own stimuli</a>.
</p>


<h3>Video Tutorials (under construction)</h3>

<ul class="p">
    <li><a href="movies/mixed_questionnaire.m4v">Mixed Questionnaire</a></li>
</ul>

<?php

$page->displayFooter();

?>