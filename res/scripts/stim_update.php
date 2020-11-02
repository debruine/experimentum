<?php
    # updates all potential stimuli in a directory
    # run at site initialisation
    # http://exp.test/res/scripts/stim_update?dir=mooney
    # http://exp.test/res/scripts/stim_update?dir=facelab_canada
    # http://exp.test/res/scripts/stim_update?dir=facelab_london

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

$return = array();

$mydir = DOC_ROOT . '/stimuli/' . $_GET['dir'];


function find_all_files($dir) {
    $root = scandir($dir);
    foreach($root as $value) {
        if($value === '.' || $value === '..') {continue;}
        if(is_file("$dir/$value")) {$result[]="$dir/$value";continue;}
        foreach(find_all_files("$dir/$value") as $value) {
            $result[]=$value;
        }
    }
    return $result;
} 

$files = find_all_files($mydir);

foreach ($files as $file) {
    if (is_file($file)) {
        $ext = pathinfo($file)['extension'];
        
        
        switch ($ext) {
            case "jpg":
            case "gif":
            case "png":
                $type = "image";
                break;
            case "mp3":
                $type = "audio";
                break;
            case "m4v":
                $type = "video";
                break;
            default:
                $type = false;
        }
        
        if ($type) {
            $stimname = str_replace(array(DOC_ROOT, '.jpg','.gif','.png','.mp3'), '', $file);
            $size = filesize($file);
            $q = new myQuery("SELECT id FROM stimuli WHERE path='{$stimname}'");
            if ($q->get_num_rows() > 0) {
                $newid = $q->get_one();
                $query = "UPDATE stimuli SET type='{$type}', size='{$size}' WHERE id='{$newid}'";
                $q = new myQuery($query);
            } else {
                $query = "INSERT INTO stimuli (path, type, size) VALUES 
                          ('{$stimname}', '{$type}', '{$size}')";
                $q = new myQuery($query);
                $newid = $q->get_insert_id();
            }
            $return['uploaded'][$newid] = $stimname;
        } else {
            $return['rejected'][] = $file;
        }
    } else {
        $return['not_file'][] = $file;
    }
}

    
scriptReturn($return);
exit;

?>