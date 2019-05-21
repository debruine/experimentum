<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

if (count($_FILES) > 0) {

    $mydir = DOC_ROOT . '/stimuli/uploads/' . $_SESSION['user_id'];
    
    //if (!is_dir(DOC_ROOT . '/stimuli/uploads')) mkdir(DOC_ROOT . '/stimuli/uploads', 0755);
    
    if (!is_dir($mydir)) mkdir($mydir, 0755);
    
    $subdir = $mydir . '/' . $_POST['subdir'];
    $subdir = preg_replace('/\/+/', '/', $subdir);
    $subdir = safeFileName($subdir);
    if (substr($subdir, -1) == '/') $subdir = substr($subdir, 0, -1);
    if (!is_dir($subdir))  mkdir($subdir, 0755, true);
    
    $description = my_clean($_POST['description']);
    
    $uploaded = array();
    $okfiles = array();
    
    foreach ($_FILES['uploads']['name'] as $n => $name) {
        //$type = explode('/', $_FILES['uploads']['type'][$n]);
        $tmp_name = $_FILES['uploads']['tmp_name'][$n];
        $error = $_FILES['uploads']['error'][$n];
        $size = $_FILES['uploads']['size'][$n];
        $ext = pathinfo($name)['extension'];
        
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

        if ($error == 0 && $size > 0 && 
            in_array($type, array('image','audio','video'))
            ) {
            $newname = $subdir . '/' . safeFileName($name);
            $okfiles[$newname] = array(
                'newname' => $newname,
                'tmp_name' => $tmp_name,
                'type' => $type,
                'size' => $size
            );
        }
    }
    
    ksort($okfiles);
    foreach ($okfiles as $file) {
        if (copy($file['tmp_name'], $file['newname'])) {
            chmod($file['newname'], 0744);
            $stimname = str_replace(array(DOC_ROOT, '.jpg','.gif','.png','.mp3'), '', $file['newname']);
            $q = new myQuery("SELECT id FROM stimuli WHERE path='{$stimname}'");
            if ($q->get_num_rows() > 0) {
                $newid = $q->get_one();
                $query = "UPDATE stimuli SET type='{$file['type']}', size='{$file['size']}', description='$description' WHERE id='{$newid}'";
                $query = str_replace("'null'", "NULL", $query);
                $q = new myQuery($query);
            } else {
                $query = "INSERT INTO stimuli (path, type, size, description) VALUES ('{$stimname}', '{$file['type']}', '{$file['size']}', '$description')";
                $query = str_replace("'null'", "NULL", $query);
                $q = new myQuery($query);
                $newid = $q->get_insert_id();
            }
            $uploaded[$newid] = $stimname;
        }
    }
    
    header('Location: /res/stimuli/upload?updated=' .  count($uploaded)); 
    //scriptReturn($uploaded);
    exit;
}

?>