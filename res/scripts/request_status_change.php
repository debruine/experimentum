<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth($RES_STATUS);

$return = array('error' => false);

date_default_timezone_set('Europe/London');
include DOC_ROOT . '/include/classes/PHPMailer/PHPMailerAutoload.php';

$q = new myQuery();
$q->prepare("SELECT * FROM res
            WHERE user_id = (SELECT supervisor_id FROM res WHERE user_id = ?)", 
            array('i', $_SESSION['user_id']));
$sor = $q->get_one_row();
$q->prepare("SELECT * FROM res WHERE user_id = ?", 
            array('i', $_SESSION['user_id']));
$see = $q->get_one_row();

if (!count($see) || !count($sor)) {
    $return['error'] = "The supervisor could not be found";
    scriptReturn($return);
    exit;
}

// mail reminder to supervisor
$message =  "<html><body style='color: rgb(50,50,50); font-family:\"Lucida Grande, sans-serif\"';>" .
            "<p>Dear {$sor['firstname']} {$sor['lastname']},</p>" .
            "<p>{$see['firstname']} {$see['lastname']} has requested a status change for a " . 
            "<a href='https://exp.psy.gla.ac.uk/res/{$_POST['type']}/info?id={$_POST['id']}'>project</a> " .
            "at Experimentum.</p>\n".
            "<p>Kind regards,<br>Experimentum Admin</p>\n" .
            "</body></html>\n.";

$text_message = "Dear {$sor['firstname']} {$sor['lastname']},\n" .
            "{$see['firstname']} {$see['lastname']} has requested a status change for a " . 
            "project at Experimentum.\n\n" .
            "https://exp.psy.gla.ac.uk/res/{$_POST['type']}/info?id={$_POST['id']}\n\n" .
            "Kind regards,\nExperimentum Admin\n";

$mail = new PHPMailer();    //Create a new PHPMailer instance

$mail->setFrom(ADMIN_EMAIL, ADMIN_NAME);
$mail->addAddress($sor['email'], "{$sor['firstname']} {$sor['lastname']}");
$mail->addAddress($see['email'], "{$see['firstname']} {$see['lastname']}");
$mail->addBCC(ADMIN_EMAIL, ADMIN_NAME);
$mail->Subject = 'Experimentum Project Status Change Request: ' . $_POST['id'];
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