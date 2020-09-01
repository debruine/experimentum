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
    <a href="https://teams.microsoft.com/l/team/19%3a173a62977b2445529504bc4a5128dca4%40thread.tacv2/conversations?groupId=38922d94-16b9-42ca-97a4-ee9d04a56d6a&tenantId=6e725c29-763a-4f50-81f2-2e254f0133c8" target="_blank">Experimentum Teams forum</a>. 
    Any email address ending in @glasgow.ac.uk or @student.gla.ac.uk can sign up.</p>
    
    <p><a href="/docs/">Manual</a> by Rebecca Lai, Rifah Abdullah, and Gaby Mahrholz.</p>

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