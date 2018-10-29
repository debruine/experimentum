<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';
auth(array('res', 'admin'), "/res/");

$title = array(
    '/res/' => loc('Researchers'),
    '/res/stimuli/' => loc('Stimuli'),
    '/res/stimuli/upload' => loc('Upload Stimuli')
);

$styles = array('#msg' => 'height: 400px; background: transparent center center no-repeat url("/images/stuff/loading_theme");');

if (count($_FILES) > 0) {

    $mydir = DOC_ROOT . '/stimuli/uploads/' . $_SESSION['user_id'];
    
    //if (!is_dir(DOC_ROOT . '/stimuli/uploads')) mkdir(DOC_ROOT . '/stimuli/uploads', 0755);
    
    if (!is_dir($mydir)) mkdir($mydir, 0755);
    
    $subdir = $mydir . '/' . $_POST['subdir'];
    $subdir = str_replace('//', '/', $subdir);
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
            case "ogg":
            case "mp3":
            case "wav":
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
            $stimname = str_replace(array(DOC_ROOT, '.jpg','.gif','.png','.mp3','.ogg'), '', $file['newname']);
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
    
    header('Location: /res/stimuli/upload?updated=' . count($uploaded)); 
    exit;
}

// set up form
$form = array();

$form['subdir'] = new input('subdir', 'subdir');
$form['subdir']->set_question('Folder name');
$form['subdir']->set_placeholder('newfolder/subfolder');
$form['subdir']->set_width(320);

$form['uploads[]'] = new input('uploads[]', 'uploads[]');
$form['uploads[]']->set_question('Files (<span id="filenumber">0</span>)');
$form['uploads[]']->set_type('file');

$form['description'] = new textarea('description', 'description');
$form['description']->set_question('Description');
$form['description']->set_width(320);

// set up form table
$formTable = new formTable();
$formTable->set_table_id('folderInfo');
$formTable->set_enctype('multipart/form-data');
$formTable->set_title('Folder to Upload');
$formTable->set_action('upload');
$formTable->set_questionList($form);
$formTable->set_method('post');
$formTable->set_submit_text('Upload');

/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<p class="warning">You can upload image files with the formats jpg, gif, or png. 
    For audio files, you must upload both ogg and mp3 versions for your study to 
    be playable on all major browsers.</p> 

<?= $formTable->print_form() ?>

<h2>My Uploaded Stimuli</h2>
<div id="imagebox">
    <div id='imageurl'></div>
    <img />
</div>

<div id="finder"></div>

<!--****************************************************-->
<!-- !Javascripts for this page -->
<!--****************************************************-->

<script>

    $(function() { 
        $('input[name^=uploads]').attr('multiple', 'multiple')
            .after('<ul id="uploadlist"></ul>')
            .change( function() {
            var selectedfiles = document.getElementById('uploads[]').files;
            $('#filenumber').html(selectedfiles.length);
            
            $('#uploadlist').html('');
            for (var i = 0; i < selectedfiles.length; ++i) {
                var name = selectedfiles.item(i).name;
                $('#uploadlist').append('<li>' + name + '</li>');
            }
        });
    
        $.ajax({
            url: '/res/scripts/browse?dir=/stimuli/uploads/<?= $_SESSION['user_id'] ?>/', 
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                folderize(data, $('#finder'));
                
                // hide loading animation and show finder
                $('#finder').show();
                sizeToViewport();
            }
        });
    } );

</script>

<?php

$page->displayFooter();

?>