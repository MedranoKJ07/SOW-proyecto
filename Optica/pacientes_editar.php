<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'secretaria') {
  header('Location: login.php'); exit;
}

function h($v): string {
  return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$db = conectarDB();
$db->set_charset('utf8mb4');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { die('ID inválido'); }

// Cargar datos
$stmt = $db->prepare("SELECT id, nombre, telefono, edad, cedula, correo FROM pacientes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
if (!$p) { die('Paciente no encontrado'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre   = trim($_POST['nombre']   ?? '');
  $telefono = trim($_POST['telefono'] ?? '');
  $edadRaw  = trim($_POST['edad']     ?? '');
  $edad     = ($edadRaw === '') ? null : (int)$edadRaw;
  $cedula   = trim($_POST['cedula']   ?? '');
  $correo   = trim($_POST['correo']   ?? '');
  $correo   = ($correo === '') ? null : $correo;

  $up = $db->prepare("UPDATE pacientes SET nombre=?, telefono=?, edad=?, cedula=?, correo=? WHERE id=?");
  $up->bind_param("ssissi", $nombre, $telefono, $edad, $cedula, $correo, $id);
  $ok = $up->execute();

  if ($ok) {
    header("Location: pacientes_listar.php?actualizado=1");
    exit;
  } else {
    $error = "❌ No se pudo actualizar. Intenta de nuevo.";
  }

  $p = ['id'=>$id,'nombre'=>$nombre,'telefono'=>$telefono,'edad'=>$edad,'cedula'=>$cedula,'correo'=>$correo];
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Editar Paciente</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f4f6f9; --card:#ffffff; --ink:#111827; --muted:#6b7280;
  --brand:#2563eb; --border:#e5e7eb; --danger:#dc2626;
}
*{box-sizing:border-box}
body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial;background:var(--bg);color:var(--ink)}
header{
  background:linear-gradient(90deg,#cbe9f5,#a2d4ec);
  padding:18px 24px; box-shadow:0 2px 8px rgba(0,0,0,.06);
  display:flex; align-items:center; justify-content:space-between;
}
header h1{margin:0;font-size:22px;color:#0f172a}
header a.back{
  background:#fff;color:var(--brand);padding:10px 16px;font-weight:700;
  border-radius:10px;text-decoration:none;border:1px solid var(--brand);
  transition:.15s;
}
header a.back:hover{background:var(--brand);color:#fff}
.wrap{max-width:680px;margin:30px auto 40px;padding:0 16px}
.card{background:var(--card);border:1px solid var(--border);
  border-radius:16px;box-shadow:0 10px 24px rgba(0,0,0,.05);padding:22px}
label{display:block;font-weight:600;margin:12px 0 6px}
input{
  width:100%;padding:12px 14px;border:1px solid var(--border);
  border-radius:10px;outline:none;font-size:15px;background:#fff;
}
input:focus{border-color:var(--brand);box-shadow:0 0 0 4px rgba(37,99,235,.12)}
.actions{margin-top:22px;display:flex;gap:12px;flex-wrap:wrap}
button{
  padding:12px 16px;border:none;border-radius:10px;background:var(--brand);
  color:#fff;font-weight:800;cursor:pointer;box-shadow:0 8px 20px rgba(37,99,235,.22);
  transition:.15s
}
button:hover{transform:translateY(-1px)}
a.cancel{
  display:inline-block;padding:12px 16px;border-radius:10px;
  background:#fff;color:var(--danger);border:1px solid var(--danger);
  font-weight:700;text-decoration:none
}
a.cancel:hover{background:var(--danger);color:#fff}
.alert{
  background:#fff7ed;border:1px solid #fdba74;color:#9a3412;
  padding:10px 12px;border-radius:10px;margin-bottom:14px
}
</style>
</head>
<body>
  <header>
    <h1>Editar datos del paciente</h1>
    <!-- 🔙 Nuevo botón de regresar al panel -->
    <a class="back" href="panel_secretaria.php">← Regresar al Panel</a>
  </header>

  <div class="wrap">
    <div class="card">
      <?php if (!empty($error)): ?><div class="alert"><?= h($error) ?></div><?php endif; ?>
      <form method="post" novalidate>
        <label>Nombre:</label>
        <input type="text" name="nombre" value="<?= h($p['nombre']) ?>" required>

        <label>Teléfono:</label>
        <input type="text" name="telefono" value="<?= h($p['telefono']) ?>">

        <label>Edad:</label>
        <input type="number" name="edad" min="0" value="<?= h($p['edad']) ?>">

        <label>Cédula:</label>
        <input type="text" name="cedula" value="<?= h($p['cedula']) ?>">

        <label>Correo:</label>
        <input type="email" name="correo" value="<?= h($p['correo']) ?>" placeholder="ej: paciente@correo.com">

        <div class="actions">
          <button type="submit">💾 Guardar Cambios</button>
          <a class="cancel" href="pacientes_listar.php">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
