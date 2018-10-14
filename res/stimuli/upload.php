<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';
auth(array('researcher', 'admin'));

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
    
    foreach ($_FILES['uploads']['name'] as $n => $name) {
        $type = explode('/', $_FILES['uploads']['type'][$n]);
        $tmp_name = $_FILES['uploads']['tmp_name'][$n];
        $error = $_FILES['uploads']['error'][$n];
        $size = $_FILES['uploads']['size'][$n];

        if ($error == 0 && $size > 0 && in_array($type[0], array('image','audio','video'))) {
            $newname = $subdir . '/' . safeFileName($name);
            if (copy($tmp_name, $newname)) {
                chmod($newname, 0744);
                $stimname = str_replace(array(DOC_ROOT, '.jpg','.gif','.png','.mp3','.ogg'), '', $newname);
                $q = new myQuery("SELECT id FROM stimuli WHERE path='{$stimname}'");
                if ($q->get_num_rows() > 0) {
                    $newid = $q->get_one();
                    $query = "UPDATE stimuli SET type='{$type[0]}', size='$size', description='$description' WHERE id='{$newid}'";
                    $query = str_replace("'null'", "NULL", $query);
                    $q = new myQuery($query);
                } else {
                    $query = "INSERT INTO stimuli (path, type, size, description) VALUES ('/{$stimname}', '{$type[0]}', '$size', '$description')";
                    $query = str_replace("'null'", "NULL", $query);
                    $q = new myQuery($query);
                    $newid = $q->get_insert_id();
                }
                $uploaded[$newid] = $stimname;
            }
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
$page->set_logo(false);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

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

    $j(function() { 
        $j('input[name^=uploads]').attr('multiple', 'multiple')
            .after('<ul id="uploadlist"></ul>')
            .change( function() {
            var selectedfiles = document.getElementById('uploads[]').files;
            $j('#filenumber').html(selectedfiles.length);
            
            $j('#uploadlist').html('');
            for (var i = 0; i < selectedfiles.length; ++i) {
                var name = selectedfiles.item(i).name;
                $j('#uploadlist').append('<li>' + name + '</li>');
            }
        });
    
        $j.ajax({
            url: '/res/scripts/browse?dir=/stimuli/uploads/<?= $_SESSION['user_id'] ?>/', 
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                folderize(data, $j('#finder'));
                
                // hide loading animation and show finder
                $j('#finder').show();
                sizeToViewport();
            }
        });
    } );

    function folderize(json, appendElement) {
        
        appendElement   .unbind('click')                    // remove folderize function
                        .parent('ul').find('li.folder').addClass('closed'); // close all folders at and below this level
        appendElement   .removeClass('closed')              // open folder, since you just clicked on it
                        .find('span').click( function() {   // add a folder opening function when clicked   
                            $j(this).parent().removeClass('closed') // open this folder on click
                                    .siblings('li').addClass('closed') // close sibling folders
                                    .find('li').addClass('closed'); // close all folders below this level
                        });
        
        var theFolder = $j('<ul />').css('margin-left', appendElement.width()+10);
    
        $j.each(json, function(folder, contents) {
            var theItem = $j('<li />');
            
            
            var intKey = new RegExp('^[0-9]+$');
            if (intKey.test(folder)) {
                // contents are an image name
                var splitName = contents.split('/');
                var shortName = splitName[splitName.length - 1];
                
                theItem .html('<span>' + shortName + '</span>')
                        .attr('url', contents)
                        .addClass('image')
                        .click( function() {
                            $j('#imagebox img').attr('src', $j(this).attr('url'));
                            $j('#imagebox #imageurl').html($j(this).attr('url'));
                            
                            $j('li.image.selected').removeClass('selected');
                            $j(this).addClass('selected');
                            $j(this).siblings('li').addClass('closed').find('li').addClass('closed');
                        });
            } else {
                // contents are more files/folders
                
                var splitName = folder.split('/');
                var shortName = splitName[splitName.length - 1];
                
                theItem .html('<span>' + shortName + '</span>')
                        .addClass('folder closed')
                        .data('contents', contents)
                        .click( function() { 
                            folderize($j(this).data('contents'), $j(this)); 
                        });
            }
            
            theFolder.append( theItem );
        });
    
        appendElement.append( theFolder );
        $j('#finder > ul').css('margin-left', 0); // fix first ul
    }
    
    function sizeToViewport() {
        var new_height = $j(window).height() - $j('#finder').offset().top - $j('#footer').height()-30;
        $j('#finder').height(new_height);
    }

</script>

<?php

$page->displayFooter();

?>