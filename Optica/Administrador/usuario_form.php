<?php
// Administrador/usuario_form.php
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

// --- Seguridad ---
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$conn = conectarDB();
if (!$conn) { die('Error de conexión'); }
$conn->set_charset('utf8mb4'); // importante para la ñ

// --- CSRF ---
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf'];

// --- Datos base ---
$id = (int)($_GET['id'] ?? 0);
$editing = $id > 0;
$nombre = $usuario = $email = $rol = '';
$validRoles = ['admin','doctor','secretaria'];

// --- Cargar datos si se edita ---
if ($editing) {
    $stmt = $conn->prepare("SELECT id, nombre, usuario, email, rol FROM usuarios WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows) {
        $u = $res->fetch_assoc();
        $nombre  = $u['nombre'];
        $usuario = $u['usuario'];
        $email   = $u['email'];
        $rol     = $u['rol'];
    } else {
        header('Location: ' . BASE_URL . 'Administrador/usuarios.php');
        exit;
    }
    $stmt->close();
}

$errores = [];
$ok = false;

// --- Guardar cambios ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $errores[] = 'Token inválido, recarga la página.';
    } else {
        $nombre  = trim($_POST['nombre'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $rol     = trim($_POST['rol'] ?? '');
        // 👇 ojo: ahora sí leemos el mismo name del input
        $pass    = trim($_POST['pass'] ?? '');

        if ($nombre === '')  $errores[] = 'El nombre es obligatorio.';
        if ($usuario === '') $errores[] = 'El usuario es obligatorio.';
        if ($email === '')   $errores[] = 'El email es obligatorio.';
        if (!in_array($rol, $validRoles, true)) $errores[] = 'Rol inválido.';

        // Unicidad usuario/email
        if (!$errores) {
            if ($editing) {
                $stmt = $conn->prepare("SELECT COUNT(*) c FROM usuarios WHERE (usuario=? OR email=?) AND id<>?");
                $stmt->bind_param('ssi', $usuario, $email, $id);
            } else {
                $stmt = $conn->prepare("SELECT COUNT(*) c FROM usuarios WHERE usuario=? OR email=?");
                $stmt->bind_param('ss', $usuario, $email);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : ['c'=>0];
            if ((int)$row['c'] > 0) $errores[] = 'Usuario o email ya existen.';
            $stmt->close();
        }

        // Insertar o actualizar
        if (!$errores) {
            if ($editing) {
                if ($pass !== '') {
                    $hash = password_hash($pass, PASSWORD_BCRYPT);
                    // 🔒 usar backticks si tu columna tiene ñ
                    $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, usuario=?, email=?, rol=?, `contraseña`=? WHERE id=?");
                    $stmt->bind_param('sssssi', $nombre, $usuario, $email, $rol, $hash, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, usuario=?, email=?, rol=? WHERE id=?");
                    $stmt->bind_param('ssssi', $nombre, $usuario, $email, $rol, $id);
                }
                $ok = $stmt->execute();
                $stmt->close();
            } else {
                if ($pass === '') $errores[] = 'La contraseña es obligatoria.';
                if (!$errores) {
                    $hash = password_hash($pass, PASSWORD_BCRYPT);
                    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, usuario, email, rol, `contraseña`) VALUES (?,?,?,?,?)");
                    $stmt->bind_param('sssss', $nombre, $usuario, $email, $rol, $hash);
                    $ok = $stmt->execute();
                    $stmt->close();
                }
            }

            if ($ok) {
                header('Location: ' . BASE_URL . 'Administrador/usuarios.php');
                exit;
            } else if (!$errores) {
                $errores[] = 'No se pudo guardar el usuario.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= $editing ? 'Editar usuario' : 'Nuevo usuario' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root{--bg:#f5f7fb;--card:#fff;--text:#1f2937;--border:#e5e7eb;
      --primary:#2563eb;--primaryH:#1d4ed8;}
    body{font-family:Arial,Helvetica,sans-serif;margin:24px;background:var(--bg);color:var(--text);}
    .btn{border:0;border-radius:8px;padding:10px 14px;background:var(--primary);color:#fff;text-decoration:none;cursor:pointer;}
    .btn:hover{background:var(--primaryH);}
    .btn-outline{background:#fff;color:var(--primary);border:1px solid var(--primary);}
    .card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px;max-width:700px;}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    label{font-weight:bold;margin-bottom:6px;display:block;}
    input,select{width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;}
    .errors{background:#fee2e2;border:1px solid #fecaca;color:#7f1d1d;border-radius:10px;padding:10px;margin-bottom:12px;}
  </style>
</head>
<body>

<a class="btn-outline" href="<?= BASE_URL ?>Administrador/usuarios.php">← Volver</a>
<h2><?= $editing ? 'Editar usuario' : 'Nuevo usuario' ?></h2>

<div class="card">
  <?php if ($errores): ?>
    <div class="errors">
      <ul style="margin:0 0 0 18px">
        <?php foreach ($errores as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= $csrf ?>">
    <div class="row">
      <div>
        <label>Nombre</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>
      </div>
      <div>
        <label>Usuario</label>
        <input type="text" name="usuario" value="<?= htmlspecialchars($usuario) ?>" required>
      </div>
      <div>
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
      </div>
      <div>
        <label>Rol</label>
        <select name="rol" required>
          <option value="">Selecciona…</option>
          <option value="admin" <?= $rol==='admin'?'selected':'' ?>>Admin</option>
          <option value="doctor" <?= $rol==='doctor'?'selected':'' ?>>Doctor</option>
          <option value="secretaria" <?= $rol==='secretaria'?'selected':'' ?>>Secretaria</option>
        </select>
      </div>
      <div>
        <label><?= $editing ? 'Nueva contraseña (opcional)' : 'Contraseña' ?></label>
        
        <input type="password" name="pass" <?= $editing ? '' : 'required' ?>>
        <?php if ($editing): ?><small>Déjala vacía para mantener la actual.</small><?php endif; ?>
      </div>
    </div>
    <div style="margin-top:14px;display:flex;gap:10px;">
      <button class="btn" type="submit"><?= $editing ? 'Guardar cambios' : 'Crear usuario' ?></button>
      <a class="btn-outline" href="<?= BASE_URL ?>Administrador/usuarios.php">Cancelar</a>
    </div>
  </form>
</div>
</body>
</html>
