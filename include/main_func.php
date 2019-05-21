<?php

/***************************************************/
/* !Functions and code for every page */
/***************************************************/
    
    // set php environment variables and start the session
    session_start();
    ini_set("arg_separator.output", "&amp;");
    define('DOC_ROOT', $_SERVER['DOCUMENT_ROOT']);
    
    // get user-defined variables and main classes
    require_once DOC_ROOT.'/include/config.php';
    require_once DOC_ROOT.'/include/classes/mysqli.class.php';
    require_once DOC_ROOT.'/include/classes/general.php';
    
    // send to return_location if status is not high enough for this page (integer $status)
    // or if user status is not in the list (array $status)
    function auth($status, $return_location = '/') {
        global $ALL_STATUS;
        $status_n = array_search($_SESSION['status'], $ALL_STATUS);
        
        if (
            (is_int($status) && $status_n < $status) ||
            (is_array($status) && !in_array($_SESSION['status'], $status)) ||
            (is_string($status) && $_SESSION['status'] != $status)
        ) {
            header('Location: ' . $return_location);
            exit();
        }
    }


/***************************************************/
/* !Text Functions */
/***************************************************/

    function safeFileName($filename) {
        //$filename = strtolower($filename);
        $filename = str_replace(array("#", " ", "__"),"_",$filename);
        $filename = str_replace(array("'", '"', "\\", "?"),"",$filename);
        //$filename = str_replace("/","_",$filename);
        return $filename;
    }
    
    function cleanTags($tags) {
        $tagArray = (is_array($tags)) ? $tags : explode(';', $tags);
        $tagArray = array_filter($tagArray, strlen);                    // get rid of blank tags
        foreach ($tagArray as $i => $t) {
            $t = trim($t);
            $t = str_replace(array('"', "'", "\\"), '', $t);
            $t = str_replace(' ', '_', $t);
            $t = my_clean($t);
        
            $tagArray[$i] = $t;
        }
        
        return $tagArray;
    }
    
    // set a variable to something if it is empty (not set, or equal to 0, false or '')
    function ifEmpty(&$var, $value='', $strict=false) {
        if (empty($var) && (!$strict || ($var!==0 && $var !=='0' && $var !== FALSE))) { 
            $var = $value;
            return $value;
        } else {
            return $var;
        }
    }
    
    function buttonstyle($text_link) {
        $b = '<div class="buttons">';
        
        foreach($text_link as $t => $l) {
            $b .= '<a href="' . $l . '">' . loc($t) . '</a>';
        }
        
        $b .= '</div>';
        
        return $b;
    }
    
    function linkList($links, $class = '', $list_type = 'ul', $bigbutton_classes = null) {
        $l = "<$list_type class='$class'>" . ENDLINE;
        $c = 0;
        foreach ($links as $href => $link) {
            if ('bigbuttons' == $class) {
                $number_of_colors = 2;  // number of cycling colors from stylesheet
                $c = ($c % ($number_of_colors-1)) + 1;
                if (is_array($bigbutton_classes) && isset($bigbutton_classes[$href])) {
                    $biginit = '&nbsp;&nbsp;&nbsp;&nbsp;';
                    $biginitclass = $bigbutton_classes[$href];
                    $l .= "<li><a class='color$c $biginitclass' href='$href'>$link</a></li>";
                } else {
                    $biginitclass = '';
                    $initials = explode(' ', str_replace('-', ' ', $link));
                    if (count($initials) == 1) {
                        $biginit = strtoupper(substr($link, 0, 1)) . strtolower(substr($link, 1, 1));
                    } else {
                        $biginit = strtoupper(substr($initials[0], 0, 1)) . strtolower(substr($initials[1], 0, 1));
                    }
                    $l .= "<li><a class='color$c' href='$href'>$link<br /><span class='biginit $biginitclass'>$biginit</span></a></li>";
                }
                
            } else {
                $l .= (is_integer($href)) ? "   <li>$link</li>" : " <li><a href='$href'>$link</a></li>";
            }
            $l .= ENDLINE;
        }
        $l .= "</$list_type>" . ENDTAG;
        
        return $l;
    }
    
    function htmlArray($array) {
        echo '<dl>' . ENDLINE;
        if (is_array($array)) {
            foreach ($array as $k => $v) {
                echo '<dt>' . $k . '</dt><dd>';
                if (is_array($v)) {
                    htmlArray($v);
                } else {
                    echo $v;
                }
                echo '</dd>'. ENDLINE;
            }
        } else {
            echo $array;
        }
        echo '</dl>' . ENDLINE;
    }
    
    function array_to_table($array, $header = true, $sortable=false, $rotate=false) {
        if (empty($array)) return false;
        
        if ($rotate) {
            $array = rotate_array($array);
        }
        
        $sort = ($sortable) ? ' sortable' : '';
        $return = '<table class="query' . $sort . '">' . PHP_EOL;
        
        // table header
        if ($header) {
        $return .= '<thead><tr>';
            $keys = array_keys($array);
            foreach ($array[$keys[0]] as $h => $v) {
                $return .= "    <th>$h</th>" . PHP_EOL;
            }
            $return .= '</tr></thead>';
        }
        
        // table data
        $return .= "<tbody>";
        foreach($array as $a) {
            $return .= "<tr>";
            foreach ($a as $v) {
                $return .= "    <td>$v</td>" . PHP_EOL;
            }
            $return .= '</tr>' . PHP_EOL;
        }
        $return .= "</tbody>";
        
        $return .= '</table>' . PHP_EOL . PHP_EOL;
        
        // add sorting script
        if ($sortable) $return .= '<script src="/include/js/sorttable.js"></script>' . PHP_EOL;
        
        return $return;
    }
    
    function multiFaces() {
        // return a randomised order of faces for the background
        
        $ethnicities = array('mx', 'af', 'ea', 'wa', 'wh');
        shuffle($ethnicities);
        $sexes = array('male', 'female');
        shuffle($sexes);
        
        $output = "<div class='$sexes[0]_$ethnicities[0]' id='logo'></div>" . ENDLINE;
        //. '<div style="width:140px;height:120px;float:left;"></div>' . ENDLINE;
        
        return $output;
    }
    
    function cleanData($unclean_array, $var, $valid_values, $default = '') {
        if (isset($unclean_array[$var]) && in_array($unclean_array[$var], $valid_values)) return $unclean_array[$var];
        return $default;
    }
    
    // parse paragraphs for html display
    function parsePara($p) {
        $ul = false;
        $return = "";
        
        $split = preg_split('/[\n\r]{3,}/', $p);
        
        foreach($split as $subp) { 
            if ("**NOTRANS**"==substr($subp,0,11)) {
                if ($ul)  { $ul = false; $return .= "</ul>" . ENDLINE; }
                $return .= substr($subp,11) . ENDLINE; 
            } else if ("**GRAPH" == substr($subp,0,7)) {
                if ($ul)  { $ul = false; $return .= "</ul>" . ENDLINE; }
                $return .= $subp;
            } elseif ("<"==substr($subp,0,1)) {
                if ($ul)  { $ul = false; $return .= "</ul>" . ENDLINE; }
                $return .= loc($subp) . ENDLINE;
            } elseif ("*"==substr($subp,0,1)) {
                if (!$ul)  { $ul = true; $return .= "<ul>" . ENDLINE; }
                
                $subsplit = preg_split('/[\n\r]{1,}/', $subp);
                foreach($subsplit as $subsubp) {
                    if ("*"==substr($subsubp,0,1)) {
                        $return .= "    <li class='new'>" . loc(substr($subsubp,1)) . "</li>" . ENDLINE;
                    } else {
                        $return .= "    <li>" . loc($subsubp) . "</li>" . ENDLINE;
                    }
                }
            } else { 
                if ($ul)  { $ul = false; $return .= "</ul>" . ENDLINE; }
                $return .= "<p>" . loc($subp) . "</p>" . ENDLINE;
            }
        }
        if ($ul)  { $ul = false; $return .= "</ul>" . ENDLINE; }
        
        return $return;
    }
    
