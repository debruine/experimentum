<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

$title = loc('Informed Consent');

$styles = array(
	'.ethicsList' => 'margin: 1em auto; width: 35em;'
);
$page = new page($title);
$page->set_logo(true);
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();

echo tag("If you wish to use our images for research purposes, you may do so only if you adhere to the following requirements:");

$ethics = array(
	"Images will never be used for commercial purposes.",
	"Images will never be portrayed as real people (e.g., you cannot use them as avatars for a fake Facebook profile or dating advert).",
	"Images will not be portrayed in a defamatory way (e.g., images will not be described as having committed criminal acts, even in the context of an experiment).",
	"All scientific publications resulting from the use of these images will contain an appropriate citation (see <a href='http://facelab.org/Publications/search?type=paper'>facelab.org</a> for a list of appropriate papers, or email <a href='mailto:info@faceresearch.org'>info@faceresearch.org</a>).",
	"Images will not be published without permission (email <a href='mailto:info@faceresearch.org'>info@faceresearch.org</a>)."
);

echo linkList($ethics, 'ethicsList', 'ol');

$page->displayFooter();

?>