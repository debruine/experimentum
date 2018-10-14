<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('student', 'researcher', 'admin'));

function folder_array($dir) {
    $a = array(); // array of directories in this directory
    
    $d = dir($dir);
    
    while (false !== ($f = $d->read())) {
        $ext = pathinfo($f)['extension'];
        $show_exts = array("jpg", "png", "gif", "mp3", "ogg", "m4v", "wav", "txt", "csv");
            
        if (is_dir($dir . $f . '/') && substr($f, 0, 1) != '.') {
            $a[str_replace(DOC_ROOT, '', $dir) . $f] = folder_array($dir . $f . '/');
        } elseif (in_array($ext, $show_exts) && substr($f, 0, 1) != '.') {
            $a[] = str_replace(DOC_ROOT, '', $dir) . $f;
        }
    }
    $d->close();

    asort($a);
    
    # remove uploads directory unless admin
    if ($_SESSION['status'] != 'admin') {
        unset($a['/stimuli/uploads']);
    }
    
    return $a;
}

if (isset($_GET['dir'])) {
    $stimuli_dir = DOC_ROOT . $_GET['dir'];
    $stimulus_dir_structure = folder_array($stimuli_dir);
    //print_dir_array($stimulus_dir_structure);
    
    echo json_encode($stimulus_dir_structure);
}
   
exit;

?>