/***************************************************/
/* !Image Functions */
/***************************************************/
    
    // display images in a table
    function imgTable($images, $caption='', $class='', $id='') {
        echo "<table class='imgTable $class'";
        if (!empty($id)) echo " id='$id'";
        echo ">\n";
        if (!empty($caption)) {
            #echoTag($caption, "caption");
            echo sprintf("<tr><th colspan='%d'>%s</th></tr>\n", count($images), $caption);
        }
        echo "  <tr>\n";
        foreach ($images as $url => $label) {
            echo "      <td><img src='$url' alt='$label' /></td>\n";
        }
        echo "  </tr>\n";
        echo "  <tr class='imgLabels'>\n";
        foreach ($images as $url => $label) {
            echo "      <th>$label</th>\n";
        }
        echo "  </tr>\n";
        echo "</table>\n\n";
    }
    
    // extract the commmon path for an array of paths
    function common_path($imagelist) {
        $paths = explode('/', $imagelist[0]);
        $common_path = array();
        foreach ($paths as $n => $p) {
            if (!empty($p)) {
                $search_path = implode('/', array_slice($paths, 0, $n+1));
                $check = true;
                foreach($imagelist as $i) {
                    if (strpos($i, $search_path) !== 0) $check = false;
                }
                if ($check) $common_path[] = $p;
            }
        }
        return '/' . implode('/', $common_path) . '/';
    }
    
