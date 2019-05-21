<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

// clear sets so you don't get stuck when testing
unset($_SESSION['set_list']);
unset($_SESSION['set_item_number']);

$title = loc('Researchers');

$styles = array(
    '.bigbuttons li' => 'position: relative;',
    '.bigbuttons li input' => 'width: 60%; 
                               display: block; 
                               position: absolute; 
                               top: 2.5em; left: 20%; 
                               color: white;
                               background-color: hsla(0, 0%, 100%, 20%);
                               border: 2px solid white;
                               text-align: center;',
    '.bigbuttons li input:focus' => 'box-shadow: none;',
    '.bigbuttons li a.study1' => 'background-image: url("/images/linearicons/smile?c=FFFFFF");'
);

$links = array(
    '/res/exp/'      => 'Experiments',
    '/res/quest/'    => 'Questionnaires',
    '/res/set/'      => 'Sets',
    '/res/project/'  => 'Projects',
    '/res/lab/'      => 'Labs',
    '/res/stimuli/'  => 'Stimuli',
    '/res/psa1'      => 'Study 1'
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
    '/res/admin/'    => 'admin',
    '/res/lab/'      => 'lab',
    '/res/psa1'      => 'study1'
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

foreach ($dashboard as $attr) {
    $dash .= sprintf('    <li class="%s"><a href="/res/%s/info?id=%s">%s<br>%s</a></li>' . ENDLINE,
        $attr['type'],
        $attr['type'],
        $attr['id'],
        $attr['res_name'],
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
    Browse our <a href="/res/stimuli/browse">open-access stimuli</a> or 
    <a href="/res/stimuli/upload">upload your own stimuli</a>.
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