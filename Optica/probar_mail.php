<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

try {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = getenv('SMTP_HOST') ?: 'localhost';
    $mail->SMTPAuth   = (bool)(getenv('SMTP_USERNAME') && getenv('SMTP_PASSWORD'));
    $mail->Username   = getenv('SMTP_USERNAME') ?: '';
    $mail->Password   = getenv('SMTP_PASSWORD') ?: '';
    $mail->SMTPSecure = getenv('SMTP_ENCRYPTION') ?: '';
    $mail->Port       = (int)(getenv('SMTP_PORT') ?: 25);
    $mail->CharSet    = 'UTF-8';

    $from = getenv('SMTP_FROM') ?: 'no-reply@localhost';
    $to = getenv('SMTP_TEST_TO') ?: $from;
    $mail->setFrom($from, 'Optica SOW');
    $mail->addAddress($to);

    $mail->isHTML(true);
    $mail->Subject = 'Prueba simple';
    $mail->Body    = '<h3>Correo de prueba</h3><p>Si llegó, SMTP funciona.</p>';

    $mail->send();
    echo 'OK: correo enviado';
} catch (Exception $e) {
    echo 'ERROR: ' . $mail->ErrorInfo;
}
