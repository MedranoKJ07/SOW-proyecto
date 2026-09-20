<?php
session_start();

// Incluimos configuración y conexión
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

// Validar sesión y rol
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Conexión
$conn = conectarDB();

// Obtener nombre del usuario logueado
$id_usuario = $_SESSION['id_usuario'] ?? 0;
$nombreUsuario = $_SESSION['usuario'];

if ($id_usuario) {
    $stmt = $conn->prepare("SELECT nombre FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $nombreUsuario = $row['nombre'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Administrador</title>
    <link rel="stylesheet" href="dashboard_Admin.css">
    <?php if (!empty($_SESSION['postlogin_notice'])): ?>
  <div style="margin:10px auto;max-width:900px;color:#065f46;background:#d1fae5;border:1px solid #34d399;padding:12px;border-radius:10px">
    <?php echo htmlspecialchars($_SESSION['postlogin_notice'],ENT_QUOTES,'UTF-8'); ?>
    <div style="margin-top:8px">
      <a href="/cambiar_password.php" style="margin-right:10px;">Cambiar contraseña</a>
      <a href="#" onclick="this.parentElement.parentElement.remove();return false;">Continuar</a>
    </div>
  </div>
  <?php unset($_SESSION['postlogin_notice']); ?>
<?php endif; ?>
</head>
<body>
    <a href="<?= BASE_URL ?>logout.php" class="logout-btn">Cerrar sesión</a>
    


    <h1>Bienvenido, <?= htmlspecialchars($nombreUsuario) ?></h1>

    <div class="admin-panel">
        <a class="btn" href="<?= BASE_URL ?>COntabilidad/panel_contabilidad.php">📊 Contabilidad</a>
        <a class="btn" href="<?= BASE_URL ?>Administrador/usuarios.php">👥 Usuarios</a>
        <a class="btn" href="<?= BASE_URL ?>Administrador/proveedores.php">🏭 Proveedores</a>
       <a class="btn" href="<?= BASE_URL ?>Administrador/agenda_admin.php"><i class="fa-solid fa-calendar-check"></i>🗓️ Agenda</a>
</a>

    </div>
</body>
</html>
