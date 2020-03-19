<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

date_default_timezone_set('Europe/London');
include DOC_ROOT . '/include/classes/PHPMailer/PHPMailerAutoload.php';

$return = array(
    'msg' => '',
    'error' => ''
);

$clean = my_clean($_POST);
if (empty($clean)) $clean = array();
$username = ifEmpty($clean['username']);

if (!empty($username)) {
    # done in multiple steps to give more specific feedback if error
    $q = new myQuery("SELECT user_id FROM user WHERE LCASE(username) = LCASE('{$username}') LIMIT 1;");
    
    if ($q->get_num_rows() == 1) {
        # account exists, check for email
        $user_id = $q->get_one();
        $q = new myQuery("SELECT email FROM res WHERE user_id = '{$user_id}' LIMIT 1;");
        if ($q->get_num_rows() == 0) {
            $return['error'] = loc("There is no email address associated with that account.");
        } else {
            $email = $q->get_one();
        }
    } else {
        # not a username, check if it's a researcher email
        $q = new myQuery("SELECT user_id FROM res WHERE LCASE(email)=LCASE('{$username}') LIMIT 1;");
        if ($q->get_num_rows() == 0) {
            $return['error'] = loc("There is no account with that username or email address.");
        } else {
            $user_id = $q->get_one();
            $email = $username;
        }
    }
    
    // create a new password
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789123456789";
    $password = substr(str_shuffle($chars),0,10);
    $q = new myQuery("UPDATE user SET password=MD5('{$password}') WHERE user_id = {$user_id}");
    #if (DEBUG) { $return['newpass'] = $password; } // only for debugging!!!!
    
    // email new password to the user
$message =  "<html><body style='color: rgb(50,50,50); font-family:\"Lucida Grande, sans-serif\"';>" .
            "<p>Hi $email,</p>\n" .
            "<p>You (or someone) just reset your password at <a href='https://exp.psy.gla.ac.uk'>Experimentum</a>.</p>\n\n" .
            "<p>Your new password: $password</a> \n\n" . 
            "<p>You can reset your password after logging in by going to <a href='https://exp.psy.gla.ac.uk/my'>My Account</a>.</p>\n\n" .
            "<p>Kind regards,<br>Experimentum Admin</p>\n" .
            "</body></html>\n.";

$text_message = "Hi $email,\n" .
                "You (or someone) just reset your password at Experimentum (https://exp.psy.gla.ac.uk).\n\n" .
                "Your new password: $password \n\n" . 
                "You can reset your password after logging in by going to My Account (https://exp.psy.gla.ac.uk/my).\n\n" .
                "Kind regards,\nExperimentum Admin\n";

$mail = new PHPMailer();    //Create a new PHPMailer instance

$mail->setFrom(ADMIN_EMAIL, ADMIN_NAME);
$mail->addAddress($email, $email);
$mail->addBCC(ADMIN_EMAIL, ADMIN_NAME);
$mail->Subject = 'Experimentum Password Reset';
$mail->msgHTML($message);
$mail->AltBody = $text_message;
    
    //send the message, check for errors
    if (!$mail->send()) {
       $return['error'] = $mail->ErrorInfo;
    } else {
        # don't return the email address for privacy reasons
        $return['msg'] = loc("An email has been sent to the address associated with account {$user_id} with your new password.");
    }
} elseif (empty($username)) {
    // prompt for username or password if one is missing
    $return['error'] = loc("Please include your username or email address. We can only send you a password reset if you have a student or researcher account with a valid email address. If you have a regular account and need to recover your password for a longitudinal study, please contact the researcher.");
} else {
    // no valid information
    $return['error'] = loc("Sorry, something went wrong.");
}

scriptReturn($return);

?>