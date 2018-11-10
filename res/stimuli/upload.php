<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';
auth($RES_STATUS);

$title = array(
    '/res/' => loc('Researchers'),
    '/res/stimuli/' => loc('Stimuli'),
    '/res/stimuli/upload' => loc('Upload Stimuli')
);

$styles = array('#msg' => 'height: 400px; background: transparent center center no-repeat url("/images/stuff/loading_theme");');

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
$formTable->set_action('/res/scripts/stim_upload');
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

<p class="note">Image formats: jpg, gif, png; audio format: mp3; video format: m4v</p>

<?= $formTable->print_form() ?>

<h2>My Uploaded Stimuli</h2>
<div id="imagebox">
    <div id='imageurl'></div>
    <audio controls="controls">
        <source src="" type="audio/mpeg" />
        Your browser does not support the audio element.
    </audio><br>
    <video width="300" height="225">
        <source src="" type="video/mp4" />
        Your browser does not support the video element.
    </video><br>
    <img />
</div>

<button id="delete">Delete</button>

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
        
        $('#delete').button().click(function() {
            var selFiles = [];
            var isdir = false;
            
            $('#finder li.file.ui-selected').each( function(i) {
                selFiles[i] = $(this).attr('url');
            });
            
            if (selFiles.length == 0) {
                selFiles = '';
                
                var openDir = $('#finder li.folder:not(.closed)').last();
                
                if (openDir.find('>ul li').length > 0) { 
                    growl("Folders must be empty to delete", 3000);
                    return false;
                } else {
                    selFiles = openDir.attr('url');
                    isdir = true;
                }
            }
            
            $.ajax({
                url: "/res/scripts/stim_delete",
                dataType: 'json',
                data: {
                    file: selFiles,
                    isdir: isdir
                },
                success: function(data) {
                    console.log(data);
                },
                error: function() {
                },
                complete: function() {
                    reloadFinder();
                }
            });
        });
        
        /*
        $('#folderInfo_form').submit(function(e) {
            console.log("submit");
            var selectedfiles = document.getElementById('uploads[]').files;
            
            $.each(selectedfiles, function(i, file) {
                console.log(selectedfiles.item(i));
                var formData = new FormData();
                formData.append('uploads[0]', selectedfiles.item(i));
                formData.append('subdir', $('#subdir').val());
                formData.append('description', $('#description').val());
                
                console.log(formData);

                $.ajax({
                    data: formData,
                    url: "/res/scripts/stim_upload",
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(data) {
                        console.log(data);
                    },
                    error: function() {
                    },
                    complete: function() {
                        console.log("complete");
                        reloadFinder();
                    }
                });
            });
            return false;
        });
        */
        
        $('#imagebox video').click( function() {
            $('#imagebox video').get(0).play();
        });
        function reloadFinder() {
            $('#finder').html("");
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
        }
        reloadFinder();
    } );

</script>

<?php

$page->displayFooter();

?>