<?php
	header('Content-type: image/svg+xml');
	
	$fill = array_key_exists("c", $_GET) ? $_GET['c'] : "000000";
?>
<svg version="1.1"
     baseProfile="full"
     xmlns="http://www.w3.org/2000/svg"
     width="20" height="20" viewBox="0 0 20 20">
<path fill="#<?= $fill ?>" d="M0.646 10.146l6-6c0.195-0.195 0.512-0.195 0.707 0s0.195 0.512 0 0.707l-5.146 5.146h16.293c0.276 0 0.5 0.224 0.5 0.5s-0.224 0.5-0.5 0.5h-16.293l5.146 5.146c0.195 0.195 0.195 0.512 0 0.707-0.098 0.098-0.226 0.146-0.354 0.146s-0.256-0.049-0.354-0.146l-6-6c-0.195-0.195-0.195-0.512 0-0.707z"></path>
</svg>
