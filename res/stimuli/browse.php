<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('student', 'researcher', 'admin'));

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
$page->set_logo(false);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<p>Go to <a href="/res/stimuli/upload">My Uploads</a> to view your uploaded stimuli.</p>

<div id="imagebox">
    <div id='imageurl'></div>
    <img />
</div>

<div id="finder"></div>


<!--****************************************************-->
<!-- !Javascripts for this page -->
<!--****************************************************-->

<script>
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


    $j(function() {
        window.onresize = sizeToViewport;
        $j('#finder').hide();
        
        // get directory structure via ajax
        $j.ajax({
            url: '/res/scripts/browse?dir=/stimuli/', 
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                //$j('#finder').html(JSON.stringify(data));
                folderize(data, $j('#finder'));
                
                // hide loading animation and show finder
                $j('#msg').hide();
                $j('#finder').show();
                sizeToViewport();
            }
        });
    });
</script>

<?php

$page->displayFooter();

?>