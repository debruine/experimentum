<?php
	header('Content-type: image/svg+xml');
	
	$fill = array_key_exists("c", $_GET) ? $_GET['c'] : "000000";
?>
<svg version="1.1"
     baseProfile="full"
     xmlns="http://www.w3.org/2000/svg"
     width="20" height="20" viewBox="0 0 20 20">
<path fill="#<?= $fill ?>" d="M17.894 8.897c-1.041 0-2.928-0.375-3.516-0.963-0.361-0.361-0.446-0.813-0.515-1.177-0.085-0.448-0.136-0.581-0.332-0.666-0.902-0.388-2.196-0.61-3.551-0.61-1.34 0-2.62 0.219-3.512 0.6-0.194 0.083-0.244 0.216-0.327 0.663-0.068 0.365-0.152 0.819-0.512 1.179-0.328 0.328-1.015 0.554-1.533 0.685-0.668 0.169-1.384 0.267-1.963 0.267-0.664 0-1.113-0.126-1.372-0.386-0.391-0.391-0.641-0.926-0.685-1.467-0.037-0.456 0.051-1.132 0.68-1.762 1.022-1.022 2.396-1.819 4.086-2.368 1.554-0.506 3.322-0.773 5.114-0.773 1.804 0 3.587 0.27 5.156 0.782 1.705 0.556 3.093 1.361 4.124 2.393 1.050 1.050 0.79 2.443 0.012 3.221-0.257 0.257-0.7 0.382-1.354 0.382zM9.98 4.481c1.507 0 2.908 0.246 3.946 0.691 0.713 0.306 0.833 0.938 0.92 1.398 0.052 0.275 0.097 0.513 0.24 0.656 0.252 0.252 1.706 0.671 2.809 0.671 0.481 0 0.633-0.082 0.652-0.094 0.31-0.314 0.698-1.086-0.017-1.802-1.805-1.805-5.010-2.882-8.574-2.882-3.535 0-6.709 1.065-8.493 2.848-0.288 0.288-0.42 0.616-0.391 0.974 0.025 0.302 0.17 0.614 0.39 0.836 0.019 0.012 0.173 0.098 0.67 0.098 1.098 0 2.541-0.411 2.789-0.659 0.141-0.141 0.185-0.379 0.236-0.654 0.086-0.462 0.203-1.095 0.917-1.4 1.026-0.439 2.413-0.68 3.905-0.68z"></path>
<path fill="#<?= $fill ?>" d="M16.5 18h-13c-0.671 0-1.29-0.264-1.743-0.743s-0.682-1.112-0.645-1.782c0.004-0.077 0.118-1.901 1.27-3.739 0.682-1.088 1.586-1.955 2.686-2.577 1.361-0.769 3.020-1.159 4.932-1.159s3.571 0.39 4.932 1.159c1.101 0.622 2.005 1.489 2.686 2.577 1.152 1.839 1.266 3.663 1.27 3.739 0.037 0.67-0.192 1.303-0.645 1.782s-1.072 0.743-1.743 0.743zM10 9c-3.117 0-5.388 1.088-6.749 3.233-1.030 1.623-1.139 3.282-1.14 3.299-0.022 0.392 0.111 0.761 0.373 1.038s0.623 0.43 1.017 0.43h13c0.393 0 0.754-0.153 1.017-0.43s0.395-0.646 0.373-1.039c-0.001-0.016-0.111-1.675-1.14-3.298-1.362-2.145-3.633-3.233-6.749-3.233z"></path>
<path fill="#<?= $fill ?>" d="M10 16c-1.654 0-3-1.346-3-3s1.346-3 3-3 3 1.346 3 3-1.346 3-3 3zM10 11c-1.103 0-2 0.897-2 2s0.897 2 2 2c1.103 0 2-0.897 2-2s-0.897-2-2-2z"></path>
</svg>
