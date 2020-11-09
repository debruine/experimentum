<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

$return = array(
    'error' => false,
    'deleted' => array()
);

function recurseRmdir($dir) {
    global $return;
    
    $files = array_diff(scandir($dir), array('.','..'));
    foreach ($files as $file) {
        $path = "$dir/$file";
        if (is_dir($path)) { 
            recurseRmdir($path);
        } else {
            $stim = str_replace(array(DOC_ROOT, '.jpg','.gif','.png','.mp3'), '', $path);
            $q = new myQuery("SELECT exp_id
                                FROM stimuli
                          RIGHT JOIN trial ON (left_img=stimuli.id OR center_img=stimuli.id OR right_img=stimuli.id) 
                               WHERE path = '$stim'
                            GROUP BY exp_id"
            );
            if ($q->get_num_rows() == 0) {
                if (unlink($path)) {
                    $return['deleted'][] = $file;
                    $q = new myQuery("DELETE FROM stimuli WHERE path = '$stim'");
                } else {
                    $return['error'][$file] = "The file could not be deleted";
                }
            } else {
                $return['error'][$file] = array("in_exp" => $q->get_col('exp_id'));
            }
        }
    }
    
    ## rescan 
    $files = array_diff(scandir($dir), array('.','..'));
    if (count($files) > 0) {
        return false;
    } else {
        return rmdir($dir);
    }
}

$userdir = DOC_ROOT . "/stimuli/uploads/" . $_SESSION['user_id'] . "/";

if ($_POST['isdir'] == "true") {
    $dir = $_POST['file'];
    $path = DOC_ROOT . $dir;
    $path = realpath($path);
    $dir = str_replace($userdir, "", $path);
    
    if (strpos($path, $userdir) !== 0) {
        $return['error'][$dir] = "You don't have permission to delete this.";
    } else {
        /*
        $handle = opendir($path);
        $count = 0;
        while (false !== ($file = readdir($handle))) {
            if (!in_array($file, array('.', '..'))) {
                $count++;
            }
        }
        closedir($handle);
        
        if ($count) {
            $return['error'][$dir] = "The directory contains $count remaining files. Please delete these before you delete the folder.";
        } else */
        if (recurseRmdir($path)) {
            $return['deleted'][] = $dir;
        } else {
            $return['error'][$dir] = "The directory could not be deleted.";
        }
    }
} else {
    foreach ($_POST['file'] as $file) {
        $path = DOC_ROOT . $file;
        $path = realpath($path);
        $file = str_replace($userdir, "", $path);
    
        if (strpos($path, $userdir) !== 0) {
            $return['error'][$file] = "You are not authorised to delete this file.";
        } else if (!file_exists($file)) {
            $return['error'][$file] = "This file doesn't exist";
        } else {
            $stim = str_replace(array(DOC_ROOT, '.jpg','.gif','.png','.mp3'), '', $path);
            $q = new myQuery("SELECT exp_id
                                FROM stimuli
                          RIGHT JOIN trial ON (left_img=stimuli.id OR center_img=stimuli.id OR right_img=stimuli.id) 
                               WHERE path = '$stim'
                            GROUP BY exp_id"
            );
            if ($q->get_num_rows() == 0) {
                if (unlink($path)) {
                    $return['deleted'][] = $file;
                    $q = new myQuery("DELETE FROM stimuli WHERE path = '$stim'");
                } else {
                    $return['error'][$file] = "The file could not be deleted";
                }
            } else {
                $return['error'][$file] = array("in_exp" => $q->get_col('exp_id'));
            }
        }
    }
}

scriptReturn($return);

?>