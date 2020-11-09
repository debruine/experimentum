<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';

/****************************************************
 * Set up forms
 ***************************************************/

$input = array();
$input_width = 250;

// username
$input['username'] = new input('username', 'username');
//$input['username']->set_question('Username<span class="note"><a href="javascript:newpwd();">change your password</a></span>');
$input['username']->set_question('Username<small>must be at least 4 characters</small>');

$input['username']->set_maxlength(100);
$input['username']->set_width($input_width);
$input['username']->set_required(true);

// new password
$input['password'] = new input('password', 'password');
$input['password']->set_question('Password<small>must be at least 5 characters</small>');
$input['password']->set_type('password');
$input['password']->set_width($input_width);
$input['password']->set_required(true);

// confirm new password
$input['password2'] = new input('password2', 'password2');
$input['password2']->set_question('Confirm Password<small></small>');
$input['password2']->set_type('password');
$input['password2']->set_width($input_width);
$input['password2']->set_required(true);

// sex
$input['sex'] = new select('sex', 'sex');
$input['sex']->set_question('Gender');
$input['sex']->set_options(array(
    'male' => loc('male'),
    'female' => loc('female'),
    'nonbinary' => loc('non-binary'),
    'na' => loc('prefer not to answer')
));
$input['sex']->set_required(true);       

// birthday
//$input['birthday'] = new dateMenu('birthday', 'birthday');
$input['birthday'] = new formElement('birthday', 'birthday');
$input['birthday']->set_question('Birthday <small>Year required for some age-restricted studies</small>');
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

$input['status'] = new select('status', 'status');
$input['status']->set_question('Type of account');
$input['status']->set_options(array('registered' => 'Participant', 'test' => 'Test', 'res' => 'Researcher'));
$input['status']->set_value('registered');
$input['status']->set_null(false);

/*
// pquestion
$input['pquestion'] = new input('pquestion', 'pquestion');
$input['pquestion']->set_question('Password retrieval question');
$input['pquestion']->set_placeholder('E.g., My first phone number?');
$input['pquestion']->set_maxlength(100);
$input['pquestion']->set_width($input_width);

// panswer
$input['panswer'] = new input('panswer', 'panswer');
$input['panswer']->set_question('Answer to password retrieval question');
$input['panswer']->set_maxlength(100);
$input['panswer']->set_width($input_width);
*/

// firstname
$input['firstname'] = new input('firstname', 'firstname');
$input['firstname']->set_question('First Name');
$input['firstname']->set_maxlength(100);
$input['firstname']->set_width($input_width);

// lastname
$input['lastname'] = new input('lastname', 'lastname');
$input['lastname']->set_question('Last Name');
$input['lastname']->set_maxlength(100);
$input['lastname']->set_width($input_width);

// email
$input['email'] = new input('email', 'email');
$input['email']->set_question('Email Address');
$input['email']->set_maxlength(255);
$input['email']->set_width($input_width);

// supervisor
$q = new myQuery("SELECT user_id, CONCAT(lastname, ', ', firstname) as name 
                FROM res 
                LEFT JOIN user USING (user_id)
                WHERE status IN ('super','admin')
                ORDER BY lastname, firstname");
$supervisors = $q->get_key_val("user_id", "name");
$input['supervisor'] = new select('supervisor', 'supervisor');
$input['supervisor']->set_question('Supervisor');
$input['supervisor']->set_options($supervisors);

// set up form table
$q = new formTable();
$q->set_table_id('myInformation');
$q->set_title('My Information');
$q->set_action('');
$q->set_questionList($input);
$q->set_method('post');
$q->set_buttons(array(
    'Register' => 'register();'
    #'Request Researcher Status' => 'requestres();'
));
$q->set_button_location('bottom');


$title = loc('Register');

$styles = array(
    //'#myInformation input, #myInformation select' => 'font-size: 125%',
    '.question small' => 'color: #666; height: 1em;'
);

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();

echo tag('Participant accounts let you log in to the site in order to anonymously 
    link data across sessions (we never track potentially identifying information 
    like your IP address). Test accounts will record data for testing purposes, but 
    this data will not be included in research. Sign up for a researcher account 
    to create studies on experimentum.');

echo '<p class="alert" id="response" style="display:none;" onclick="this.toggle()"></p>' , ENDLINE;

$q->print_form();

echo '<h3>' . loc('This website requires cookies to allow you to log in.') . 
     '<br>' . loc('Registering indicates you agree to this.') . '</h3>';

/****************************************************
 * Javascripts for this page
 ***************************************************/

?>

<script>
        
    $('#firstname_row').hide();
    $('#lastname_row').hide();
    $('#email_row').hide();
    $('#supervisor_row').hide();

    // show requestres if status is researcher
    $('select[name="status"]').change(function() {
        if ($('select[name="status"]').val() == 'res') {
            $('#firstname_row').show();
            $('#lastname_row').show();
            $('#email_row').show();
            $('#supervisor_row').show();
        } else {
            $('#firstname_row').hide();
            $('#lastname_row').hide();
            $('#email_row').hide();
            $('#supervisor_row').hide();
        }
        stripe('#myInformation tbody');
    });

    // add username availability check-as-you-type
    $('input[name="username"]').keyup(function() {
        var username = $('input[name="username"]').val();
        if (username.length < 4) {
            $('#username_row .question small').html('must be at least 4 characters');
        } else {
            $.ajax({
                url: '/include/scripts/user_checkname',
                type: 'GET',
                dataType: 'json',
                data: {username: username},
                success: function(data) {
                    if (data == 'taken') {
                        $('#username_row .question small').html(username + ' is already taken');
                    } else if (data == 'free') {
                        $('#username_row .question small').html(username + ' is available');
                    } else {
                        alert(data);
                    }
                }
            });
        }
    });
    
    // password length check-as-you-type (5+ chars)
    $('#password').keyup(function() { password_check(); });
    $('#password2').keyup(function() { password_check(); });

    
    function password_check() {
        $('#password_row .question small').html('');
        $('#password2_row .question small').html('');
        
        if ($('#password').val().length < 5) {
            $('#password_row .question small').html('must be at least 5 characters');
        }
        if ($('#password').val() != $('#password2').val()) {
            $('#password2_row .question small').html('passwords do not match');
        }
    }
    
    // register account
    function register() {
        var theData = $('#myInformation_form').serialize();
        if (theData.indexOf("sex=") == -1) {
            growl('You must select a gender.');
        } else {
            $.ajax({
                url: '/include/scripts/user_register',
                type: 'POST',
                dataType: 'json',
                data: theData,
                success: function(data) {
                    if (data.error || isNaN(data.user_id)) {
                        alert(data.error);
                    } else {
                        if (data.msg) { alert(data.msg); }
                        window.location='<?= ifEmpty($_SESSION['return_to'], '/') ?>';
                    }
                }
            });
        }
    }
    
</script>

<?php


$page->displayFooter();

?>