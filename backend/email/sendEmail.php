<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php';

function sendEmail($to, $subject, $htmlBody) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'mail.smtp2go.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your_smtp2go_username';
        $mail->Password   = 'your_smtp2go_password';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->setFrom('noreply@findit.com.au', 'FindIt');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
        return false;
    }
}
?>
