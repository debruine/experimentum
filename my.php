<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(1);

/****************************************************
 * AJAX Responses
 ***************************************************/

if (array_key_exists('check_username', $_GET)) {
    // check if the username is already taken
    
    $username = my_clean($_POST['username']);

    $query = new myQuery('SELECT user_id FROM user WHERE username="'.$username.'" AND user_id!='.$_SESSION['user_id'].' LIMIT 1');
    if (0 == $query->get_num_rows()) {
        // no other users have this username
        echo 'OK';
    }
    exit;
}


if (array_key_exists('update', $_GET)) {
    // update my information
    
    $clean = my_clean($_POST);
    
    if (!empty($clean['firstname']) && !empty($clean['lastname']) && !empty($clean['email']) ) {
        $q = sprintf('REPLACE INTO res (user_id, firstname, lastname, email) VALUES (%d, "%s", "%s", "%s")',
            $_SESSION['user_id'],
            $clean['firstname'],
            $clean['lastname'],
            $clean['email']
        );
        
        $query = new myQuery($q);
        
            
        if (0 == $query->get_affected_rows()) {
            echo loc('Something went wrong with the request.<br>');
        } else if (in_array($_SESSION['status'], $RES_STATUS)) {
            echo loc('Your researcher information has been updated.<br>');
        } else {
            echo loc('Your researcher status request has been sent.<br>');
        }
    }
    
    // set password
    $new_password = 'password';
    if ($clean['newpassword'] == $clean['newpassword2'] && strlen($clean['newpassword']) > 4) {
        $new_password = '"'.md5($clean['newpassword']).'"';
    }
    
    $q = sprintf('UPDATE user SET 
        username="%s",
        password=%s,
        sex="%s",
        birthday="%s"
        WHERE user_id="%d"',
        $clean['username'],
        $new_password,
        $clean['sex'],
        $clean['year'] . "-" . $clean['month'] . "-" . $clean['day'],
        $_SESSION['user_id']
    );
    
    $query = new myQuery($q);
    
    if (0 == $query->get_affected_rows()) {
        echo loc('No account information was changed');
    } else {
        // reset session variables
        $_SESSION['sex']        = $clean['sex'];
        if (!empty($clean['username'])) {
            $_SESSION['username']   = $clean['username'];   
        }
        
        echo loc('Your account information has been updated');
    }

    
    session_write_close();
    exit;

}

/****************************************************
 * Set up forms
 ***************************************************/
 
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';

// get my data
$query = new myQuery('SELECT user.*, res.*,
    COUNT(login.id) as logins
    FROM user 
    LEFT JOIN res USING (user_id)
    LEFT JOIN login USING (user_id)
    WHERE user.user_id=' . $_SESSION['user_id'] .
    ' GROUP BY user.user_id LIMIT 1'
);

$mydata = $query->get_assoc(0);

$login_info = sprintf(loc('You first registered on %s and have logged in %d times.'),
    $mydata['regdate'],
    $mydata['logins']
);

$input = array();

// username
$input['username'] = new input('username', 'username', $mydata['username']);
$input['username']->set_question('Username<span class="note"><a href="javascript:newpwd();">change your password</a></span>');
$input['username']->set_maxlength(100);
$input['username']->set_width(200);

// new password
$input['newpassword'] = new input('newpassword', 'newpassword');
$input['newpassword']->set_question('New Password');
$input['newpassword']->set_type('password');
$input['newpassword']->set_width(200);

// confirm new password
$input['newpassword2'] = new input('newpassword2', 'newpassword2');
$input['newpassword2']->set_question('Confirm New Password');
$input['newpassword2']->set_type('password');
$input['newpassword2']->set_width(200);

// sex
$input['sex'] = new select('sex', 'sex', $mydata['sex']);
$input['sex']->set_question('Gender');
$input['sex']->set_options(array(
	'male' => loc('male'),
	'female' => loc('female'),
	'nonbinary' => loc('non-binary'),
	'na' => loc('prefer not to answer')
));

