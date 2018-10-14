<?php
	header('Content-type: image/svg+xml');
	
	$hue = is_numeric($_GET['h']) ? $_GET['h']%361 : 0;
?>

<svg version="1.1"
     baseProfile="full"
     width="130" height="160"
     xmlns="http://www.w3.org/2000/svg">

  <g fill="none" stroke="hsl(<?= $hue ?>,<?= $hue ? 80 : 0 ?>%,30%)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round">
  	<polygon points="4,4 90,4 126,40 126,156 4,156 4,4" fill="rgba(255,255,255,.75)" />
  	
  	<polyline points="90,4 90,40 126,40" />

    <line x1="30" y1="65" x2="100" y2="65" />
    <line x1="30" y1="87" x2="100" y2="87" />
    <line x1="30" y1="109" x2="100" y2="109" />
    <line x1="30" y1="131" x2="100" y2="131" />
  </g>
</svg>