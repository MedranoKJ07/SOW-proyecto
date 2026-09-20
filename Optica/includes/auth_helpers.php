<?php
// includes/auth_helpers.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cargar configuración del mailer
require_once __DIR__ . '/mailer.php';

/** 🔹 Obtiene la IP real del cliente */
function getClientIP(): string {
  $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
  foreach ($keys as $k) {
    if (!empty($_SERVER[$k])) {
      return explode(',', $_SERVER[$k])[0];
    }
  }
  return '0.0.0.0';
}

/** 🔹 Envía código OTP por email y lo guarda (caduca en 10 minutos) */
function send_otp_email(mysqli $db, int $userId, string $toEmail, string $toName='Usuario'): bool {
  // 🧹 Invalida cualquier OTP anterior no usado
  $kill = $db->prepare("UPDATE mfa_codes SET used=1 WHERE user_id=? AND used=0");
  $kill->bind_param("i", $userId);
  $kill->execute();

  // 🧩 Genera nuevo código
  $code = str_pad(strval(random_int(0, 999999)), 6, '0', STR_PAD_LEFT);
  $stmt = $db->prepare("INSERT INTO mfa_codes (user_id, code, expires_at)
                        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
  $stmt->bind_param("is", $userId, $code);
  $stmt->execute();

  // ✉️ Enviar correo con el código
  $mail = make_mailer();
  try {
    $mail->clearAllRecipients();
    $mail->addAddress($toEmail, $toName);
    $mail->isHTML(true);
    $mail->Subject = 'Tu código de verificación (2 pasos)';
    $mail->Body = "
      <p>Hola {$toName},</p>
      <p>Tu código de verificación es: 
         <b style='font-size:18px;color:#0a0;'>{$code}</b></p>
      <p>Caduca en 10 minutos. Si no fuiste tú, te recomendamos cambiar tu contraseña.</p>";
    $mail->AltBody = "Tu código de verificación es {$code}. Caduca en 10 minutos.";

    return $mail->send();
  } catch (Throwable $e) {
    error_log('EMAIL_MFA_ERROR: '.$e->getMessage());
    return false;
  }
}

/** 🔹 Registra auditoría de inicio de sesión */
function audit_login(mysqli $db, ?int $uid, string $usuario, bool $ok, string $motivo) {
  $ip = getClientIP();
  $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 250);
  $stmt = $db->prepare("INSERT INTO login_audit (user_id, usuario, exito, motivo, ip, user_agent)
                        VALUES (?, ?, ?, ?, ?, ?)");
  $exito = $ok ? 1 : 0;
  $stmt->bind_param("isisss", $uid, $usuario, $exito, $motivo, $ip, $ua);
  $stmt->execute();
}

/** 🔹 Verifica si el usuario está bloqueado temporalmente */
function is_blocked(array $u): bool {
  if (empty($u['bloqueado_hasta'])) return false;
  return strtotime($u['bloqueado_hasta']) > time();
}

/** 🔹 Ubicación aproximada por IP (placeholder para futura API) */
function geo_from_ip(string $ip): string {
  if ($ip === '0.0.0.0') return 'Ubicación desconocida';
  return 'Ubicación aproximada por IP';
}
