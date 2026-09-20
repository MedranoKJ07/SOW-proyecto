<?php
// 👇 Asegura que el autoloader se cargue desde /vendor
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// (resto del archivo igual)
function make_mailer(): PHPMailer {
  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host       = getenv('SMTP_HOST') ?: 'localhost';
  $mail->SMTPAuth   = (bool)(getenv('SMTP_USERNAME') && getenv('SMTP_PASSWORD'));
  $mail->Username   = getenv('SMTP_USERNAME') ?: '';
  $mail->Password   = getenv('SMTP_PASSWORD') ?: '';
  $mail->SMTPSecure = getenv('SMTP_ENCRYPTION') ?: '';
  $mail->Port       = (int)(getenv('SMTP_PORT') ?: 25);
  $mail->CharSet    = 'UTF-8';
  $mail->setFrom(getenv('SMTP_FROM') ?: 'no-reply@localhost', 'Optica SOW');
  return $mail;
}
