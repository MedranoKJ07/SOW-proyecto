<?php
// Administrador/proveedores_listar.php (versión moderna azul/negro)
session_start();
require_once __DIR__ . '/../conexion.php';
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../login.php'); exit;
}

$conn = conectarDB();

// Helper seguro para imprimir
function h(?string $v): string {
  return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Búsqueda
$q = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($q !== '') {
  $where = "WHERE nombre LIKE ? OR nombre_comercial LIKE ? OR email LIKE ? OR telefono LIKE ?";
  $like = "%{$q}%";
  $params = [$like, $like, $like, $like];
}

// Datos
$sql = "SELECT id_proveedor, nombre, nombre_comercial, telefono, email, ciudad, departamento
        FROM proveedores
        $where
        ORDER BY nombre ASC";

$stmt = $conn->prepare($sql);
if ($where) { $stmt->bind_param('ssss', ...$params); }
$stmt->execute();
$result = $stmt->get_result();

// KPI: totales rápidos
$totProv = (int)($conn->query("SELECT COUNT(*) c FROM proveedores")->fetch_assoc()['c'] ?? 0);
$totCompras = (int)($conn->query("SELECT COUNT(*) c FROM compras")->fetch_assoc()['c'] ?? 0);
$totDeuda = (float)(
  $conn->query("
    SELECT COALESCE(SUM(c.total - COALESCE(p.pagado,0)),0) saldo
    FROM compras c
    LEFT JOIN (SELECT id_compra, SUM(monto) pagado FROM pagos_proveedor GROUP BY id_compra) p
      ON p.id_compra = c.id_compra
  ")->fetch_assoc()['saldo'] ?? 0
);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Proveedores registrados</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="../css/bootstrap.min.css">
<style>
:root{
  --bg:#0b1220; --bg-soft:#0f1730; --surface:#121b36; --surface-2:#0e162b;
  --primary:#2f6df6; --primary-2:#7aa2ff; --accent:#0eeaff;
  --text:#e7ecff; --muted:#9fb0d9; --success:#11d18b; --danger:#ff5a79;
  --shadow: 0 12px 32px rgba(0,0,0,.35);
}
*{box-sizing:border-box}
body{
  margin:0;background:
    radial-gradient(1200px 600px at -5% -10%, #1b2b57 0%, transparent 60%),
    radial-gradient(900px 500px at 110% 10%, #1a274a 0%, transparent 60%),
    linear-gradient(180deg, var(--bg) 0%, #0a0f1c 60%);
  color:var(--text); font-family: "Inter", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
}
.wrap{max-width:1200px;margin:28px auto;padding:0 16px}
.header{
  background: linear-gradient(135deg, #13234a, #0f1d3d);
  border:1px solid rgba(255,255,255,.06);
  color:var(--text); border-radius:18px; box-shadow: var(--shadow);
  padding:18px 20px; display:flex; align-items:center; justify-content:space-between; gap:14px;
}
.header h1{font-size:22px; margin:0; letter-spacing:.3px}
.header small{color:var(--muted)}
.btn-ghost{
  color:var(--text); border:1px solid rgba(255,255,255,.16);
  background:transparent; padding:.6rem 1rem; border-radius:12px; text-decoration:none;
}
.btn-ghost:hover{background:rgba(255,255,255,.06)}

.grid-kpi{display:grid; gap:16px; grid-template-columns:repeat(3,1fr); margin:18px 0}
.kpi{
  background: linear-gradient(180deg, var(--surface) 0%, var(--surface-2) 100%);
  border:1px solid rgba(255,255,255,.06); border-radius:16px; padding:16px 18px;
  box-shadow: var(--shadow); position:relative; overflow:hidden;
}
.kpi .label{color:var(--muted); font-size:.92rem}
.kpi .value{font-size:26px; font-weight:700; margin-top:6px}
.kpi::after{
  content:""; position:absolute; top:-40px; right:-40px; width:120px; height:120px;
  background: radial-gradient(closest-side, rgba(47,109,246,.22), transparent);
  filter: blur(6px);
}

.panel{
  background: linear-gradient(180deg, var(--surface) 0%, var(--surface-2) 100%);
  border:1px solid rgba(255,255,255,.06); border-radius:16px; padding:16px; box-shadow: var(--shadow);
}
.search{display:flex; gap:8px; align-items:center; margin:6px 0 14px 0;}
.search input{
  flex:1; background:#0b1327; color:var(--text); border:1px solid rgba(255,255,255,.12);
  padding:.7rem .9rem; border-radius:12px; outline:none;
}
.search button{
  background:var(--primary); color:#fff; border:none; padding:.7rem 1rem; border-radius:12px;
}

.table{width:100%; border-collapse:separate; border-spacing:0 8px; margin:0}
.table thead th{
  padding:10px 12px; color:var(--muted); font-weight:600; border-bottom:1px solid rgba(255,255,255,.08)
}
.table tbody tr{
  background: linear-gradient(180deg, #0f1833, #0b1327);
  border:1px solid rgba(255,255,255,.07);
}
.table tbody tr:hover{ border-color: rgba(47,109,246,.35) }
.table tbody td{ padding:12px 14px; }
.table tbody tr td:first-child{ border-radius:10px 0 0 10px }
.table tbody tr td:last-child{ border-radius:0 10px 10px 0 }
a.btn-mini{
  display:inline-block; padding:.45rem .75rem; border-radius:10px; text-decoration:none; font-size:.9rem;
  border:1px solid rgba(255,255,255,.16); color:#fff;
}
a.btn-mini:hover{ background:rgba(255,255,255,.06) }

@media (max-width: 1000px){ .grid-kpi{grid-template-columns:1fr} }
</style>
</head>
<body>
<div class="wrap">

  <!-- Header -->
  <div class="header">
    <div>
      <h1>📦 Proveedores registrados</h1>
      <small>Ver, buscar y abrir el perfil para revisar compras, pagos y saldos.</small>
    </div>
    <div style="display:flex;gap:8px">
      <a class="btn-ghost" href="./proveedores.php">⬅ Volver al módulo</a>
     
    </div>
  </div>

  <!-- KPIs -->
  <div class="grid-kpi">
    <div class="kpi">
      <div class="label">Proveedores</div>
      <div class="value"><?= number_format($totProv) ?></div>
    </div>
    <div class="kpi">
      <div class="label">Compras registradas</div>
      <div class="value"><?= number_format($totCompras) ?></div>
    </div>
    <div class="kpi">
      <div class="label">Deuda total (C$)</div>
      <div class="value" style="color:<?= $totDeuda>0 ? '#ff5a79':'#11d18b' ?>"><?= number_format($totDeuda,2) ?></div>
    </div>
  </div>

  <!-- Buscador -->
  <div class="panel">
    <form class="search" method="get">
      <input type="text" name="q" value="<?= h($q) ?>" placeholder="Buscar por nombre, comercial, correo o teléfono…">
      <button type="submit">Buscar</button>
    </form>

    <!-- Tabla -->
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width:70px">#</th>
            <th>Nombre</th>
            <th>Empresa</th>
            <th>Teléfono</th>
            <th>Correo</th>
            <th>Ubicación</th>
            <th style="width:160px" class="text-center">Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows === 0): ?>
            <tr><td colspan="7" class="text-center" style="color:var(--muted);padding:16px">No hay proveedores para mostrar.</td></tr>
          <?php else: ?>
            <?php while ($p = $result->fetch_assoc()):
              $ubic = implode(', ', array_filter([
                $p['ciudad'] ?? '',
                $p['departamento'] ?? ''
              ], fn($x) => $x !== null && $x !== ''));
            ?>
            <tr>
              <td>#<?= (int)$p['id_proveedor'] ?></td>
              <td><?= h($p['nombre']) ?></td>
              <td><?= h($p['nombre_comercial']) ?></td>
              <td><?= h($p['telefono']) ?></td>
              <td><?= h($p['email']) ?></td>
              <td><?= h($ubic) ?></td>
              <td class="text-center">
                <a class="btn-mini" href="proveedores_ver.php?id=<?= (int)$p['id_proveedor'] ?>">Ver compras</a>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
</body>
</html>
