<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

function perm($perms) {
    switch ($perms & 0xF000) {
        case 0xC000: // socket
            $info = 's';
            break;
        case 0xA000: // symbolic link
            $info = 'l';
            break;
        case 0x8000: // regular
            $info = 'r';
            break;
        case 0x6000: // block special
            $info = 'b';
            break;
        case 0x4000: // directory
            $info = 'd';
            break;
        case 0x2000: // character special
            $info = 'c';
            break;
        case 0x1000: // FIFO pipe
            $info = 'p';
            break;
        default: // unknown
            $info = 'u';
    }
    
    // Owner
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ?
                (($perms & 0x0800) ? 's' : 'x' ) :
                (($perms & 0x0800) ? 'S' : '-'));
    
    // Group
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ?
                (($perms & 0x0400) ? 's' : 'x' ) :
                (($perms & 0x0400) ? 'S' : '-'));
    
    // World
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ?
                (($perms & 0x0200) ? 't' : 'x' ) :
                (($perms & 0x0200) ? 'T' : '-'));
    
    return( $info );
}

$return = array('deleted' => array());

$userdir = DOC_ROOT . "/stimuli/uploads/" . $_SESSION['user_id'] . "/";

if ($_POST['isdir'] == "true") {
    $dir = $_POST['file'];
    $path = DOC_ROOT . $dir;
    $path = realpath($path);
    
    if (strpos($path, $userdir) !== 0) {
        $return['error'][$dir] = "You don't have permission to delete this.";
    } else {
        $handle = opendir($path);
        $count = 0;
        while (false !== ($file = readdir($handle))) {
            if (!in_array($file, array('.', '..'))) {
                $count++;
            }
        }
        closedir($handle);
        
        if ($count) {
            $return['error'][$dir] = "The directory <code>$dir</code> contains $count remaining files. Please delete these before you delete the folder.";
        } else {
            $return['deleted'][$dir] = rmdir($path);
        }
    }
} else {
    foreach ($_POST['file'] as $file) {
        $path = DOC_ROOT . $file;
        $path = realpath($path);
    
        if (strpos($path, $userdir) !== 0) {
            $return['error'][$file] = "You are not authorised to delete this file.";
        } else if (!file_exists($path)) {
            $return['error'][$file] = "This file doesn't exist";
        } else {
            $return['deleted'][$file] = unlink($path);
        }
        if (!$return['deleted'][$file]) {
            $return['deleted'][$file]['perms'] = perm(fileperms($path));
            $return['deleted'][$file]['owner'] = fileowner($path);
            $return['deleted'][$file]['me'] = getmyuid();
        }
    }
}

scriptReturn($return);

?>