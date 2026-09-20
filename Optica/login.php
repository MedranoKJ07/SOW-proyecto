<?php
// login.php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/vendor/autoload.php'; // PHPMailer
require_once __DIR__ . '/includes/auth_helpers.php';

$T0 = microtime(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario      = trim($_POST['usuario'] ?? '');
    $contrasenaIn = $_POST['contrasena'] ?? $_POST['contraseña'] ?? '';
    $contrasenaIn = is_string($contrasenaIn) ? $contrasenaIn : '';

    $db = conectarDB();

    $stmt = $db->prepare("SELECT id, nombre, usuario, email, rol, `contraseña` AS contrasena,
                                 intentos_fallidos, bloqueado_hasta, mfa_email
                          FROM usuarios WHERE usuario = ? LIMIT 1");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $res = $stmt->get_result();
    $u = $res->fetch_assoc();

    $ip = getClientIP();
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    if (!$u) {
        audit_login($db, null, $usuario, false, 'usuario_no_encontrado');
        $error = "Usuario no encontrado.";
    } else {
        if (is_blocked($u)) {
            if ((int)$u['mfa_email'] === 1 && !empty($u['email'])) {
                send_otp_email($db, (int)$u['id'], $u['email'], $u['nombre'] ?: $u['usuario']);
            }
            $resta = max(1, ceil((strtotime($u['bloqueado_hasta']) - time())/60));
            $error = "Cuenta bloqueada por intentos fallidos. Enviamos un código a tu correo. Podrás reintentar en ~{$resta} min o verificar ahora.";
            audit_login($db, (int)$u['id'], $usuario, false, 'usuario_bloqueado');
        } else {
            $guardado = $u['contrasena'];
            $loginOk = false;

            if ($guardado && $guardado[0] === '$') {
                $loginOk = password_verify($contrasenaIn, $guardado);
                if ($loginOk && password_needs_rehash($guardado, PASSWORD_DEFAULT, ['cost' => 10])) {
                    $nuevo = password_hash($contrasenaIn, PASSWORD_DEFAULT, ['cost' => 10]);
                    $up = $db->prepare("UPDATE usuarios SET `contraseña`=? WHERE id=?");
                    $up->bind_param("si", $nuevo, $u['id']);
                    $up->execute();
                }
            } else {
                if ($guardado && hash_equals($guardado, md5($contrasenaIn))) { $loginOk = true; }
                elseif ($guardado && hash_equals($guardado, $contrasenaIn)) { $loginOk = true; }
                if ($loginOk) {
                    $nuevo = password_hash($contrasenaIn, PASSWORD_DEFAULT, ['cost' => 10]);
                    $up = $db->prepare("UPDATE usuarios SET `contraseña`=? WHERE id=?");
                    $up->bind_param("si", $nuevo, $u['id']);
                    $up->execute();
                }
            }

            if ($loginOk) {
               $forzarMFA = false; // no pedir MFA en login normal

                if ($forzarMFA) {
                    send_otp_email($db, (int)$u['id'], $u['email'], $u['nombre'] ?: $u['usuario']);

                    $_SESSION['pending_user_id']  = (int)$u['id'];
                    $_SESSION['pending_user_rol'] = $u['rol'];
                    $_SESSION['pending_user']     = $u['usuario'];
                    $_SESSION['after_login_ip']   = $ip;
                    $_SESSION['after_login_ua']   = $ua;

                    $db->query("UPDATE usuarios SET intentos_fallidos=0, bloqueado_hasta=NULL WHERE id=".(int)$u['id']);
                    audit_login($db, (int)$u['id'], $usuario, true, 'password_ok_mfa_pendiente');

                    header("Location: verificar_mfa.php");
                    exit;
                }

                // Sin MFA → login directo
                session_regenerate_id(true);
                $_SESSION['usuario']    = $u['usuario'];
                $_SESSION['rol']        = $u['rol'];
                $_SESSION['id_usuario'] = $u['id'];

                $upd = $db->prepare("UPDATE usuarios SET intentos_fallidos=0, bloqueado_hasta=NULL,
                                     last_login_at=NOW(), last_login_ip=?, last_login_ua=? WHERE id=?");
                $upd->bind_param("ssi", $ip, $ua, $u['id']);
                $upd->execute();

                audit_login($db, (int)$u['id'], $usuario, true, 'login_ok');

                session_write_close();
                if ($u['rol'] === 'doctor') { header("Location: panel_doctor.php"); }
                elseif ($u['rol'] === 'secretaria') { header("Location: panel_secretaria.php"); }
                elseif ($u['rol'] === 'admin') { header("Location: Administrador/panelAdmin.php"); }
                else { header("Location: index.php"); }
                exit;
            } else {
                $n = (int)$u['intentos_fallidos'] + 1;
                if ($n >= 3) {
    // 1) Bloquear por 15 minutos
    $stm2 = $db->prepare("UPDATE usuarios 
                          SET intentos_fallidos=?, bloqueado_hasta=DATE_ADD(NOW(), INTERVAL 15 MINUTE) 
                          WHERE id=?");
    $stm2->bind_param("ii", $n, $u['id']);
    $stm2->execute();

    // 2) Enviar OTP
    if ((int)$u['mfa_email'] === 1 && !empty($u['email'])) {
        send_otp_email($db, (int)$u['id'], $u['email'], $u['nombre'] ?: $u['usuario']);
    }

    // 3) Preparar flujo MFA en sesión
    $_SESSION['pending_user_id']  = (int)$u['id'];
    $_SESSION['pending_user_rol'] = $u['rol'];
    $_SESSION['pending_user']     = $u['usuario'];
    $_SESSION['after_login_ip']   = $ip;
    $_SESSION['after_login_ua']   = $ua;

    // 4) Redirigir YA a la pantalla para ingresar el código
    session_write_close();
    header("Location: verificar_mfa.php");
    exit;
} else {
    // Mantén tu lógica actual de error y conteo
    $stm2 = $db->prepare("UPDATE usuarios SET intentos_fallidos=? WHERE id=?");
    $stm2->bind_param("ii", $n, $u['id']);
    $stm2->execute();

    $restan = 3 - $n;
    $error = "Contraseña incorrecta. Intentos restantes: {$restan}.";
    audit_login($db, (int)$u['id'], $usuario, false, 'password_incorrecto');
}

            }
        }
    }

    $T1 = microtime(true);
    error_log("LOGIN tiempo_total=" . number_format(($T1 - $T0) * 1000, 1) . "ms usuario={$usuario}");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login - Óptica</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<button class="Btn" onclick="window.location.href='index.php'">
  <div class="sign">
    <svg viewBox="0 0 512 512"><path d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"/></svg>
  </div>
  <div class="text">Inicio</div>
</button>

<div class="main-container">
  <div class="container">
    <div class="heading">Iniciar sesión</div>
    <?php if (isset($error)): ?>
      <div style="color:#b91c1c;background:#fee2e2;border:1px solid #fca5a5;padding:10px;border-radius:8px;text-align:center;margin-top:10px;">
        <?php echo htmlspecialchars($error, ENT_QUOTES,'UTF-8'); ?>
        <?php if (strpos($error,'código')!==false): ?>
          <div style="margin-top:8px">
            <a href="verificar_mfa.php" style="color:#1d4ed8">Ingresar código ahora</a>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <form method="POST" class="form" action="login.php">
      <input required class="input" type="text" name="usuario" placeholder="Usuario" autocomplete="username">
      <input required class="input" type="password" name="contraseña" placeholder="Contraseña" autocomplete="current-password">
      <span class="forgot-password"><a href="recuperar.php">¿Olvidaste tu contraseña?</a></span>	
      <input class="login-button" type="submit" value="Entrar">
    </form>
  </div>
</div>
</body>
</html>
