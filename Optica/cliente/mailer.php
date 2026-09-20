<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../vendor/autoload.php'; // Composer

function enviarCorreoCita($toEmail, $toName, $asunto, $htmlBody, $icsString = null) {
  $mail = new PHPMailer(true);
  try {
    // SMTP configurable por entorno; sin SMTP local configurado se registra el error.
    $mail->isSMTP();
    $mail->Host = getenv('SMTP_HOST') ?: 'localhost';
    $mail->SMTPAuth = (bool)(getenv('SMTP_USERNAME') && getenv('SMTP_PASSWORD'));
    $mail->Username = getenv('SMTP_USERNAME') ?: '';
    $mail->Password = getenv('SMTP_PASSWORD') ?: '';
    $mail->SMTPSecure = getenv('SMTP_ENCRYPTION') ?: '';
    $mail->Port = (int)(getenv('SMTP_PORT') ?: 25);

    $mail->setFrom(getenv('SMTP_FROM') ?: 'no-reply@localhost', 'Óptica West');
    $mail->addAddress($toEmail, $toName ?: $toEmail);

    // COPIA a tu correo (opcional)
    // $mail->addBCC('mi_correo_admin@tu-dominio.com');

    $mail->isHTML(true);
    $mail->Subject = $asunto;
    $mail->Body    = $htmlBody;

    // Adjuntar .ics (opcional)
    if ($icsString) {
      $mail->addStringAttachment($icsString, 'cita.ics', 'base64', 'text/calendar');
    }

    $mail->send();
    return true;
  } catch (Exception $e) {
    // Log simple
    error_log('MAIL ERROR: '.$mail->ErrorInfo);
    return false;
  }
}
