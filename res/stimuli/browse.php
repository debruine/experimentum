<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

$title = array(
    '/res/' => loc('Researchers'),
    '/res/stimuli/' => loc('Stimuli'),
    '/res/stimuli/browse' => loc('Browse Stimuli')
);

$styles = array(
    '#msg' => 'height: 400px; background: transparent center center no-repeat url("/images/stuff/loading_theme");',
);


/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<p>Go to <a href="/res/stimuli/upload">My Uploads</a> to view your uploaded stimuli.</p>

<!--<a id="select_all">Select All</a>-->

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
    window.onresize = sizeToViewport;
    $('#finder').hide();
    
    $('#select_all').button().click(function() {
        console.log("select_all");
        var $finder = $('#finder');
        if ($finder.is(':visible')) {
            // (un)select all files in the open folder
            var $openFolder = $finder.find('li.folder')
                                  .filter(':not(.closed)')
                                   .filter(':last');
            // unselect selected folders
            $openFolder.find('li.folder.selected').removeClass('selected'); 
    
            var $allfiles = $openFolder.find('> ul > li.image:visible');
            console.log($allfiles);
            if ($allfiles.length == $allfiles.filter('.selected').length) {
                // all files are already selected, so unselect instead
                $finder.find('li.file').removeClass('selected');
            } else {
                $allfiles.addClass('selected');
            }
        }
    });
    
    // get directory structure via ajax
    $.ajax({
        url: '/res/scripts/browse?dir=/stimuli/', 
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            //$('#finder').html(JSON.stringify(data));
            folderize(data, $('#finder'));
            
            // hide loading animation and show finder
            $('#msg').hide();
            $('#finder').show();
            sizeToViewport();
        }
    });
});

</script>

<?php

$page->displayFooter();

?>