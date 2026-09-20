<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/auth_helpers.php';

date_default_timezone_set('America/Managua');

if (empty($_SESSION['pending_user_id'])) { 
  header("Location: login.php");
  exit;
}

$db  = conectarDB();
$uid = (int)$_SESSION['pending_user_id'];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $code = trim($_POST['code'] ?? '');

  if (!preg_match('/^\d{6}$/', $code)) {
    $mensaje = "Código inválido.";
  } else {
    // 🔍 Buscar solo el último código válido (no usado, no vencido)
    $stmt = $db->prepare("
      SELECT id, code, expires_at 
      FROM mfa_codes 
      WHERE user_id=? AND used=0 
      ORDER BY id DESC 
      LIMIT 1
    ");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
      $mensaje = "No se encontró un código válido. Solicita uno nuevo.";
    } elseif ($row['code'] !== $code) {
      $mensaje = "Código incorrecto.";
    } elseif (strtotime($row['expires_at']) < time()) {
      $mensaje = "Código vencido. Solicita uno nuevo.";
    } else {
      // ✅ Código correcto → marcar como usado
      $db->query("UPDATE mfa_codes SET used=1 WHERE id=" . (int)$row['id']);

      // Obtener datos del usuario
      $stmtU = $db->prepare("SELECT id, usuario, rol FROM usuarios WHERE id=? LIMIT 1");
      $stmtU->bind_param("i", $uid);
      $stmtU->execute();
      $u = $stmtU->get_result()->fetch_assoc();

      if ($u) {
        // Inicia sesión definitiva
        session_regenerate_id(true);
        $_SESSION['usuario']    = $u['usuario'];
        $_SESSION['rol']        = $u['rol'];
        $_SESSION['id_usuario'] = $u['id'];

        $ip  = $_SESSION['after_login_ip'] ?? getClientIP();
        $ua  = $_SESSION['after_login_ua'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        $geo = geo_from_ip($ip);

        $upd = $db->prepare("
          UPDATE usuarios 
          SET intentos_fallidos=0, bloqueado_hasta=NULL,
              last_login_at=NOW(), last_login_ip=?, last_login_ua=? 
          WHERE id=?
        ");
        $upd->bind_param("ssi", $ip, $ua, $u['id']);
        $upd->execute();

        unset($_SESSION['pending_user_id'], $_SESSION['pending_user_rol'], $_SESSION['pending_user'],
              $_SESSION['after_login_ip'], $_SESSION['after_login_ua']);

        $_SESSION['postlogin_notice'] =
          "Inicio de sesión verificado. Ubicación estimada: {$geo} (IP: {$ip}). ¿Deseas cambiar tu contraseña ahora?";

        if ($u['rol'] === 'doctor') { header("Location: panel_doctor.php"); }
        elseif ($u['rol'] === 'secretaria') { header("Location: panel_secretaria.php"); }
        elseif ($u['rol'] === 'admin') { header("Location: Administrador/panelAdmin.php"); }
        else { header("Location: index.php"); }
        exit;
      } else {
        $mensaje = "No se pudo completar el inicio de sesión.";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Verificación en dos pasos</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="main-container">
    <div class="container">
      <div class="heading">Verificación en dos pasos</div>
      <?php if ($mensaje): ?>
        <div style="color:#b91c1c;background:#fee2e2;border:1px solid #fca5a5;
                    padding:10px;border-radius:8px;text-align:center;margin-top:10px;">
          <?php echo htmlspecialchars($mensaje, ENT_QUOTES,'UTF-8'); ?>
        </div>
      <?php endif; ?>
      <form method="POST" class="form">
        <input class="input" type="text" inputmode="numeric" maxlength="6" minlength="6"
               name="code" placeholder="Código de 6 dígitos" required autofocus>
        <input class="login-button" type="submit" value="Verificar">
      </form>
      <div style="margin-top:10px;text-align:center">
        <a href="reenviar_codigo.php">Reenviar código</a>
      </div>
    </div>
  </div>
</body>
</html>
