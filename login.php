<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';

$login_input = array();
$input_width = 250;

$login_input['login_username'] = new input('login_username', 'login_username');
$login_input['login_username']->set_question('Username');
$login_input['login_password'] = new input('login_password', 'login_password');
$login_input['login_password']->set_question('Password');
$login_input['login_password']->set_type('password');

// set up form table
$l = new formTable();
$l->set_table_id('login');
$l->set_title('Login');
$l->set_action('');
$l->set_questionList($login_input);
$l->set_method('post');
$l->set_buttons(array(
    'Login' => 'login();',
));
$l->set_button_location('bottom');

/*
// guest login
$input = array();

// sex
$input['sex'] = new select('sex', 'sex');
$input['sex']->set_question('Sex');
$input['sex']->set_options(array(
    'male'=>loc('male'),
    'female'=>loc('female'),
    'nonbinary'=>loc('these options do not apply to me (e.g., nonbinary)'),
    'na' => loc('prefer not to answer')
)); 

// birthday
//$input['birthday'] = new dateMenu('birthday', 'birthday');
$input['birthday'] = new formElement('birthday', 'birthday');
$input['birthday']->set_question('Birthday <small>Year required for some studies</small>');
$year = new selectnum('year','year',0);
$year->set_options(array(0 => "----"), date("Y"), date("Y") - 120);
$year->set_null(false);
$month = new select('month','month',0);
$month->set_options(array(
    0 => "----------",
    1 => loc("January"),
    2 => loc("February"),
    3 => loc("March"),
    4 => loc("April"),
    5 => loc("May"),
    6 => loc("June"),
    7 => loc("July"),
    8 => loc("August"),
    9 => loc("September"),
    10 => loc("October"),
    11 => loc("November"),
    12 => loc("December")
)); 
$month->set_null(false);
$day = new selectnum('day','day',0);
$day->set_options(array(0=>"--"), 1, 31);
$day->set_null(false);

$input['birthday']->set_custom_input( $year->get_element() . ' ' .
                                      $month->get_element() . ' ' .
                                      $day->get_element() );
                                      
$input['msg'] = new msgRow();
$input['msg']->set_custom_input("Some studies are only available to people in a certain age range or of a certain sex,
    so you can optionally fill in that information to be able to participate in those studies.");

// set up form table
$q = new formTable();
$q->set_table_id('guestreg');
$q->set_title('Guest Login');
$q->set_action('');
$q->set_questionList($input);
$q->set_method('post');
$q->set_buttons(array(
    'Guest Login' => 'register();'
));
$q->set_button_location('bottom');
*/


/****************************************************/
/* !Display Page */
/***************************************************/   

$title = 'Login';

$styles = array();

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();

?>
<!-- Modal dialog box for logging in -->

<?php $l->print_form(); ?>

<div id='guestloginbox'>
    <?php //$q->print_form(); ?>
</div>

<div id='login_error' class='ui-state-highlight'></div>

<p>This website requires cookies to allow you to log in. Logging in indicates you agree to this.</p>

<!--****************************************************-->
<!-- !Javascripts for this page -->
<!--****************************************************-->

<script>
    
    $('#login_username, #login_password').blur(function(){
        if ($('#login_username').val() != "" && $('#login_password').val() != "") {
            login();
        }
    }).keyup(function(e) {
        if (e.keyCode == 13) { $(this).blur(); }
    });
    
    
    
    $('#login_username').focus();

</script>

<?php

$page->displayFooter();

?>