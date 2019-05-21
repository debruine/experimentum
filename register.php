<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

/****************************************************
 * AJAX Responses
 ***************************************************/
 
if (array_key_exists('register', $_GET)) {
    $return = array('error' => false);
    $clean = my_clean($_POST);
    
    if ($clean['password'] != $clean['password2']) {
        // check passwords match
        $return['error'] = 'Your passwords do not match';
        scriptReturn($return);
        exit;
    } else if (strlen($clean['password'])<5) {
        // check password length
        $return['error'] = 'Your password must be at least 5 characters long';
        scriptReturn($return);
        exit;
    } else {
        // check username exists
        $username_check = new myQuery("SELECT username FROM user WHERE username='" . $clean['username'] . "' LIMIT 1");
        $username_taken = $username_check->get_assoc();
        if (count($username_taken) > 0) {
            $return['error'] = 'The username ' . $clean['username'] . ' is already being used. Please choose a different username.';
            scriptReturn($return);
            exit;
        }
    }
    
    $birthday = explode('-', $clean['birthday']);

    $user = new user();
    $user->set_username($clean['username']);
    $user->set_sex($clean['sex']);
    $user->set_birthday(intval($clean['year']), intval($clean['month']), intval($clean['day']));
    $user->set_pq_and_a($clean['pquestion'], $clean['panswer']);
    $user->set_status('registered');
    
    $return['user_id'] = $user->register($clean['password']);
    
    if ($return['user_id'] && !empty($clean['firstname']) && !empty($clean['lastname']) && !empty($clean['email']) ) {
        $query = 'REPLACE INTO res (user_id, firstname, lastname, email) VALUES (?, ?, ?, ?)';
        $vals = array(
            'isss',
            $return['user_id'],
            $clean['firstname'],
            $clean['lastname'],
            $clean['email']
        );
        
        $q = new myQuery();
        $q->prepare($query, $vals);
            
        if (0 == $q->get_affected_rows()) {
            $return['error'] = 'Something went wrong with the researcher status request, but your account has been created. <a href="/">Return to the main page.</a>';
        } else {
            $return['msg'] = 'Your researcher status request has been sent.';
            
            $q->prepare("REPLACE INTO supervise (supervisor_id, supervisee_id) VALUES (?, ?)",
                        array("ii", $clean['supervisor'], $return['user_id'])
            );
            
            $q->prepare('SELECT user_id, firstname, lastname, email, 
                                LEFT(MD5(regdate),10) as p
                           FROM res 
                      LEFT JOIN user USING (user_id)
                          WHERE user_id=?',
                        array('i', $clean['supervisor'])
                        );
            $super = $q->get_one_row();
            
            if (isset($super['email']) && $super['email'] != "") {
                
                date_default_timezone_set('Europe/London');
                include DOC_ROOT . '/include/classes/PHPMailer/PHPMailerAutoload.php';

                // mail reminder to supervisor
                $message =  "<html><body style='color: rgb(50,50,50); font-family:\"Lucida Grande\"';>" .
                            "<p>Dear {$super['firstname']} {$super['lastname']},</p>" .
                            "<p>{$clean['firstname']} {$clean['lastname']} ({$clean['email']}) " .
                            "just requested a researcher account at the PSA Experiment interface" .
                            "and listed you as the supervisor.</p>\n".
                            "<p>You can authorise their account at the " .
                            "<a href='https://psa.psy.gla.ac.uk/include/scripts/login?u={$super['user_id']}&p={$super['p']}&url=/res/admin/supervise'>Supervision page</a>. " .
                            "(<b>Do not share this email, as it contains an auto-login link for your account.</b>)</p>\n" .
                            "<p>Kind regards,<br>Lisa DeBruine</p>\n" .
                            "</body></html>\n.";
            
                $text_message = "Dear {$super['firstname']} {$super['lastname']},\n" .
                            "{$clean['firstname']} {$clean['lastname']} ({$clean['email']}) " .
                            "just requested a researcher account at the PSA Experiment interface and listed you as the supervisor.\n\n".
                            "You can authorise their account at:  " .
                            "https://psa.psy.gla.ac.uk/include/scripts/login?u={$super['user_id']}&p={$super['p']}&url=/res/admin/supervise\n\n" .
                            "(Do not share this email, as it contains an auto-login link for your account.)\n\n".
                            "Kind regards,\n" .
                            "Lisa DeBruine";
            
                $mail = new PHPMailer();    //Create a new PHPMailer instance
    
                $mail->setFrom($clean['email'], "{$clean['firstname']} {$clean['lastname']}");
                $mail->addAddress($email, $email);
                $mail->addBCC('lisa.debruine@glasgow.ac.uk', 'Lisa DeBruine');
                $mail->Subject = 'PSA Researcher Status Request: ' . $clean['firstname'] . " " . $clean['lastname'];
                $mail->msgHTML($message);
                $mail->AltBody = $text_message;
                
                //send the message, check for errors
                if (!$mail->send()) {
                   $return['emailerror'] = $mail->ErrorInfo;
                }
            }
        }
    }
    
    scriptReturn($return);
    exit;
}

