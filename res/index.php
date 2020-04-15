<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

// clear sets so you don't get stuck when testing
unset($_SESSION['set_list']);
unset($_SESSION['set_item_number']);

$title = loc('Researchers');

$styles = array();

$links = array(
    '/res/exp/'      => 'Experiments',
    '/res/quest/'    => 'Questionnaires',
    '/res/set/'      => 'Sets',
    '/res/project/'  => 'Projects',
    '/res/stimuli/'  => 'Stimuli',
    '/res/tutorial/'  => 'Tutorial',
);

if (in_array($_SESSION['status'], array('res', 'super', 'admin'))) {
    $links['/res/admin/'] = 'Admin';
}

$class = array(
    '/res/stimuli/'  => 'stimuli',
    '/res/exp/'      => 'exp',
    '/res/quest/'    => 'quest',
    '/res/set/'      => 'set',
    '/res/project/'  => 'project',
    '/res/admin/'    => 'admin',
    '/res/tutorial/' => 'tutorial'
);

$q = new myQuery();
$q->prepare(
    'SELECT IF(d.type="exp", e.res_name, 
                IF(d.type="quest", q.res_name,
                    IF(d.type="set", s.res_name,
                        IF(d.type="project", p.res_name, "Unknown")))) AS res_name, 
            IF(d.type="exp", e.name, 
                IF(d.type="quest", q.name,
                    IF(d.type="set", s.name,
                        IF(d.type="project", p.name, "Unknown")))) AS name, 
            d.id, 
            d.type 
    FROM dashboard AS d
    LEFT JOIN exp AS e ON e.id = d.id AND d.type = "exp"
    LEFT JOIN quest AS q ON q.id = d.id AND d.type = "quest"
    LEFT JOIN sets AS s ON s.id = d.id AND d.type = "set"
    LEFT JOIN project AS p ON p.id = d.id AND d.type = "project"
    WHERE user_id=? 
    ORDER BY type, res_name',
    array('i', $_SESSION['user_id'])
);
$dashboard = $q->get_assoc();
$dash = 'My Pinned Items<ul id="myfavs">' . ENDLINE;
$url = array(
    'query' => '/res/data/',
    'exp' => '/res/exp/info',
    'quest' => '/res/quest/info',
    'set' => '/res/set/info',
    'project' => '/res/project/info'
);
foreach ($dashboard as $attr) {
    $dash .= sprintf('    <li class="%s"><a href="/res/%s/info?id=%s">%s</a></li>' . ENDLINE,
        $attr['type'],
        $attr['type'],
        $attr['id'],
        $attr['res_name']
    );
}
$dash .= '</ul>';

/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();

?>

<div id="dash"><?= $dash ?></div>
<?= linkList($links, 'bigbuttons resbuttons', 'ul', $class) ?>

<script>
    //$("#dash").prependTo('ul.resbuttons');
    
    $('.resbuttons a.exp').closest('li').append('<input data-type="exp" />');
    $('.resbuttons a.quest').closest('li').append('<input data-type="quest" />');
    $('.resbuttons a.set').closest('li').append('<input data-type="set" />');
    $('.resbuttons a.project').closest('li').append('<input data-type="project" />');
    
    $('.resbuttons input').keydown( function(e) {
        if (e.which == 13) {
            var n = this.value;
            var isValid = Math.floor(n) == n && $.isNumeric(n) && n>0;
            if (isValid) {
                var theType = $(this).data('type');
                
                location.href = '/res/' + theType + '/info?id=' + n;
            } else {
                this.value = '';
            }
        }
    });
</script>

<?php

$page->displayFooter();

?>