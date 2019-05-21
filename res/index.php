<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

// clear sets so you don't get stuck when testing
unset($_SESSION['set_list']);
unset($_SESSION['set_item_number']);

$title = loc('Researchers');

$styles = array(
    '.bigbuttons li a.exp:hover'   => 'background-color: var(--rainbow-red);',
    '.bigbuttons li a.quest:hover' => 'background-color: var(--rainbow-orange);',
    '.bigbuttons li a.set:hover'   => 'background-color: var(--rainbow-yellow);',
    '.bigbuttons li a.project:hover' => 'background-color: var(--rainbow-green);',
    '.bigbuttons li a.stimuli:hover' => 'background-color: var(--rainbow-blue);',
    '.bigbuttons li a.admin:hover' => 'background-color: var(--rainbow-purple);',
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

if (in_array($_SESSION['status'], array('res', 'admin'))) {
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
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();

?>

<div id="dash"><?= $dash ?></div>
<?= linkList($links, 'bigbuttons', 'ul', $class) ?>

<p class="fullwidth" style="clear:both;">You can make new experiments or questionnaires at the 
    <a href="/res/exp/">Experiment</a>  or <a href="/res/quest/">Questionnaire</a> lists above. 
    Chain them together by making new sets at the <a href="/res/set/builder">Set Builder</a>.
    Make a project page with the <a href="/res/project/builder">Project Builder</a> 
    so you can direct participants to your project with a custom URL. 
    Browse our <a href="/res/stimuli/browse">open-access stimuli</a>. <!-- or
    <a href="/res/stimuli/upload">upload your own stimuli</a>.-->
</p>


<script>
    $(function() {
        $("#dash").prependTo('ul.bigbuttons');
        
        $('.bigbuttons a.exp').closest('li').append('<input data-type="exp" />');
        $('.bigbuttons a.quest').closest('li').append('<input data-type="quest" />');
        $('.bigbuttons a.set').closest('li').append('<input data-type="set" />');
        $('.bigbuttons a.project').closest('li').append('<input data-type="project" />');
        
        $('.bigbuttons input').keydown( function(e) {
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
    });
</script>

<?php

$page->displayFooter();

?>