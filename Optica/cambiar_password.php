<?php
session_start();
require_once __DIR__ . '/conexion.php';

if (empty($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }

$db = conectarDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $actual = $_POST['actual'] ?? '';
  $nueva  = $_POST['nueva'] ?? '';
  $repite = $_POST['repite'] ?? '';

  if ($nueva !== $repite) {
    $msg = 'Las contraseñas no coinciden.';
  } elseif (strlen($nueva) < 8) {
    $msg = 'La nueva contraseña debe tener al menos 8 caracteres.';
  } else {
    $stmt = $db->prepare("SELECT `contraseña` AS pass FROM usuarios WHERE id=?");
    $stmt->bind_param("i", $_SESSION['id_usuario']);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();

    if (!$u || !password_verify($actual, $u['pass'])) {
      $msg = 'La contraseña actual no es correcta.';
    } else {
      $hash = password_hash($nueva, PASSWORD_DEFAULT, ['cost'=>10]);
      $upd = $db->prepare("UPDATE usuarios SET `contraseña`=? WHERE id=?");
      $upd->bind_param("si", $hash, $_SESSION['id_usuario']);
      $upd->execute();
      $msg = 'Contraseña actualizada.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Cambiar contraseña</title></head>
<body>
  <h2>Cambiar contraseña</h2>
  <?php if ($msg): ?><p><?php echo htmlspecialchars($msg,ENT_QUOTES,'UTF-8'); ?></p><?php endif; ?>
  <form method="POST">
    <input type="password" name="actual" placeholder="Contraseña actual" required><br>
    <input type="password" name="nueva" placeholder="Nueva contraseña" required><br>
    <input type="password" name="repite" placeholder="Repite nueva contraseña" required><br>
    <button type="submit">Guardar</button>
  </form>
</body>
</html>
