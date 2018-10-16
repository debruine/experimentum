<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

// clear sets so you don't get stuck
unset($_SESSION['set_list']);
unset($_SESSION['set_item_number']);
unset($_SESSION['project']);
unset($_SESSION['session']);

/****************************************************/
/* !Display Page */
/***************************************************/   

$title = '';

$styles = array();

if (3 < $_SESSION['status']) {
    # show hidden buttons to researchers
    $styles['.bigbuttons li.hide'] = 'display: block;';
}
$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();

if (isset($_SESSION['status']) && 0 < $_SESSION['status']) {
    echo tag('Text to show when logged in.', 'p', 'class="fullwidth"');

    // get list of featured experiments from project 'mainpage' 1
    
    $exclusions = array();
    if ($_SESSION['age'] > 0) {
        $exclusions['exp'][]    = '(exp.lower_age <= ' . ($_SESSION['age']) . ' OR exp.lower_age IS NULL)'; 
        $exclusions['quest'][]  = '(quest.lower_age <= ' . ($_SESSION['age']) . ' OR quest.lower_age IS NULL)';
        $exclusions['sets'][]   = '(sets.lower_age <= ' . ($_SESSION['age']) . ' OR sets.lower_age IS NULL)';
        $exclusions['exp'][]    = '(exp.upper_age >= ' . ($_SESSION['age']) . ' OR exp.upper_age IS NULL)';
        $exclusions['quest'][]  = '(quest.upper_age >= ' . ($_SESSION['age']) . ' OR quest.upper_age IS NULL)';
        $exclusions['sets'][]   = '(sets.upper_age >= ' . ($_SESSION['age']) . ' OR sets.upper_age IS NULL)';
    } else {
        // only show items with no age limits for people without an age
        $exclusions['exp'][]    = 'exp.lower_age IS NULL AND exp.upper_age IS NULL';
        $exclusions['quest'][]  = 'quest.lower_age IS NULL AND quest.upper_age IS NULL';
        $exclusions['sets'][]   = 'sets.lower_age IS NULL AND sets.upper_age IS NULL';
    }
    
    if ($_SESSION['sex'] == 'male') {
        $exclusions['exp'][]    = '(exp.sex!="female")';
        $exclusions['quest'][]  = '(quest.sex!="female")';
        $exclusions['sets'][]   = '(sets.sex!="female")';
    } else if ($_SESSION['sex'] == 'female') {
        $exclusions['exp'][]    = '(exp.sex!="male")';
        $exclusions['quest'][]  = '(quest.sex!="male")';
        $exclusions['sets'][]   = '(sets.sex!="male")';
    }
    
    $myitems = new myQuery('SELECT item_type, item_id, icon,
                            IF(item_type="exp", exp.name, 
                                IF(item_type="quest", quest.name, 
                                    IF(item_type="set", sets.name, "Mystery Item"))) as name,
                            IF(item_type="exp", exp.status, 
                                IF(item_type="quest", quest.status, 
                                    IF(item_type="set", sets.status, NULL))) as the_status,
                            IF(item_type="exp", exp.lower_age, 
                                IF(item_type="quest", quest.lower_age, 
                                    IF(item_type="set", sets.lower_age, NULL))) as the_lower_age,
                            IF(item_type="exp", exp.upper_age, 
                                IF(item_type="quest", quest.upper_age, 
                                    IF(item_type="set", sets.upper_age, NULL))) as the_upper_age
                            FROM project_items as p
                            LEFT JOIN exp ON (exp.id=item_id) AND item_type="exp" AND ' . implode(' AND ', $exclusions['exp']) . '
                            LEFT JOIN quest ON (quest.id=item_id) AND item_type="quest" AND ' . implode(' AND ', $exclusions['quest']) . '
                            LEFT JOIN sets ON (sets.id=item_id) AND item_type="set" AND ' . implode(' AND ', $exclusions['sets']) . '
                            WHERE p.project_id=1
                            ORDER BY item_n');
                        
    $items = $myitems->get_assoc();
    
    echo '<ul id="featured" class="bigbuttons">';
    $url = array(
        'exp'   => '/exp/exp',      
        'quest' => '/quest/q',
        'set'   => '/include/scripts/set'
    );
    $itemList = array();
    
    foreach ($items as $i) {
        printf('<li id="%s_%s" class="%s"><a class="%s" href="%s?id=%s" style="%s">%s</a></li>' . ENDLINE,
            $i['item_type'],
            $i['item_id'],
            ifEmpty($i['the_status'], 'hide'),
            $i['item_type'],
            $url[$i['item_type']],
            $i['item_id'],
            (!empty($i['icon'])) ? "background-image: url({$i['icon']}@2x)" : "",
            ifEmpty($i['name'], "Hidden: age or sex <span class='corner'>" . $i['item_type'] . "_" . $i['item_id'] . "</span>")
        );
        
        if ($i['the_status'] != 'hide') $itemList[] = '"' . $i['item_type'] . '_' . $i['item_id'] . '"';
    }
    echo '</ul>';
    
} else {
    echo tag('Intro text goes here');
    echo tag('Try the <a href="project?test">Test Studies</a>');
}

?>

<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script>

$(function() {
    
    itemIDs = [ <?php if (is_array($itemList)) { echo implode(',', $itemList); } ?> ];
    
    $.each(itemIDs, function() {
        var theItem = $('#' + this);
        var itemInfo = this.split('_');
        
        $.get('/include/scripts/check?' + itemInfo[0] + '&id=' + itemInfo[1], function(responseText){  
            if ('nodisplay' == responseText) {
                theItem.addClass('hide');
            } else {
                var parsedResponse = responseText.split(";");
                if (1 == parsedResponse[0]) {
                    theItem.addClass('done');
                    theItem.find('a').append($('<span class="corner">Done</span>'));
                }
            }
        });
    });
});

function post_fb() {
    FB.ui(
       {
         method: 'stream.publish',
         message: 'I just signed up to participate in experiments at faceresearch.org.',
         attachment: {
           name: 'FaceResearch',
           caption: 'Experiments about face and voice preferences',
           description: (
             'FaceResearch.org allows you to participate in short ' +
             'online psychology experiments looking at the traits ' +
             'people find attractive in faces and voices.'
           ),
           href: 'http://faceresearch.org'
         },
         user_message_prompt: 'Share your thoughts about FaceResearch'
       },
       function(response) {
         if (response && response.post_id) {
         } else {
           alert('Post was not published.');
         }
       }
     );
}

</script>

<?php

$page->displayFooter();

?>