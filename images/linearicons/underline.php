<?php
	header('Content-type: image/svg+xml');
	
	$fill = array_key_exists("c", $_GET) ? $_GET['c'] : "000000";
?>
<svg version="1.1"
     baseProfile="full"
     xmlns="http://www.w3.org/2000/svg"
     width="20" height="20" viewBox="0 0 20 20">
<path fill="#<?= $fill ?>" d="M14.5 18h-9c-0.276 0-0.5-0.224-0.5-0.5s0.224-0.5 0.5-0.5h9c0.276 0 0.5 0.224 0.5 0.5s-0.224 0.5-0.5 0.5z"></path>
<path fill="#<?= $fill ?>" d="M10 15c-2.757 0-5-2.243-5-5v-7.5c0-0.276 0.224-0.5 0.5-0.5s0.5 0.224 0.5 0.5v7.5c0 2.206 1.794 4 4 4s4-1.794 4-4v-7.5c0-0.276 0.224-0.5 0.5-0.5s0.5 0.224 0.5 0.5v7.5c0 2.757-2.243 5-5 5z"></path>
</svg>
