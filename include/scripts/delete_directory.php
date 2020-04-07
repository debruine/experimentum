<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';	
    auth(array('admin'), '/res/');
    
    exit;
    
    $return = array();
    
    define('IMAGEBASEDIR', DOC_ROOT . "stimuli/");
    function recursive_delete($dir) {
        global $return;
        
        // only delete directories in the images path
        if (strpos($dir, IMAGEBASEDIR) === 0) {
            $dir = str_replace(IMAGEBASEDIR, '', $dir); 
        }
        
        $deletedir = realpath(IMAGEBASEDIR . $dir);
        
        if (strpos($deletedir, IMAGEBASEDIR) !== 0) {
            $return['delete']['error'][] = $dir . ' is not in the image directory';
        } else if (is_dir($deletedir)) {
            $handle = opendir($deletedir);
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $ext = pathinfo($entry, PATHINFO_EXTENSION);
                    $path = $deletedir . '/' . $entry;
                    if (!is_dir($path)) {
                        unlink($path);
                        $return['delete']['files'][] = $path;
                    } else {
                        recursive_delete($path);
                    }
                }
            }
            closedir($handle);
            rmdir($deletedir);
            $return['delete']['dirs'][] = $deletedir;
        } else {
            $return['delete']['error'] = $dir . ' does not exist';
        }
    }
    
    //recursive_delete(IMAGEBASEDIR . "4");
    
    print_r($return);
?>