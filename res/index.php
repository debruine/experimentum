<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('student', 'researcher', 'admin'));

// clear sets so you don't get stuck when testing
unset($_SESSION['set_list']);
unset($_SESSION['set_item_number']);

$title = loc('Researchers');

$styles = array(
    '.bigbuttons li a.exp'   => 'background-color: hsl(30,100%,25%);',
    '.bigbuttons li a.quest'     => 'background-color: hsl(50,100%,25%);',
    '.bigbuttons li a.set' => 'background-color: hsl(120,100%,15%);',
    '.bigbuttons li a.project' => 'background-color: hsl(200,100%,20%);',
    '.bigbuttons li a.stimuli' => 'background-color: hsl(280,100%,20%);',
    '.bigbuttons li a.admin' => 'background-color: hsl(0,0%,10%);',
    '.bigbuttons li' => 'position: relative;',
    '.bigbuttons li input' => 'width: 60%; 
                               display: block; 
                               position: absolute; 
                               top: 2.5em; left: 20%; 
                               color: white;
                               background-color: hsla(0, 0%, 100%, 20%);
                               border: 2px solid white;
                               text-align: center;',
    '.bigbuttons li input:focus' => 'box-shadow: none;'
);

$links = array(
    '/res/exp/'      => 'Experiments',
    '/res/quest/'    => 'Questionnaires',
    '/res/set/'      => 'Sets',
    '/res/project/'  => 'Projects',
    '/res/stimuli/'  => 'Stimuli',
);

if ($_SESSION['status'] == 'admin') {
    $links['/res/admin/'] = 'Admin';
}

$class = array(
    '/res/stimuli/'  => 'stimuli',
    '/res/exp/'      => 'exp',
    '/res/quest/'    => 'quest',
    '/res/set/'      => 'set',
    '/res/project/'  => 'project',
    '/res/admin/'    => 'admin'
);

$my_dashboard = new myQuery('SELECT name, id, type FROM dashboard WHERE user_id=' . 
                            $_SESSION['user_id'] . ' ORDER BY type, dt DESC');
$dashboard = $my_dashboard->get_assoc();
$dash = 'My Favorites<ul id="myfavs">' . ENDLINE;
$url = array(
    'query' => '/res/data/',
    'exp' => '/res/exp/info',
    'quest' => '/res/quest/info',
    'set' => '/res/set/info',
    'project' => '/res/project/info'
);
foreach ($dashboard as $attr) {
    $dash .= sprintf('    <li class="%s"><a href="%s?id=%s">%s</a></li>' . ENDLINE,
        $attr['type'],
        $url[$attr['type']],
        $attr['id'],
        $attr['name']
    );
}
$dash .= '</ul>';

/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_logo(true);
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();

?>

<div id="dash"><?= $dash ?></div>
<?= linkList($links, 'bigbuttons', 'ul', $class) ?>


<script>
    $j(function() {
        $j("#dash").prependTo('ul.bigbuttons');
        
        $j('.bigbuttons a.exp').closest('li').append('<input data-type="exp" />');
        $j('.bigbuttons a.quest').closest('li').append('<input data-type="quest" />');
        $j('.bigbuttons a.set').closest('li').append('<input data-type="set" />');
        $j('.bigbuttons a.project').closest('li').append('<input data-type="project" />');
        
        $j('.bigbuttons input').keydown( function(e) {
            if (e.which == 13) {
                var n = this.value;
                var isValid = Math.floor(n) == n && $j.isNumeric(n) && n>0;
                if (isValid) {
                    var theType = $j(this).data('type');
                    var theSub = (theType == 'project') ? 'builder' : 'info';
                    
                    location.href = '/res/' + theType + '/' + theSub + '?id=' + n;
                } else {
                    this.value = '';
                }
            }
        });
    });
</script>

<?php

$page->displayFooter();

?>