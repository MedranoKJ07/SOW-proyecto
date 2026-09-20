<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';        // (si no lo incluyes ya en mailer.php)
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/auth_helpers.php';

if (empty($_SESSION['pending_user_id'])) { header("Location: login.php"); exit; }

$db  = conectarDB();
$uid = (int)$_SESSION['pending_user_id'];

$stmt = $db->prepare("SELECT usuario, nombre, email FROM usuarios WHERE id=? LIMIT 1");
$stmt->bind_param("i", $uid);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

if ($u && !empty($u['email'])) {
  send_otp_email($db, $uid, $u['email'], $u['nombre'] ?: $u['usuario']);
}

header("Location: verificar_mfa.php");
exit;
// Evita reenvíos cada 30s
$chk = $db->prepare("SELECT creado_en FROM mfa_codes WHERE user_id=? ORDER BY id DESC LIMIT 1");
$chk->bind_param("i", $uid);
$chk->execute();
$last = $chk->get_result()->fetch_assoc();
if ($last && (time() - strtotime($last['creado_en'])) < 30) {
  header("Location: verificar_mfa.php?msg=Espera%20unos%20segundos%20antes%20de%20reenviar");
  exit;
}

