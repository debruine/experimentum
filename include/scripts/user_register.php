<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

$return = array('error' => false);
$clean = my_clean($_POST);

if ($clean['password'] != $clean['password2']) {
    // check passwords match
    $return['error'] = loc('Your passwords do not match');
    scriptReturn($return);
    exit;
} else if (strlen($clean['password'])<5) {
    // check password length
    $return['error'] = loc('Your password must be at least 5 characters long');
    scriptReturn($return);
    exit;
} else {
    // check username exists
    $ucheck = new myQuery();
    $ucheck->prepare("SELECT username FROM user WHERE username=? LIMIT 1",
                      array('s', $clean['username']));
    if ($ucheck->get_num_rows() > 0) {
        $return['error'] = sprintf(loc('The username %s is already being used. Please choose a different username.'),
                                   $clean['username']);
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
if ($clean['status'] == 'test') {
    $user->set_status('test');
} else {
    $user->set_status('registered');
}

$return['user_id'] = $user->register($clean['password']);

if ($return['user_id'] && $clean['status'] == 'res') {
    $query = 'REPLACE INTO res (user_id, firstname, lastname, email, supervisor_id) VALUES (?, ?, ?, ?, ?)';
    $vals = array(
        'isssi',
        $return['user_id'],
        $clean['firstname'],
        $clean['lastname'],
        $clean['email'],
        $clean['supervisor']
    );
    
    $q = new myQuery();
    $q->prepare($query, $vals);
        
    if (0 == $q->get_affected_rows()) {
        $return['error'] = loc('Something went wrong with the researcher status request, but your account has been created.') .
                           ' <a href="/">' . loc('Return to the main page.') . '</a>';
    } else {        
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
                        "just requested a researcher account at Experimentum " .
                        "and listed you as the supervisor.</p>\n".
                        "<p>You can authorise their account at the " .
                        "<a href='https://exp.psy.gla.ac.uk/include/scripts/login?u={$super['user_id']}&p={$super['p']}&url=/res/admin/supervise'>Supervision page</a>. " .
                        "(<b>Do not share this email, as it contains an auto-login link for your account.</b>)</p>\n" .
                        "<p>Kind regards,<br>Experimentum Admin</p>\n" .
                        "</body></html>\n.";
        
            $text_message = "Dear {$super['firstname']} {$super['lastname']},\n" .
                        "{$clean['firstname']} {$clean['lastname']} ({$clean['email']}) " .
                        "just requested a researcher account at Experimentum and listed you as the supervisor.\n\n".
                        "You can authorise their account at:  " .
                        "https://exp.psy.gla.ac.uk/include/scripts/login?u={$super['user_id']}&p={$super['p']}&url=/res/admin/supervise\n\n" .
                        "(Do not share this email, as it contains an auto-login link for your account.)\n\n".
                        "Kind regards,\n" .
                        "Experimentum Admin";
        
            $mail = new PHPMailer();    //Create a new PHPMailer instance

            $mail->setFrom(ADMIN_EMAIL, ADMIN_NAME);
            $mail->addAddress($super['email'], "{$super['firstname']} {$super['lastname']}");
            $mail->addBCC(ADMIN_EMAIL, ADMIN_NAME);
            $mail->Subject = 'Experimentum Researcher Status Request: ' . $clean['firstname'] . " " . $clean['lastname'];
            $mail->msgHTML($message);
            $mail->AltBody = $text_message;
            
            //send the message, check for errors
            if (!$mail->send()) {
               $return['error'] = $mail->ErrorInfo;
            } else {
                $return['msg'] = loc('Your researcher status request has been sent.');
            }
        } else {
            $return['error'] = loc('Your supervisor did not have an email address registered. Your request was logged, but please email them to request activation of your researcher account.');
        }
    }
}

scriptReturn($return);

exit;
?>