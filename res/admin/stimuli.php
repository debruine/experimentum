<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin', 'super', 'res'), "/res/");

if (array_key_exists('dir', $_POST)) {
    $all_stim = array();
    $updated_images = 0;
    getImageFolders($_POST['dir']);
    ksort($all_stim);
    
    foreach ($all_stim as $fullpath => $ext) {
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
            $query = sprintf("INSERT IGNORE INTO stimuli (path, type, size) VALUES ('%s', '%s', '%d')",
                str_replace(array("../..", ".jpg", ".png", ".gif", ".mp3",), "", $fullpath),
                $type,
                filesize($fullpath)
            );
            $query = str_replace('\'\'', 'NULL', $query);
            //echo $query . "\n";
            $image_update = new myQuery($query);
            
            if ($image_update->get_affected_rows() == 1) {
                echo "    <li>recorded: $fullpath ($type)</li>";
                $updated_images++;
            } else {
                #echo "   *processed: $path\n";
            }
        }
    }
    
    
    echo "***" . count($all_stim) . " scanned; {$updated_images} updated";
    exit;
}

$styles = array(
    'textarea' => 'width: 100%; height: 5em;',
    'optgroup option' => 'padding-left: 1em;'
);

$title = array(
    '/res/' => loc('Researchers'),
    '/res/admin/' => loc('Admin'),
    '' => loc('Update Stimuli')
);


function getImageFolders($path) {
    global $all_stim;

    $d = dir($path);
    
    while (false !== ($f = $d->read())) {
        if (substr($f, 0, 1) != ".") {
            $fullpath = $path . "/" . $f;
            if (is_dir($fullpath)) { 
                getImageFolders($fullpath);
            } else {
                $ext = pathinfo($f)['extension'];
                
                switch ($ext) {
                    case "jpg":
                    case "gif":
                    case "png":
                        $type = "image";
                        break;
                    case "ogg":
                        $type = "audio";
                        break;
                    case "m4v":
                        $type = "video";
                        break;
                    case "mp3":
                    case "wav":
                    default:
                        $type = false;
                }
                
                if ($type) {
                    $all_stim[$fullpath] = $ext;
                }
            }
        }
    }
    $d->close();
}

/****************************************************/
/* !Display Page */
/***************************************************/


$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<select id="subfolders">
    <option value="">Check a Directory</option>
    <option value="../../stimuli">Scan All Stimuli</option>
</select>
<h2 id="number_updated"></h2>
<ol id="updated"></ol>


<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script>

$(function() {
    $('#subfolders').change(function() {
        if ($(this).val() != '') {
            //alert($(this).val());
            updateImages($(this).val());
        }
    });
});

function updateImages(url) {
    $('#number_updated').html('Checking ' + url);
    $('#updated').html('');
    $.ajax({
        url: 'stimuli',
        type: 'POST',
        data: 'dir=' + encodeURIComponent(url),
        success: function(data) {
            var split = data.split("***");
            $('#updated').html(split[0]);
            $('#number_updated').html(split[1]);
        }
    });
}
    
</script>

<?php

$page->displayFooter();

?>