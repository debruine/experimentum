<?php
	header('Content-type: image/svg+xml');
	
	$fill = array_key_exists("c", $_GET) ? $_GET['c'] : "000000";
?>
<svg version="1.1"
     baseProfile="full"
     xmlns="http://www.w3.org/2000/svg"
     width="20" height="20" viewBox="0 0 20 20">
<path fill="#<?= $fill ?>" d="M11.5 7c-0.276 0-0.5-0.224-0.5-0.5 0-1.378-1.122-2.5-2.5-2.5-0.276 0-0.5-0.224-0.5-0.5s0.224-0.5 0.5-0.5c1.378 0 2.5-1.122 2.5-2.5 0-0.276 0.224-0.5 0.5-0.5s0.5 0.224 0.5 0.5c0 1.378 1.122 2.5 2.5 2.5 0.276 0 0.5 0.224 0.5 0.5s-0.224 0.5-0.5 0.5c-1.378 0-2.5 1.122-2.5 2.5 0 0.276-0.224 0.5-0.5 0.5zM10.301 3.5c0.49 0.296 0.903 0.708 1.199 1.199 0.296-0.49 0.708-0.903 1.199-1.199-0.49-0.296-0.903-0.708-1.199-1.199-0.296 0.49-0.708 0.903-1.199 1.199z"></path>
<path fill="#<?= $fill ?>" d="M1.5 10c-0.276 0-0.5-0.224-0.5-0.5s-0.224-0.5-0.5-0.5c-0.276 0-0.5-0.224-0.5-0.5s0.224-0.5 0.5-0.5c0.276 0 0.5-0.224 0.5-0.5s0.224-0.5 0.5-0.5 0.5 0.224 0.5 0.5c0 0.276 0.224 0.5 0.5 0.5s0.5 0.224 0.5 0.5-0.224 0.5-0.5 0.5c-0.276 0-0.5 0.224-0.5 0.5s-0.224 0.5-0.5 0.5z"></path>
<path fill="#<?= $fill ?>" d="M18.147 15.939l-10.586-10.586c-0.283-0.283-0.659-0.438-1.061-0.438s-0.778 0.156-1.061 0.438l-0.586 0.586c-0.283 0.283-0.438 0.659-0.438 1.061s0.156 0.778 0.438 1.061l10.586 10.586c0.283 0.283 0.659 0.438 1.061 0.438s0.778-0.156 1.061-0.438l0.586-0.586c0.283-0.283 0.438-0.659 0.438-1.061s-0.156-0.778-0.438-1.061zM5.561 6.646l0.586-0.586c0.094-0.094 0.219-0.145 0.354-0.145s0.26 0.052 0.354 0.145l1.439 1.439-1.293 1.293-1.439-1.439c-0.195-0.195-0.195-0.512 0-0.707zM17.439 17.354l-0.586 0.586c-0.094 0.094-0.219 0.145-0.353 0.145s-0.26-0.052-0.353-0.145l-8.439-8.439 1.293-1.293 8.439 8.439c0.195 0.195 0.195 0.512 0 0.707z"></path>
<path fill="#<?= $fill ?>" d="M3.5 5c-0.276 0-0.5-0.224-0.5-0.5 0-0.827-0.673-1.5-1.5-1.5-0.276 0-0.5-0.224-0.5-0.5s0.224-0.5 0.5-0.5c0.827 0 1.5-0.673 1.5-1.5 0-0.276 0.224-0.5 0.5-0.5s0.5 0.224 0.5 0.5c0 0.827 0.673 1.5 1.5 1.5 0.276 0 0.5 0.224 0.5 0.5s-0.224 0.5-0.5 0.5c-0.827 0-1.5 0.673-1.5 1.5 0 0.276-0.224 0.5-0.5 0.5zM2.998 2.5c0.19 0.143 0.359 0.312 0.502 0.502 0.143-0.19 0.312-0.359 0.502-0.502-0.19-0.143-0.359-0.312-0.502-0.502-0.143 0.19-0.312 0.359-0.502 0.502z"></path>
<path fill="#<?= $fill ?>" d="M3.5 15c-0.276 0-0.5-0.224-0.5-0.5 0-0.827-0.673-1.5-1.5-1.5-0.276 0-0.5-0.224-0.5-0.5s0.224-0.5 0.5-0.5c0.827 0 1.5-0.673 1.5-1.5 0-0.276 0.224-0.5 0.5-0.5s0.5 0.224 0.5 0.5c0 0.827 0.673 1.5 1.5 1.5 0.276 0 0.5 0.224 0.5 0.5s-0.224 0.5-0.5 0.5c-0.827 0-1.5 0.673-1.5 1.5 0 0.276-0.224 0.5-0.5 0.5zM2.998 12.5c0.19 0.143 0.359 0.312 0.502 0.502 0.143-0.19 0.312-0.359 0.502-0.502-0.19-0.143-0.359-0.312-0.502-0.502-0.143 0.19-0.312 0.359-0.502 0.502z"></path>
</svg>
