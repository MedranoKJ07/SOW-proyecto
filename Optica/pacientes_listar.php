<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'secretaria') {
  header('Location: login.php'); exit;
}

function h($v): string {
  return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function showOrDash($v): string {
  $s = trim((string)($v ?? ''));
  return $s === '' ? '—' : h($s);
}

$db = conectarDB();
$db->set_charset('utf8mb4');

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
  $like = "%$q%";
  $stmt = $db->prepare("
    SELECT id, nombre, telefono, edad, cedula, correo
    FROM pacientes
    WHERE nombre LIKE ? OR telefono LIKE ? OR cedula LIKE ?
    ORDER BY nombre ASC
  ");
  $stmt->bind_param('sss', $like, $like, $like);
  $stmt->execute();
  $pacientes = $stmt->get_result();
} else {
  $pacientes = $db->query("
    SELECT id, nombre, telefono, edad, cedula, correo
    FROM pacientes
    ORDER BY nombre ASC
  ");
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Pacientes | Secretaría</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f7f8fa; --card:#ffffff; --ink:#0f172a; --muted:#6b7280;
  --brand:#2563eb; --ok:#16a34a; --danger:#ef4444; --border:#e5e7eb; --thead:#eff6ff;
}
*{box-sizing:border-box}
body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial;background:var(--bg);color:var(--ink)}
a{color:var(--brand);text-decoration:none}
a:hover{text-decoration:underline}

.header{
  background:#ffffff; border-bottom:1px solid var(--border);
  padding:14px 16px; display:flex; align-items:center; gap:12px; justify-content:space-between; flex-wrap:wrap;
}
.header h1{margin:0; font-size:18px}
.back{
  display:inline-block; padding:8px 12px; border:1px solid var(--brand); color:var(--brand);
  border-radius:10px; font-weight:700; text-decoration:none;
}
.back:hover{background:var(--brand); color:#fff; text-decoration:none}

.container{max-width:1100px;margin:18px auto;padding:0 16px}
.toolbar{
  display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; margin-bottom:10px;
}
.search{
  display:flex; gap:8px; align-items:center; background:var(--card); border:1px solid var(--border);
  padding:8px 10px; border-radius:10px; width:100%; max-width:520px;
}
.search input{border:none; outline:none; width:100%; font-size:14px; color:var(--ink); background:transparent}
.btn{
  display:inline-block; padding:10px 14px; border-radius:10px; font-weight:700; color:#fff; background:var(--brand); border:1px solid var(--brand);
}
.btn:hover{filter:brightness(1.05); text-decoration:none}
.btn--outlined{background:transparent;color:var(--brand)}
.btn--ok{background:var(--ok); border-color:var(--ok)}
.btn--danger{background:var(--danger); border-color:var(--danger)}

.card{background:var(--card); border:1px solid var(--border); border-radius:12px}
.table-wrap{overflow:auto; max-height:70vh}
table{width:100%; border-collapse:separate; border-spacing:0; min-width:760px}
thead th{
  position:sticky; top:0; background:var(--thead); text-align:left; padding:12px; font-size:13px; border-bottom:1px solid var(--border);
}
tbody td{padding:12px; border-bottom:1px solid var(--border); vertical-align:middle; font-size:14px}
tbody tr:hover{background:#fafafa}
.actions{display:flex; gap:8px; flex-wrap:wrap}
.meta{color:var(--muted); font-size:13px; margin:8px 2px}

/* Alertas simples */
.alert{
  margin:10px 0; padding:10px 12px; border-radius:10px; border:1px solid transparent; font-size:14px;
}
.alert--ok{background:#ecfdf5; border-color:#bbf7d0; color:#166534}
.alert--err{background:#fef2f2; border-color:#fecaca; color:#991b1b}

/* Móvil */
@media (max-width:720px){
  .actions .btn{padding:8px 10px}
}
</style>
</head>
<body>

<header class="header">
  <h1>Pacientes</h1>
  <a class="back" href="panel_secretaria.php">← Volver al Panel</a>
</header>

<div class="container">

  <?php if(isset($_GET['ok'])): ?>
    <div class="alert alert--ok">✅ <?= h($_GET['ok']) ?></div>
  <?php elseif(isset($_GET['err'])): ?>
    <div class="alert alert--err">⚠️ <?= h($_GET['err']) ?></div>
  <?php endif; ?>

  <div class="toolbar">
    <form class="search" method="get" action="">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M21 21l-3.5-3.5M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="#64748b" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
      <input type="text" name="q" value="<?= h($q) ?>" placeholder="Buscar por nombre, teléfono o cédula…">
      <?php if($q!==''): ?>
        <a class="btn btn--outlined" href="pacientes_listar.php">Limpiar</a>
      <?php endif; ?>
    </form>

    <a class="btn" href="pacientes_nuevo.php">➕ Nuevo paciente</a>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Teléfono</th>
            <th>Edad</th>
            <th>Cédula</th>
            <th>Correo</th>
            <th style="width:180px">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($pacientes && $pacientes->num_rows): ?>
            <?php while($p = $pacientes->fetch_assoc()): ?>
              <tr>
                <td><?= showOrDash($p['nombre']) ?></td>
                <td>
                  <?php $tel = trim((string)($p['telefono'] ?? '')); ?>
                  <?= $tel === '' ? '—' : '<a href="tel:'.h($tel).'">'.h($tel).'</a>' ?>
                </td>
                <td>
                  <?php
                    $edad = $p['edad'];
                    echo ($edad === null || $edad === '' ? '—' : h((int)$edad).' años');
                  ?>
                </td>
                <td><?= showOrDash($p['cedula']) ?></td>
                <td>
                  <?php
                    $correo = trim((string)($p['correo'] ?? ''));
                    echo $correo === '' ? '—' : '<a href="mailto:'.h($correo).'">'.h($correo).'</a>';
                  ?>
                </td>
                <td class="actions">
                  <a class="btn btn--ok" href="pacientes_editar.php?id=<?= (int)$p['id'] ?>">Editar</a>
                  <a
                    class="btn btn--danger"
                    href="pacientes_eliminar.php?id=<?= (int)$p['id'] ?>"
                    onclick="return confirm('¿Eliminar este paciente? Si tiene citas/facturas relacionadas, la eliminación puede fallar.')"
                  >Eliminar</a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="6" class="meta">No hay pacientes que coincidan con la búsqueda.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <p class="meta">
    <?= ($pacientes ? (int)$pacientes->num_rows : 0) ?> resultado(s)<?= $q ? ' para “'.h($q).'”' : '' ?>.
  </p>
</div>

</body>
</html>