if (isset($_GET['username'])) {
    $clean = my_clean($_GET);
    
    $username_check = new myQuery("SELECT username FROM user WHERE username='" . $clean['username'] . "' LIMIT 1");
    if ($username_check->get_num_rows() > 0) {
        scriptReturn('taken');
    } else {
        scriptReturn('free');
    }
    exit;
}


/****************************************************
 * Set up forms
 ***************************************************/
 
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';

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
//$input['sex']->set_question('Sex<small><a href="javascript: trans();">Click here if these options don&#39;t apply to you</a></small>');
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
                WHERE status IN ('res','admin')
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
    'Register' => 'register();',
    'Request Researcher Status' => 'requestres();'
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

echo '<p class="alert" id="response" style="display:none;" onclick="this.toggle()"></p>' , ENDLINE;

$q->print_form();

echo '<h3>This website requires cookies to allow you to log in.<br>Registering indicates you agree to this.</h3>';

/****************************************************
 * Javascripts for this page
 ***************************************************/

?>

<script>
        
    $('#firstname_row').hide();
    $('#lastname_row').hide();
    $('#email_row').hide();
    $('#supervisor_row').hide();

    // add username availability check-as-you-type
    $('input[name="username"]').keyup(function() {
        var username = $('input[name="username"]').val();
        if (username.length < 4) {
            $('#username_row .question small').html('must be at least 4 characters');
        } else {
            $.get('/register?username=' + username, function(data) {
            
                if (data == 'taken') {
                    $('#username_row .question small').html(username + ' is already taken');
                } else if (data == 'free') {
                    $('#username_row .question small').html(username + ' is available');
                } else {
                    alert(data);
                }
            });
        }
    });
    
    // password length check-as-you-type (5+ chars)
    $('#password').keyup(function() { password_check(); });
    $('#password2').keyup(function() { password_check(); });

    
    function password_check() {
        if ($('#password').val().length >= 5 && $('#password').val() == $('#password2').val()) {
            // passwords are long enough and match
            $('#password_row .question small').html('');
            $('#password2_row .question small').html('');
        } else {
            if ($('#password').val().length < 5) {
                // password isn't long enough
                $('#password_row .question small').html('must be at least 5 characters');
            } else {
                $('#password_row .question small').html('');
            }
            if ($('#password').val() != $('#password2').val()) {
                // passwords don't match
                $('#password2_row .question small').html('passwords do not match');
            } else {
                $('#password2_row .question small').html('');
            }
        }
    }
    
    // register account
    function register() {
        var theData = $('#myInformation_form').serialize();
        if (theData.indexOf("sex=") == -1) {
            growl('You must select a sex.');
        } else {
            $.ajax({
                url: '?register',
                type: 'POST',
                dataType: 'json',
                data: theData,
                success: function(data) {
                    if (data.error || isNaN(data.user_id)) {
                        alert(data.error);
                    } else {
                        window.location='<?= ifEmpty($_SESSION['return_to'], '/') ?>';
                    }
                }
            });
        }
    }
    
    function requestres() {
        var vis = $('#firstname_row:visible').length;
        
        if (!vis) {
            $('#firstname_row').show();
            $('#lastname_row').show();
            $('#email_row').show();
            $('#supervisor_row').show();
        } else if ($('#firstname').val() != "" & 
                   $('#lastname').val() != "" &
                   $('#email').val() != "" &
                   $('#supervisor').val() != "NULL") {
            register();
        } else {
            $('#firstname_row').hide();
            $('#lastname_row').hide();
            $('#email_row').hide();
            $('#supervisor_row').hide();
        }
    }
    
    function trans() {
        // add transgender questions
        $('#sex_row .question').html('Assigned sex at birth<small><a href="javascript: notrans();">Back</a></small>');
        $('#sex').buttonset("destroy");
        $('#sex').append("<li><input type='radio' name='sex' value='intersex' id='sex_intersex' /> <label for='sex_intersex'>intersex</label></li>");
        $('#sex').buttonset();
        $('#sex_row').after('<tr id="gender_row"><td class="question">Current gender<small>Please be as specific as you want</small></td><td><input type="text" id="gender" name="gender" maxlength="255" autocomplete="off" style="width:<?= $input_width ?>px" /></td></tr>');
        
        stripe('#myInformation tbody');

        //alert("We're interested in all kinds of people at FaceResearch. Please let us know if there is anything we can do to make the website more inclusive.");
    }
    
    function notrans() {
        $('#sex_row .question').html('Sex<small><a href="javascript: trans();">Click here if these options don&#39;t apply to you</a></small>');
        $('#sex').buttonset("destroy");
        $('#sex li:last').remove();
        $('#sex').buttonset();
        $('#gender_row').remove();
        
        stripe('#myInformation tbody');
    }
    
</script>

<?php


$page->displayFooter();

?>