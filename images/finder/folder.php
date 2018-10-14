<?php
	header('Content-type: image/svg+xml');
	
	$hue = is_numeric($_GET['h']) ? $_GET['h']%361 : 0;
	if ($_GET['h'] == 362) $hue = 360;
?>

<svg version="1.1"
     baseProfile="full"
     width="160" height="122"
     xmlns="http://www.w3.org/2000/svg">

  <g fill="none" stroke="hsl(<?= $hue ?>,<?= $hue ? 80 : 0 ?>%,30%)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" transform="translate(0,-18)">
  	<path d="M34,136 L126,136 C156,136 156,136 156,106 
  			L156,66 C156,36 156,36 126,36 
  			L34,36 C4,36 4,36 4,66 
  			L4,106 C4,136 4,136 34,136 Z"
  		  fill="hsla(<?= $hue ?>,<?= $hue ? 50 : 0 ?>%,70%,.75)" />
  			
  	<path d="M16,36 L22,24 C24,22 24,22 26,22 L46,22 C48,22 48,22 50,24 L56,36 Z"
  			fill="hsla(<?= $hue ?>,<?= $hue ? 80 : 0 ?>%,30%,.5)" />
  </g>
</svg>