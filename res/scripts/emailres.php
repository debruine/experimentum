<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin', 'res'));

$return = array('error' => false);

date_default_timezone_set('Europe/London');
include DOC_ROOT . '/include/classes/PHPMailer/PHPMailerAutoload.php';

$q = new myQuery();
$q->prepare("SELECT * FROM res WHERE user_id = ?", array('i', $_POST['supervisor_id']));
$sor = $q->get_one_row();
$q->prepare("SELECT * FROM res WHERE user_id = ?", array('i', $_POST['supervisee_id']));
$see = $q->get_one_row();

if (!count($see) || !count($sor)) {
    $return['error'] = "The supervisor or supervisee could not be found";
    scriptReturn($return);
    exit;
}

// mail reminder to supervisor
$message =  "<html><body style='color: rgb(50,50,50); font-family:\"Lucida Grande, sans-serif\"';>" .
            "<p>Dear {$sor['firstname']} {$sor['lastname']} and {$see['firstname']} {$see['lastname']},</p>" .
            "<p>{$sor['firstname']} {$sor['lastname']} has been assigned as " .
            "{$see['firstname']} {$see['lastname']}'s supervisor at Experimentum.</p>\n".
            "<p>Supervisors can view and authorise their supervisee's studies at " .
            "the <a href='https://exp.psy.gla.ac.uk/res/'>Researchers section</a>.</p>" .
            "<p>Supervisees can also create studies at the " .
            "<a href='https://exp.psy.gla.ac.uk/res/'>Researchers section</a> " .
            "and get help at the <a href='https://experimentum-web.slack.com/signup'>Slack forum</a>.</p>" .
            "<p>Kind regards,<br>Experimentum Admin</p>\n" .
            "</body></html>\n.";

$text_message = "Dear {$sor['firstname']} {$sor['lastname']} and {$see['firstname']} {$see['lastname']},\n\n" .
            "{$sor['firstname']} {$sor['lastname']} has been assigned as " .
            "{$see['firstname']} {$see['lastname']}'s supervisor at Experimentum.\n\n".
            "Supervisors can view and authorise their supervisee's studies at " .
            "Researchers section (https://exp.psy.gla.ac.uk/res/).\n\n" .
            "Supervisees can also create studies at the Researchers section " .
            "and get help at the Slack forum (https://experimentum-web.slack.com/signup).\n\n" .
            "Kind regards,\nExperimentum Admin\n";

$mail = new PHPMailer();    //Create a new PHPMailer instance

$mail->setFrom(ADMIN_EMAIL, ADMIN_NAME);
$mail->addAddress($sor['email'], "{$sor['firstname']} {$sor['lastname']}");
$mail->addAddress($see['email'], "{$see['firstname']} {$see['lastname']}");
$mail->addBCC(ADMIN_EMAIL, ADMIN_NAME);
$mail->Subject = 'Experimentum Supervision';
$mail->msgHTML($message);
$mail->AltBody = $text_message;

//send the message, check for errors
if (!$mail->send()) {
   $return['error'] = $mail->ErrorInfo;
} else {
    $return['sor'] = $sor['email'];
    $return['see'] = $see['email'];
}

scriptReturn($return);

?>