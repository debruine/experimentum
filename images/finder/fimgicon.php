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

    <text x="10" y="110" font-size="50" font-family="Arial" stroke-width="2" fill="hsl(<?= $hue ?>,<?= $hue ? 80 : 0 ?>%,30%)" >fIMG</text>
  </g>
</svg>