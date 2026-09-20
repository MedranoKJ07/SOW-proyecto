<?php
// Administrador/proveedores_ver.php (moderno + sin Deprecated)
session_start();
require_once __DIR__ . '/../conexion.php';
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../login.php'); exit;
}

$conn = conectarDB();

// Helper seguro para imprimir y para “valor o guion”
function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function dash($v, $alt='—'): string { $s = trim((string)($v ?? '')); return $s === '' ? $alt : h($s); }

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { die('Proveedor inválido'); }

// Datos del proveedor
$stmt = $conn->prepare("SELECT * FROM proveedores WHERE id_proveedor = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$prov = $stmt->get_result()->fetch_assoc();
if (!$prov) { die('Proveedor no encontrado'); }

// Compras + pagos + saldo
$sql = "SELECT c.id_compra, c.fecha, c.num_documento, c.total,
               IFNULL(SUM(p.monto),0) AS pagado,
               (c.total - IFNULL(SUM(p.monto),0)) AS saldo
        FROM compras c
        LEFT JOIN pagos_proveedor p ON p.id_compra = c.id_compra
        WHERE c.id_proveedor = ?
        GROUP BY c.id_compra
        ORDER BY c.fecha DESC, c.id_compra DESC";
$st = $conn->prepare($sql);
$st->bind_param("i", $id);
$st->execute();
$compras = $st->get_result();

// KPIs del proveedor
$k = $conn->prepare("
  SELECT COALESCE(SUM(c.total),0) total,
         COALESCE(SUM(pg.pagado),0) pagado,
         COALESCE(SUM(c.total - COALESCE(pg.pagado,0)),0) saldo
  FROM compras c
  LEFT JOIN (SELECT id_compra, SUM(monto) pagado FROM pagos_proveedor GROUP BY id_compra) pg
    ON pg.id_compra = c.id_compra
  WHERE c.id_proveedor = ?
");
$k->bind_param("i", $id);
$k->execute();
$kp = $k->get_result()->fetch_assoc() ?: ['total'=>0,'pagado'=>0,'saldo'=>0];

// Ubicación compacta
$ubic = implode(', ', array_filter([
  $prov['ciudad'] ?? '',
  $prov['departamento'] ?? ''
], fn($x)=> trim((string)$x) !== ''));
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Proveedor: <?= h($prov['nombre']) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../css/bootstrap.min.css">
<style>
:root{
  --bg:#0b1220; --bg-soft:#0f1730; --surface:#121b36; --surface-2:#0e162b;
  --primary:#2f6df6; --primary-2:#7aa2ff; --accent:#0eeaff;
  --text:#e7ecff; --muted:#9fb0d9; --success:#11d18b; --danger:#ff5a79;
  --shadow: 0 12px 32px rgba(0,0,0,.35);
}
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
  border:1px solid rgba(255,255,255,.06); border-radius:18px; box-shadow:var(--shadow);
  padding:18px 20px; display:flex; align-items:center; justify-content:space-between; gap:14px;
}
.header h1{font-size:22px; margin:0}
.btn-ghost{color:var(--text); border:1px solid rgba(255,255,255,.16); padding:.6rem 1rem; border-radius:12px; text-decoration:none}
.btn-ghost:hover{background:rgba(255,255,255,.06)}
.panel{
  background: linear-gradient(180deg, var(--surface) 0%, var(--surface-2) 100%);
  border:1px solid rgba(255,255,255,.06); border-radius:16px; padding:16px; box-shadow: var(--shadow);
}
.grid{display:grid; gap:16px; grid-template-columns:2fr 1fr}
.card{background:linear-gradient(180deg,#0f1833,#0b1327); border:1px solid rgba(255,255,255,.08); border-radius:16px; padding:16px}
.label{color:var(--muted); font-size:.92rem}
.value{font-weight:700; font-size:18px}
.kpis{display:grid; gap:12px; grid-template-columns:repeat(3,1fr)}
.kpi{background:linear-gradient(180deg,#0f1833,#0b1327); border:1px solid rgba(255,255,255,.08); border-radius:14px; padding:14px}
.table{width:100%; border-collapse:separate; border-spacing:0 8px}
.table thead th{padding:10px 12px; color:var(--muted); font-weight:600; border-bottom:1px solid rgba(255,255,255,.08)}
.table tbody tr{ background:linear-gradient(180deg,#0f1833,#0b1327); border:1px solid rgba(255,255,255,.07) }
.table tbody td{ padding:12px 14px }
.table tbody tr td:first-child{ border-radius:10px 0 0 10px }
.table tbody tr td:last-child{ border-radius:0 10px 10px 0 }
.tag{padding:.18rem .5rem; border-radius:999px; font-size:.82rem; border:1px solid rgba(255,255,255,.1)}
.tag-danger{ color:#ffd4dd; border-color:rgba(255,90,121,.35); background:rgba(255,90,121,.10)}
.tag-ok{ color:#c8ffe7; border-color:rgba(17,209,139,.35); background:rgba(17,209,139,.10)}
a.btn-mini{display:inline-block;padding:.45rem .75rem;border-radius:10px;text-decoration:none;font-size:.9rem;border:1px solid rgba(255,255,255,.16);color:#fff}
a.btn-mini:hover{background:rgba(255,255,255,.06)}
@media(max-width:1000px){ .grid{grid-template-columns:1fr} .kpis{grid-template-columns:1fr 1fr 1fr} }
@media(max-width:640px){ .kpis{grid-template-columns:1fr} }
</style>
</head>
<body>
<div class="wrap">

  <!-- Header -->
  <div class="header">
    <h1>🏭 Proveedor: <?= h($prov['nombre']) ?></h1>
    <div style="display:flex;gap:8px">
      <a class="btn-ghost" href="./proveedores_listar.php">⬅ Volver al listado</a>
      <a class="btn-ghost" href="./proveedores.php">Módulo</a>
    </div>
  </div>

  <!-- Datos + KPIs -->
  <div class="grid" style="margin-top:16px">
    <div class="card">
      <div class="label">Comercial</div>
      <div class="value"><?= dash($prov['nombre_comercial']) ?></div>

      <div class="label" style="margin-top:10px">Teléfono</div>
      <div class="value"><?= dash($prov['telefono']) ?></div>

      <div class="label" style="margin-top:10px">Email</div>
      <div class="value"><?= dash($prov['email']) ?></div>

      <div class="label" style="margin-top:10px">Dirección</div>
      <div class="value"><?= dash($prov['direccion'], $ubic !== '' ? h($ubic) : '—') ?></div>
    </div>

    <div class="kpis">
      <div class="kpi">
        <div class="label">Total comprado</div>
        <div class="value">C$ <?= number_format((float)$kp['total'], 2) ?></div>
      </div>
      <div class="kpi">
        <div class="label">Pagado</div>
        <div class="value" style="color:#11d18b">C$ <?= number_format((float)$kp['pagado'], 2) ?></div>
      </div>
      <div class="kpi">
        <div class="label">Saldo</div>
        <div class="value" style="color:<?= ((float)$kp['saldo']>0 ? '#ff5a79' : '#11d18b') ?>">
          C$ <?= number_format((float)$kp['saldo'], 2) ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Acciones rápidas -->
  <div class="panel" style="margin-top:16px; display:flex; gap:8px; flex-wrap:wrap">
    <a class="btn-mini" href="./pagos_crear.php?id_proveedor=<?= (int)$prov['id_proveedor'] ?>">💳 Registrar pago</a>
    <a class="btn-mini" href="./compras_crear.php?id_proveedor=<?= (int)$prov['id_proveedor'] ?>">➕ Nueva compra</a>
  </div>

  <!-- Compras -->
  <div class="panel" style="margin-top:16px">
    <div class="title" style="font-weight:700; margin-bottom:8px">🧾 Compras registradas</div>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width:90px">ID Compra</th>
            <th style="width:120px">Fecha</th>
            <th>N° Documento</th>
            <th class="text-end">Total (C$)</th>
            <th class="text-end">Pagado</th>
            <th class="text-end">Saldo</th>
            <th style="width:150px" class="text-center">Detalle</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($compras->num_rows === 0): ?>
            <tr><td colspan="7" class="text-center" style="color:var(--muted);padding:16px">No hay compras registradas.</td></tr>
          <?php else: ?>
            <?php while ($c = $compras->fetch_assoc()): ?>
              <?php
                $doc = trim((string)($c['num_documento'] ?? ''));
                $doc = $doc === '' ? 's/n' : h($doc);
                $tagClass = ((float)$c['saldo']>0 ? 'tag-danger' : 'tag-ok');
              ?>
              <tr>
                <td>#<?= (int)$c['id_compra'] ?></td>
                <td><?= h($c['fecha']) ?></td>
                <td><?= $doc ?></td>
                <td class="text-end"><?= number_format((float)$c['total'],2) ?></td>
                <td class="text-end"><?= number_format((float)$c['pagado'],2) ?></td>
                <td class="text-end"><span class="tag <?= $tagClass ?>"><?= number_format((float)$c['saldo'],2) ?></span></td>
                <td class="text-center">
                  <a class="btn-mini" href="./proveedores_detalle_compra.php?id_compra=<?= (int)$c['id_compra'] ?>">Ver detalle</a>
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
