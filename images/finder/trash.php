<?php
	header('Content-type: image/svg+xml');
	
	$hue = is_numeric($_GET['h']) ? $_GET['h']%361 : 0;
	if ($_GET['h'] == 362) $hue = 170;
?>

<svg version="1.1"
     baseProfile="full"
     width="130" height="160"
     xmlns="http://www.w3.org/2000/svg">

  <g fill="none" stroke="hsl(<?= $hue ?>,<?= $hue ? 80 : 0 ?>%,30%)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round">
  	<path d="M24,156 
  	        L 106,156 C116,156 116,156 116,126 
  			L116, 46  
  			L 14, 46 
  			L 14,136 C 14,156  14,156  24,156 Z"
  		  fill="hsla(<?= $hue ?>,<?= $hue ? 50 : 0 ?>%,70%,.75)" />	  
  		  
  	<path d="M14,46 
  	        L116,46  C126,46 126,30 116,30 
  			L 14,30   C4,30    4,46  14,46 Z"
  		  fill="hsla(<?= $hue ?>,<?= $hue ? 50 : 0 ?>%,70%,.75)" />
  			
  	<path d="M45,30 L51,18 C53,16 53,16 55,16 L75,16 C77,16 77,16 79,18 L85,30 Z" />
  		  
  	<line x1="37" y1="46" x2="37" y2="136" />
    <line x1="65" y1="46" x2="65" y2="136" />
    <line x1="93" y1="46" x2="93" y2="136" />
  </g>
</svg>