// birthday
$bday_parts = explode('-', $mydata['birthday']);
$input['birthday'] = new formElement('birthday', 'birthday');
$input['birthday']->set_question('Birthday <small>Year required for some<br>age-restricted studies</small>');
$year = new selectnum('year','year',$bday_parts[0]);
$year->set_options(array(0 => "----"), date("Y") - 5, date("Y") - 120);
$year->set_null(false);
$month = new select('month','month',$bday_parts[1]);
$month->set_options(array(
    0 => "---------",
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
$day = new selectnum('day','day',$bday_parts[2]);
$day->set_options(array(0=>"--"), 1, 31);
$day->set_null(false);
$input['birthday']->set_custom_input( $year->get_element() . ' ' .
                                      $month->get_element() . ' ' .
                                      $day->get_element() );
                                      
// firstname
$input['firstname'] = new input('firstname', 'firstname', $mydata['firstname']);
$input['firstname']->set_question('First Name');
$input['firstname']->set_maxlength(100);
$input['firstname']->set_width(200);

// lastname
$input['lastname'] = new input('lastname', 'lastname', $mydata['lastname']);
$input['lastname']->set_question('Last Name');
$input['lastname']->set_maxlength(100);
$input['lastname']->set_width(200);

// email
$input['email'] = new input('email', 'email', $mydata['email']);
$input['email']->set_question('Email Address');
$input['email']->set_maxlength(255);
$input['email']->set_width(200);


// set up form table
$q = new formTable();
$q->set_table_id('myInformation');
$q->set_title('My Information');
$q->set_action('');
$q->set_questionList($input);
$q->set_method('post');
$buttons = array('Update My Information' => 'updateInfo();');
if (!in_array($_SESSION['status'], $RES_STATUS)) {
    $buttons['Request Researcher Status'] = 'requestres();';
}

$q->set_buttons($buttons);
$q->set_button_location('bottom');

/****************************************************/
/* !Display Page */
/***************************************************/

$title = array('/my' => loc('My Account'));
$page = new page($title);
$page->set_menu(true);

$page->displayHead();
$page->displayBody();

echo '<p id="response" style="display:none;"></p>' , ENDLINE;

$q->print_form();

echo "<p>$login_info</p>", ENDLINE;


/****************************************************
 * Javascripts for this page
 ***************************************************/

?>

<script>

    $(function() {
        newpwd();
        
        if ($('#firstname').val() == "" & 
            $('#lastname').val() == "" &
            $('#email').val() == "" ) {
            $('#firstname_row').hide();
            $('#lastname_row').hide();
            $('#email_row').hide();
        }
        
        $('#response').addClass('error').click( function() {
            $(this).hide('slide', { direction: 'left' }, 500);
        });
    });

    function newpwd() {
        $('#newpassword_row').toggle();
        $('#newpassword2_row').toggle();
        $('#newpassword').val('');
        $('#newpassword2').val('');
    }
    
    function requestres() {
        var vis = $('#firstname_row:visible').length;
        
        if (!vis) {
            $('#firstname_row').show();
            $('#lastname_row').show();
            $('#email_row').show();
        } else if ($('#firstname').val() != "" & 
                   $('#lastname').val() != "" &
                   $('#email').val() != "" ) {
            updateInfo();
        } else {
            $('#firstname_row').hide();
            $('#lastname_row').hide();
            $('#email_row').hide();
        }
    }
        
    function updateInfo() {
        // run checks for required information
        $('#response').html('Verifying information...').show();
        
        // set all questions to default black
        $('#myInformation tr').css('color', 'black');
        
        // check that all required information is included
        var problem = false;

        if ('' == $('input[name=sex]').val() || 'NULL' == $('input[name=sex]').val()) {
            problem = true;
            $('#sex_row').css('color', 'red');
            $('#response').html('<?= loc('Please fill in all information marked in red'); ?>');
        }
        
        // check passwords only if one newpasword field is filled in
        if ('' != $('#newpassword').val() || '' != $('#newpassword2').val()) {
            // check for matching passwords
            if ($('#newpassword').val() != $('#newpassword2').val()) {
                problem = true;
                $('#newpassword_row').css('color', 'red');
                $('#newpassword2_row').css('color', 'red');
                $('#response').html( '<?= loc('The new passwords do not match'); ?>' );
            }
            // check password length is >4 characters
            if ($('#newpassword').val().length < 5 || $('#newpassword2').val().length < 5) {
                problem = true;
                $('#newpassword_row').css('color', 'red');
                $('#newpassword2_row').css('color', 'red');
                $('#response').html( '<?= loc('Passwords must be more than 4 characters long'); ?>' );
            }
        }
        
        // if username is present and changed, check that the username is not already taken
        if ($('#username').length) {
            $.ajax({
                url: '?check_username',
                type: 'POST',
                data: $('#myInformation_form').serialize(),
                success: function(data) {
                    if ('OK' !== data) {
                        // new username is taken
                        problem = true;
                        $('#response').html( '<?= loc('That username is already taken. Please choose another.'); ?>' );
                    }
                }
            });
        }
        
        if (problem) {
            return false;
        } else {
            // everything is OK, continue with update
            $('#response').html('<?= loc('Updating your information...'); ?>');
            
            $.ajax({
                url: '?update', 
                type: 'POST',
                data: $('#myInformation_form').serialize(),
                success: function(data) {
                    $('#response').html( data );
                    if (data == 'Your information has been updated') {
                        $('#header_username a').html($('#username').val());
                    }
                }
            });
        }
    }

</script>

<?php

$page->displayFooter();

?>