/***************************************************/
/* !Localisation Functions */
/***************************************************/
 
    function loc($string) {
        // if the language is set to English, return the string
        return $string;
        
        // else, check if the translation is available
        
        // if available, return the translation
        
        // if not available
    }
    
    function tag($string, $tag='p', $attributes='') {
        $tagged_string = "<$tag $attributes>" . loc($string) . "</$tag>" . ENDTAG;
        return $tagged_string;
    }

/***************************************************/
/* !Statistical Functions */
/***************************************************/
 
    function apa_round($x) {
        // round a value to APA-style number of significant digits
        $y = abs($x);
        
        if ($y>100) return round($x, 0);
        if ($y>10) return round($x, 1);
        if ($y>0.1) return round($x, 2);
        if ($y>0.001) return round($x, 3);
        return $x;
    }
    
    function getOnlineUsers(){  
        $d = dir("/var/tmp");
        $count = 0;
        while (false !== ($f = $d->read())) {
            if ("sess_" == substr($f, 0, 5)) {  
                $count++; 
            }
        }
        return $count;  
    }
    
/***************************************************/
/* !Other Functions */
/***************************************************/

    // check if user has permission to access an item
    function permit($type, $id) {
        if ($type=='set') $type = 'sets';
        if (!in_array($type, array('exp', 'quest', 'sets', 'project'))) return false;
        
        // admins have access to everything
        if ($_SESSION['status'] == 'admin') return true;
        
        $id = intval($id);
        $user_id = intval($_SESSION['user_id']);
        $query = new myQuery(
             "SELECT user_id
                FROM access 
               WHERE type='$type' 
                 AND id=$id 
                 AND (user_id=$user_id OR
                   user_id IN (SELECT supervisee_id FROM supervise WHERE supervisor_id=$user_id)
                 )"
        );
        return $query->get_num_rows();
    }
    
    // human-readable file permissions
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

    // finish a script, return values as json (or html), optionally close out the buffer
    function scriptReturn($return, $buffer = false, $json = true) {
        if ($buffer) {
            // start and end user output so this can happen without the user waiting
            ob_end_clean();
            header("Connection: close");
            ignore_user_abort(); // optional
            ob_start();
        }
        
        if ($json) {
            header('Content-Type: application/json');
            echo json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
        } else {
            header('Content-Type: text/html');
            echo htmlArray($return);
        }
        
        if ($buffer) {
            $size = ob_get_length();
            header("Content-Length: $size");
            ob_end_flush();     // Strange behaviour, will not work
            flush();            // Unless both are called !
        }
    }
    
    // make a list of tag words sized according to frequency
    // tags = array of word => frequency pairs
    function tagList($tags, $minsize = 50) {
        if (!empty($tags)) {
            ksort($tags);
        
            $maxtag = max($tags);
            $mintag = min($tags);
            $taglist = array();
        
            foreach($tags as $tag => $n) {
                $size = ($maxtag == $mintag) ? $minsize : $minsize + ( (100-$minsize) * (($n-$mintag) / ($maxtag-$mintag)) );
                $taglist[] = "  <li style='font-size: $size%;'><a href='javascript:showTags(\"$tag\");' title='$n images'>" 
                    . str_replace(" ", "&nbsp;", $tag) . "</a>\n";
            }
            return $taglist;
        } else {
            return array();
        }
    }
    
    // check if ID is valid (i.e. a positive integer)
    function validID($id) {
        if (empty($id)) return false;
        if (!is_numeric($id)) return false;
        if ($id<1) return false;
        $x = trim($id, '0123456789');
        if (empty($x)) return true; 
        return false;
    }
    
    // check if a variable is of a certain type and (optionally) in an array of possible values, else return NULL for mySQL
    function check_null($var, $format = array()) {
        if (is_array($format)) {
            if (in_array($var, $format)) { return $var; }
        } else if ('numeric' == $format) {
            if (is_numeric($var)) { return $var; }
        } else if ('id' == $format) {
            if (validID($var)) { return $var; }
        } else if ('integer' == $format) {
            if (is_integer($var)) { return $var; }
        }
        
        return 'null';
    }
    
    // rotate an array
    function rotate_array($array) {
        $rotated_array = array();
        foreach ($array as $row => $a) {
            foreach ($a as $header => $value) {
                $rotated_array[$header][0] = $header;
                $rotated_array[$header][$row+1] = $value;
            }
        }
        return $rotated_array;
    }
    
    // display a navigation bar for ordered content
    function navBar($back, $backurl, $home, $homeurl, $next, $nexturl) {
        echo "<ul class='navBar'>\n";
        if ($back) echo "   <li class='back'><a href='$backurl'>$back</a></li>\n";
        if ($home) echo "   <li class='home'><a href='$homeurl'>$home</a></li>\n";  
        if ($next) echo "   <li class='next'><a href='$nexturl'>$next</a></li>\n";
        echo "</ul>\n\n";
    }
    
    // display a citation
    function apaCite($tags, $authors, $title, $year, $journal, $volume="", $issue="", $pages="") {
        // authors
        $citation = "       <span class='authors'>";
        if (is_array($authors)) {
            $lastauthor = array_pop($authors);
            $citation .= implode(", ", $authors);
            $citation .= " &amp; $lastauthor";
        } else {
            $citation .= $authors;
        }
        $citation .= "</span>\n";
        
        // year
        $citation .= "      <span class='year'>($year).</span>\n";
        
        // title
        $punctuation = array(".", "!", "?");
        if (!in_array(substr($title, -1), $punctuation)) $title .= ".";  
        $citation .= "      <span class='title'>$title</span>\n";
        
        // journal
        if (!$volume) {
            $journal .= ".";
        } else {
            $journal .= ",";
        }
        $citation .= "      <span class='journal'>$journal</span>\n";
        
        // volume, issue and pages
        if ($volume) {
            if (!$issue && $pages) $volume .= ":";
            $citation .= "      <span class='volume'>$volume</span>\n";
            if ($issue) {
                if ($pages) $issue .= ":";
                $citation .= "      <span class='issue'>($issue)</span>\n";
            }
            if ($pages) {
                $citation .= "      <span class='pages'>$pages.</span>\n";
            } else {
                $citation .= ".";
            }
        }
        
        if ($tags) $citation  = "   <$tags>\n$citation\n    </$tags>\n";
        
        return $citation;
    }
    
    function duplicateTable($table, $type, $old_id, $new_id) {
        
        $q = new myQuery("SELECT * FROM $table WHERE {$type}_id={$old_id}");
        $old_data = $q->get_assoc();
        if (count($old_data) > 0) {
            unset($old_data[0]["{$type}_id"]);
            $fields = array_keys($old_data[0]);
            $query = sprintf("INSERT INTO {$table} ({$type}_id, %s) SELECT {$new_id}, %s FROM {$table} WHERE {$type}_id={$old_id}",
                implode(", ", $fields),
                implode(", ", $fields)
            );
            $q = new myQuery($query);
        }
        
        return $q->get_affected_rows();
    }

?>