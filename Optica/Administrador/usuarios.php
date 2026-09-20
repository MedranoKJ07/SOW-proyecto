<?php
// Administrador/usuarios.php  — CRUD LISTADO con MySQLi
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

// --- Seguridad: solo Admin ---
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// --- Conexión ---
$conn = conectarDB();
if (!$conn) {
    die('Error de conexión con la base de datos');
}

// --- Token CSRF ---
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

// --- Filtros (busqueda + rol) ---
$q = trim($_GET['q'] ?? '');
$r = trim($_GET['r'] ?? '');
$validRoles = ['admin', 'doctor', 'secretaria'];
if ($r !== '' && !in_array($r, $validRoles, true)) {
    $r = '';
}

// --- Eliminar usuario ---
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $flash = 'Token inválido. Inténtalo de nuevo.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)($_SESSION['id_usuario'] ?? 0)) {
            $flash = 'No puedes eliminar tu propio usuario.';
        } else {
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $flash = 'Usuario eliminado correctamente.';
            } else {
                $flash = 'No se pudo eliminar el usuario.';
            }
            $stmt->close();
        }
    }
}

// --- Paginación ---
$perPage = 10;
$page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $perPage;

// --- Construir WHERE dinámico ---
$where = [];
$types = '';
$vals = [];

if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = "(nombre LIKE ? OR usuario LIKE ? OR email LIKE ?)";
    $types .= 'sss';
    $vals = array_merge($vals, [$like, $like, $like]);
}
if ($r !== '') {
    $where[] = "rol = ?";
    $types .= 's';
    $vals[] = $r;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// --- Contar total de registros ---
$sqlCount = "SELECT COUNT(*) AS total FROM usuarios $whereSql";
$stmt = $conn->prepare($sqlCount);
if ($types !== '') {
    $stmt->bind_param($types, ...$vals);
}
$stmt->execute();
$res = $stmt->get_result();
$total = (int)($res->fetch_assoc()['total'] ?? 0);
$stmt->close();

$pages = max(1, ceil($total / $perPage));

// --- Obtener registros ---
$sql = "SELECT id, nombre, usuario, email, rol
        FROM usuarios
        $whereSql
        ORDER BY id DESC
        LIMIT ? OFFSET ?";
$types2 = $types . 'ii';
$vals2 = $vals;
$vals2[] = $perPage;
$vals2[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types2, ...$vals2);
$stmt->execute();
$res = $stmt->get_result();

$usuarios = [];
while ($u = $res->fetch_assoc()) {
    $usuarios[] = $u;
}
$stmt->close();

// --- Helper para enlaces con filtros ---
function build_url(array $extra = [])
{
    $base = strtok($_SERVER['REQUEST_URI'], '?');
    $query = array_merge($_GET, $extra);
    return htmlspecialchars($base . ($query ? ('?' . http_build_query($query)) : ''));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Usuarios del sistema</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{
  --bg:#f5f7fb;--card:#fff;--text:#1f2937;--border:#e5e7eb;
  --primary:#2563eb;--primaryH:#1d4ed8;--danger:#dc2626;--dangerH:#b91c1c;
}
body{font-family:Arial,Helvetica,sans-serif;margin:24px;background:var(--bg);color:var(--text);}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;}
.btn{border:0;border-radius:10px;padding:10px 14px;background:var(--primary);color:#fff;text-decoration:none;cursor:pointer;}
.btn:hover{background:var(--primaryH);}
.btn-outline{background:#fff;color:var(--primary);border:1px solid var(--primary);}
.btn-danger{background:var(--danger);color:#fff;}
.btn-danger:hover{background:var(--dangerH);}
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:16px;}
form.inline{display:flex;gap:8px;flex-wrap:wrap;}
input[type="text"],select{padding:10px;border:1px solid var(--border);border-radius:8px;min-width:220px;}
table{width:100%;border-collapse:collapse;margin-top:10px;background:#fff;border:1px solid var(--border);}
th,td{padding:10px;border-bottom:1px solid var(--border);text-align:left;}
th{background:#f9fafb;}
tr:hover td{background:#fafcff;}
.rol{padding:4px 10px;border-radius:999px;font-size:13px;text-transform:capitalize;}
.rol.admin{background:#ffe4e6;color:#991b1b;}
.rol.doctor{background:#dcfce7;color:#065f46;}
.rol.secretaria{background:#e0e7ff;color:#3730a3;}
.flash{background:#ecfeff;border:1px solid #bae6fd;padding:10px;border-radius:10px;margin:10px 0;}
.pagination{display:flex;gap:6px;margin-top:12px;}
.page{padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#fff;text-decoration:none;color:var(--text);}
.page.active{background:var(--primary);border-color:var(--primary);color:#fff;}
</style>
</head>
<body>

<div class="topbar">
  <div style="display:flex;align-items:center;gap:10px;">
    <a href="<?= BASE_URL ?>Administrador/panelAdmin.php" class="btn btn-outline">← Volver</a>
    <h2 style="margin:0;">Usuarios del sistema</h2>
  </div>
  <a class="btn" href="<?= BASE_URL ?>Administrador/usuario_form.php">+ Nuevo usuario</a>
</div>

<div class="card">
  <form class="inline" method="get" action="">
    <input type="text" name="q" placeholder="Buscar..." value="<?= htmlspecialchars($q) ?>">
    <select name="r">
      <option value="">Todos los roles</option>
      <option value="admin"      <?= $r==='admin'?'selected':'' ?>>Admin</option>
      <option value="doctor"     <?= $r==='doctor'?'selected':'' ?>>Doctor</option>
      <option value="secretaria" <?= $r==='secretaria'?'selected':'' ?>>Secretaria</option>
    </select>
    <button class="btn" type="submit">Buscar</button>
    <?php if ($q!=='' || $r!==''): ?>
      <a class="btn btn-outline" href="<?= strtok($_SERVER['REQUEST_URI'],'?') ?>">Limpiar</a>
    <?php endif; ?>
  </form>
</div>

<?php if ($flash): ?>
  <div class="flash"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<div class="card" style="margin-top:12px;">
  <?php if (!$usuarios): ?>
    <p>No hay usuarios para mostrar.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Usuario</th>
          <th>Email</th>
          <th>Rol</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><?= htmlspecialchars($u['nombre']) ?></td>
            <td><?= htmlspecialchars($u['usuario']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="rol <?= htmlspecialchars($u['rol']) ?>"><?= htmlspecialchars($u['rol']) ?></span></td>
            <td>
              <a class="btn btn-outline" href="<?= BASE_URL ?>Administrador/usuario_form.php?id=<?= (int)$u['id'] ?>">Editar</a>
              <form method="post" action="" style="display:inline;" onsubmit="return confirm('¿Eliminar al usuario &quot;<?= htmlspecialchars($u['usuario']) ?>&quot;?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <button class="btn btn-danger" type="submit">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($pages > 1): ?>
      <div class="pagination">
        <?php for ($i=1; $i<=$pages; $i++): ?>
          <a class="page <?= $i===$page?'active':'' ?>" href="<?= build_url(['p'=>$i]) ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

</body>
</html>
