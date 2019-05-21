<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

// clear sets so you don't get stuck
unset($_SESSION['set_list']);
unset($_SESSION['set_item_number']);
unset($_SESSION['project']);
unset($_SESSION['project_id']);
unset($_SESSION['session_id']);

/****************************************************/
/* !Display Page */
/***************************************************/   

$title = '';

$styles = array();

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();


?>

<p>This is an online platform for studies run by the 
    <a href="http://psysciacc.org" target="_blank">Psychological Science Accelerator</a>.</p>

<p>Try the Test PSA Study 1 in:</p>

<ul style="display: block; max-width: 40em; margin: 0 auto;">
    <li><a href="project?psa1_ZH-S_test">Chinese (Simplified) / 中文</a></li>
    <li><a href="project?psa1_NL_test">Dutch / Nederlands</a></li>
    <li><a href="project?psa1_ENG_test">English</a></li>
    <li><a href="project?psa1_FAS_test">Farsi / فارسی</a></li>
    <li><a href="project?psa1_FRE_test">French / Français</a></li>
    <li><a href="project?psa1_FR-BE_test">French (Belgian) / Français de Belgique</a></li>
    <li><a href="project?psa1_FR-CH_test">French (Swiss) / Français de Suisse</a></li>
    <li><a href="project?psa1_GER_test">German / Deutsch</a></li>
    <li><a href="project?psa1_EL_test">Greek / Ελληνικά</a></li>
    <li><a href="project?psa1_HU_test">Hungarian / magyarul</a></li>
    <li><a href="project?psa1_ITA_test">Italian / Italiano</a></li>
    <li><a href="project?psa1_NOR_test">Norwegian / Norsk</a></li>
    <li><a href="project?psa1_POL_test">Polish / polski</a></li>
    <li><a href="project?psa1_PT_test">Portuguese / Português</a></li>
    <li><a href="project?psa1_PT-BR_test">Portuguese (Brazilian) / Português (brasileiro)</a></li>
    <li><a href="project?psa1_RO_test">Romanian / Română</a></li>
    <li><a href="project?psa1_RU_test">Russian / Русский</a></li>
    <li><a href="project?psa1_SRP_test">Serbian / Српски</a></li>
    <li><a href="project?psa1_SLO_test">Slovak / slovensky</a></li>
    <li><a href="project?psa1_SPA_test">Spanish / Español</a></li>
    <li><a href="project?psa1_ES-PE_test">Spanish (Peruvian) / Peruano Español</a></li>
    <li><a href="project?psa1_SV_test">Swedish / Svenska</a></li>
    <li><a href="project?psa1_THA_test">Thai / ไทย</a></li>
    <li><a href="project?psa1_TUR_test">Turkish / Türkçe</a></li>
</ul>

<?php

$page->displayFooter();

?>