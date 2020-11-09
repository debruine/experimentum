<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

$clean = my_clean($_GET);

$ucheck = new myQuery();
$ucheck->prepare("SELECT username FROM user WHERE username=? LIMIT 1",
                  array('s', $clean['username']));
if ($ucheck->get_num_rows() > 0) {
    scriptReturn('taken');
} else {
    scriptReturn('free');
}

exit;
?>