<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

function folder_array($dir) {
    $a = array(); // array of directories in this directory
    
    $d = dir($dir);
    
    while (false !== ($f = $d->read())) {
        $ext = pathinfo($f)['extension'];
        $show_exts = array("jpg", "png", "gif", "ogg", "mp3", "m4v", "wav", "txt", "csv");
            
        if (is_dir($dir . $f . '/') && substr($f, 0, 1) != '.') {
            $dirname = str_replace(DOC_ROOT, '', $dir) . $f;
            $valid = false;
            # remove uploads directory unless admin or own uploads dir
            $valid = $valid || strpos($dirname, '/stimuli/uploads/') !== 0; // not a user directory
            $userdir = '/stimuli/uploads/' . $_SESSION['user_id'];
            $valid = $valid || $dirname == $userdir; // is own user directory
            $valid = $valid || strpos($dirname, $userdir . '/') === 0; // is a subdirectory of own user directory
            $valid = $valid || $_SESSION['status'] == 'admin'; // admins can see all images

            if ($valid) {
                $a[$dirname] = folder_array($dir . $f . '/');
            }
        } elseif (in_array($ext, $show_exts) && substr($f, 0, 1) != '.') {
            $filename = str_replace(DOC_ROOT, '', $dir) . $f;
            $a["  " . $filename] = $filename;
        }
    }
    $d->close();

    ksort($a);
    
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