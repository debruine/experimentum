<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();

?>


<p> Please test your projects. 
    Clicking Go from a project info page will now give you the option to log in as a test user.
    Email lisa.debruine@glasgow.ac.uk immediately if there are any problems with your studies.
    Check the Teams forum for explanations of changes.</p>


<h3>Changes</h3>
<ol>
    <li>Display of instructions, feedback, and stimuli updated in experiment and questionnaire info pages</li>
    <li>Added stats for nonbinary participants to project stats</li>
    <li>Download record on project, exp and quest info pages (see who downloaded the data when)</li>
    <li>Login as a test user (marked as test status in downloads)</li>
    <li>Debriefing feedback for projects is now available (you can move it out of the feedback for the full set or leave it there; project feedback will override final set feedback, which overrides final component feedback)</li>
    <li>You can no longer accidentally add icons to projects (drag them to components to show an icon on the project info page)</li>
</ol>
    
<?php

$page->displayFooter();

?>