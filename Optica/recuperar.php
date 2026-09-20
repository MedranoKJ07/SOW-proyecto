<?php
// recuperar.php (versión segura)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/vendor/autoload.php';

session_start();
ob_start();
$db = conectarDB();

/* ===== Helpers ===== */
function render_instant_msg($type,$title,$extra=''){
  $cls = $type==='success' ? 'msg-success' : ($type==='info' ? 'msg-info':'msg');
  return '
  <!DOCTYPE html><html lang="es"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mensaje</title>
    <link rel="stylesheet" href="restableces.css?v=1">
    <style>
      body{font-family:Segoe UI,Tahoma,Arial,sans-serif;margin:0;background:#0b1020}
      .wrap{min-height:100vh;display:grid;place-items:center;padding:32px}
      .msg{max-width:720px;background:#fff;border-radius:14px;padding:22px 22px;box-shadow:0 10px 26px rgba(0,0,0,.18);line-height:1.6}
      .msg-success{background:#e6f9f0;border-left:8px solid #10b981;color:#065f46}
      .msg-info{background:#eff6ff;border-left:8px solid #2563eb;color:#1e3a8a}
    </style>
  </head><body><div class="wrap"><div class="msg '.$cls.'">
    <p>'.$title.'</p>'.$extra.'
  </div></div></body></html>';
}

function flush_response_now(){
  @session_write_close();
  @header('Connection: close');
  echo str_repeat(' ', 1024);
  @ob_flush(); @flush();
  if (function_exists('fastcgi_finish_request')) { fastcgi_finish_request(); }
}
/* ===== /Helpers ===== */

if (empty($_SESSION['csrf_rec'])) {
  $_SESSION['csrf_rec'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ident = trim($_POST['ident'] ?? '');
  $csrf  = $_POST['csrf'] ?? '';

  if (!hash_equals($_SESSION['csrf_rec'] ?? '', $csrf)) {
    http_response_code(400);
    echo "<p>Solicitud inválida.</p>";
    exit;
  }

  // Buscar por usuario o email
  $stmt = $db->prepare("SELECT id, usuario, nombre, email FROM usuarios WHERE usuario = ? OR email = ? LIMIT 1");
  $stmt->bind_param("ss", $ident, $ident);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();

  if (!$user) {
    echo render_instant_msg('info', 'ℹ️ Si la cuenta existe, te enviamos un enlace (vigente 30 min).');
    flush_response_now();
    exit;
  }

  // ===== Token =====
  $tokenPlain = bin2hex(random_bytes(32));
  $tokenHash  = hash('sha256', $tokenPlain);
  $expira     = date('Y-m-d H:i:s', time() + (defined('RESET_EXPIRA') ? RESET_EXPIRA : 1800));

  // Guarda el hash y expiración del token
  $upd = $db->prepare("UPDATE usuarios SET reset_token = ?, reset_expira = ? WHERE id = ?");
  $upd->bind_param("ssi", $tokenHash, $expira, $user['id']);
  $upd->execute();

  // Enlace seguro para restablecer
  $link = rtrim(base_url(), '/') . '/restablecer.php?token=' . urlencode($tokenPlain);

  // --- RESPUESTA INMEDIATA (sin mostrar el link) ---
  $correoDestino = trim((string)$user['email']);
  $extra = '';
  if ($correoDestino !== '') {
    $extra .= '<p>Te hemos enviado un enlace de recuperación a <b>'.htmlspecialchars($correoDestino).'</b>. Revisa tu bandeja de entrada o SPAM.</p>';
  }

  echo render_instant_msg('success', '✅ Solicitud recibida', $extra);
  flush_response_now();

  // --- Envío del correo ---
  if ($correoDestino !== '') {
    try {
      $nombreDestino = $user['nombre'] ?: $user['usuario'];
      $mail = new PHPMailer(true);
      $mail->isSMTP();
      $mail->Host       = getenv('SMTP_HOST') ?: 'localhost';
      $mail->SMTPAuth   = (bool)(getenv('SMTP_USERNAME') && getenv('SMTP_PASSWORD'));
      $mail->Username   = getenv('SMTP_USERNAME') ?: '';
      $mail->Password   = getenv('SMTP_PASSWORD') ?: '';
      $mail->SMTPSecure = getenv('SMTP_ENCRYPTION') ?: '';
      $mail->Port       = (int)(getenv('SMTP_PORT') ?: 25);
      $mail->CharSet    = 'UTF-8';
      $mail->setFrom(getenv('SMTP_FROM') ?: 'no-reply@localhost', 'Óptica SOW');
      $mail->addAddress($correoDestino, $nombreDestino);
      $mail->isHTML(true);
      $mail->Subject = 'Restablece tu contraseña';
      $mail->Body = '
        <div style="font-family: Arial, sans-serif; color:#333;">
          <h3 style="color:#2563EB;">Solicitud de restablecimiento de contraseña</h3>
          <p>Hola '.htmlspecialchars($nombreDestino).',</p>
          <p>Haz clic en el siguiente enlace para crear una nueva contraseña (vigente 30 minutos):</p>
          <p><a href="'.htmlspecialchars($link).'" rel="noopener">'.htmlspecialchars($link).'</a></p>
          <p style="font-size:12px;color:#666;">Si no solicitaste este cambio, ignora este correo.</p>
        </div>';
      $mail->AltBody = "Enlace (30 min): $link";
      $mail->send();
    } catch (Exception $e) {
      error_log("Error SMTP reset: ".$e->getMessage());
    }
  }

  exit;
}

// --- GET: mostrar formulario ---
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Recuperar contraseña</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <link rel="stylesheet" href="restableces.css?v=1">
  <style>
    
    :root{--primary:#4e73df;--primary2:#224abe;--bg:#f4f6f9;--text:#2c3e50;--muted:#6b7280;}
    *{box-sizing:border-box}
    body{font-family:Segoe UI,Tahoma,Arial,sans-serif;margin:0;background:var(--bg);color:var(--text)}
    .container{max-width:520px;margin:48px auto;padding:0 16px}
    h2{margin:0 0 10px;font-weight:700}
    p.sub{margin:0 0 18px;color:var(--muted)}
    .card{background:#fff;border-radius:16px;padding:22px 20px;box-shadow:0 6px 18px rgba(0,0,0,.08)}
    label{display:block;margin:12px 0 6px;font-weight:600}
    input[type="text"]{
      width:100%;padding:12px 14px;border:1px solid #18191aff;border-radius:12px;background:#fafbff;
      outline:none;transition:border .2s, box-shadow .2s
    }
    input[type="text"]:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(78,115,223,.18)}
    .btn{
      display:inline-block;padding:12px 18px;border:none;border-radius:12px;font-weight:700;cursor:pointer;
      background:linear-gradient(135deg,var(--primary),var(--primary2));color:#fff;transition:transform .15s, filter .15s
    }
    .btn:hover{transform:translateY(-1px);filter:brightness(1.06)}
    .actions{margin-top:14px;display:flex;gap:12px;align-items:center}
    a.link{color:var(--primary);text-decoration:none}
    a.link:hover{text-decoration:underline}
  </style>
</head>
<body>
  <div class="container">
    <h2>Recuperar contraseña</h2>
    <p class="sub">Ingresa tu <b>usuario o correo</b> y te enviaremos un enlace válido por 30 minutos.</p>

    <form method="post" autocomplete="off" class="card">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf_rec']); ?>">
      <label for="ident">Usuario o correo</label>
      <input id="ident" type="text" name="ident" required>

      <div class="actions" style="margin-top:16px">
        <button type="submit" class="btn">Recuperar contraseña</button>
        <a class="link" href="login.php">Volver al login</a>
      </div>
    </form>
  </div>
</body>
</html>
