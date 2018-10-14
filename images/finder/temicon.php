<?php
	header('Content-type: image/svg+xml');
	
	$hue = is_numeric($_GET['h']) ? $_GET['h']%361 : 0;
	if ($_GET['h'] == 362) $hue = 280;
?>

<svg version="1.1"
     baseProfile="full"
     width="130" height="160"
     xmlns="http://www.w3.org/2000/svg">

  <g fill="none" stroke="hsl(<?= $hue ?>,<?= $hue ? 80 : 0 ?>%,30%)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round">
  	<polygon points="4,4 90,4 126,40 126,156 4,156 4,4" fill="rgba(255,255,255,.75)" />
  	
  	<polyline points="90,4 90,40 126,40" />
  	
    <line x1="30" y1="95" x2="100" y2="95" />
    <line x1="65" y1="60" x2="65" y2="130" />
  </g>
</svg>