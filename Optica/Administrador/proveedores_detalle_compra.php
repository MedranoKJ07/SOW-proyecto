<?php
// Administrador/proveedores_detalle_compra.php (moderno + ruta corregida)
session_start();
require_once __DIR__ . '/../conexion.php'; // ✅ corregida la ruta
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../login.php'); exit;
}

$conn = conectarDB();

// Helpers seguros
function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function dash($v, $alt='—'): string { $s = trim((string)($v ?? '')); return $s === '' ? $alt : h($s); }

$id_compra = (int)($_GET['id_compra'] ?? 0);
if ($id_compra <= 0) { die('Compra inválida'); }

// Compra + proveedor
$stmt = $conn->prepare("
  SELECT c.*, p.nombre AS proveedor
  FROM compras c
  JOIN proveedores p ON p.id_proveedor = c.id_proveedor
  WHERE c.id_compra = ?
");
$stmt->bind_param("i", $id_compra);
$stmt->execute();
$compra = $stmt->get_result()->fetch_assoc();
if (!$compra) { die('Compra no encontrada'); }

// Detalle de productos
$d = $conn->prepare("
  SELECT producto, cantidad, costo_unitario, (cantidad * costo_unitario) AS subtotal
  FROM detalle_compra WHERE id_compra = ?
");
$d->bind_param("i", $id_compra);
$d->execute();
$detalles = $d->get_result();

// Pagos
$p = $conn->prepare("
  SELECT fecha_pago, monto, metodo_pago, referencia, observacion
  FROM pagos_proveedor WHERE id_compra = ?
  ORDER BY fecha_pago DESC, id_pago DESC
");
$p->bind_param("i", $id_compra);
$p->execute();
$pagos = $p->get_result();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Detalle de compra #<?= h($id_compra) ?> | Óptica West</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../css/bootstrap.min.css">
<style>
:root{
  --bg:#0b1220; --bg-soft:#0f1730; --surface:#121b36; --surface-2:#0e162b;
  --primary:#2f6df6; --accent:#0eeaff; --text:#e7ecff; --muted:#9fb0d9;
  --success:#11d18b; --danger:#ff5a79; --shadow:0 12px 32px rgba(0,0,0,.35);
}
body{
  margin:0;background:
    radial-gradient(1200px 600px at -5% -10%, #1b2b57 0%, transparent 60%),
    radial-gradient(900px 500px at 110% 10%, #1a274a 0%, transparent 60%),
    linear-gradient(180deg, var(--bg) 0%, #0a0f1c 60%);
  color:var(--text); font-family:"Inter", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
}
.wrap{max-width:1100px;margin:28px auto;padding:0 16px}
.header{
  background:linear-gradient(135deg,#13234a,#0f1d3d);
  border:1px solid rgba(255,255,255,.06);
  color:var(--text);border-radius:18px;box-shadow:var(--shadow);
  padding:18px 20px;display:flex;align-items:center;justify-content:space-between;gap:14px;
}
.header h1{font-size:22px;margin:0}
.btn-ghost{
  color:var(--text);border:1px solid rgba(255,255,255,.16);
  padding:.6rem 1rem;border-radius:12px;text-decoration:none;
}
.btn-ghost:hover{background:rgba(255,255,255,.06)}
.panel{
  background:linear-gradient(180deg,var(--surface) 0%,var(--surface-2) 100%);
  border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:16px;box-shadow:var(--shadow);
}
.table{width:100%;border-collapse:separate;border-spacing:0 8px}
.table thead th{padding:10px 12px;color:var(--muted);font-weight:600;border-bottom:1px solid rgba(255,255,255,.08)}
.table tbody tr{background:linear-gradient(180deg,#0f1833,#0b1327);border:1px solid rgba(255,255,255,.07)}
.table tbody td{padding:12px 14px}
.table tbody tr td:first-child{border-radius:10px 0 0 10px}
.table tbody tr td:last-child{border-radius:0 10px 10px 0}
a.btn-mini{display:inline-block;padding:.45rem .75rem;border-radius:10px;text-decoration:none;font-size:.9rem;border:1px solid rgba(255,255,255,.16);color:#fff}
a.btn-mini:hover{background:rgba(255,255,255,.06)}
.tag{padding:.18rem .5rem;border-radius:999px;font-size:.82rem;border:1px solid rgba(255,255,255,.1)}
.tag-danger{color:#ffd4dd;border-color:rgba(255,90,121,.35);background:rgba(255,90,121,.10)}
.tag-ok{color:#c8ffe7;border-color:rgba(17,209,139,.35);background:rgba(17,209,139,.10)}
@media(max-width:900px){.wrap{padding:0 10px}}
</style>
</head>
<body>
<div class="wrap">

  <!-- Header -->
  <div class="header">
    <h1>🧾 Compra #<?= h($id_compra) ?> - <?= dash($compra['proveedor']) ?></h1>
    <div style="display:flex;gap:8px">
      <a class="btn-ghost" href="./proveedores_ver.php?id=<?= (int)$compra['id_proveedor'] ?>">⬅ Volver</a>
      <a class="btn-ghost" href="./proveedores.php">Módulo</a>
    </div>
  </div>

  <!-- Info general -->
  <div class="panel" style="margin-top:18px">
    <div><b>Fecha:</b> <?= dash($compra['fecha']) ?></div>
    <div><b>N° Documento:</b> <?= dash($compra['num_documento'], 's/n') ?></div>
    <div><b>Total:</b> C$ <?= number_format((float)$compra['total'],2) ?></div>
  </div>

  <!-- Productos -->
  <div class="panel" style="margin-top:16px">
    <h5 style="font-weight:700;margin-bottom:10px;">📦 Productos comprados</h5>
    <?php if ($detalles->num_rows === 0): ?>
      <div class="text-muted" style="color:var(--muted)">Sin productos registrados.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Producto</th><th>Cantidad</th><th>Costo Unitario</th><th>Subtotal</th></tr></thead>
          <tbody>
            <?php while($d=$detalles->fetch_assoc()): ?>
              <tr>
                <td><?= dash($d['producto']) ?></td>
                <td><?= (int)$d['cantidad'] ?></td>
                <td>C$ <?= number_format((float)$d['costo_unitario'],2) ?></td>
                <td>C$ <?= number_format((float)$d['subtotal'],2) ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Pagos -->
  <div class="panel" style="margin-top:16px;margin-bottom:24px">
    <h5 style="font-weight:700;margin-bottom:10px;">💳 Pagos realizados</h5>
    <?php if ($pagos->num_rows === 0): ?>
      <div class="text-muted" style="color:var(--muted)">No hay pagos registrados para esta compra.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Fecha</th><th>Método</th><th>Referencia</th><th>Monto (C$)</th><th>Observación</th></tr></thead>
          <tbody>
            <?php while($pg=$pagos->fetch_assoc()): ?>
              <tr>
                <td><?= h($pg['fecha_pago']) ?></td>
                <td><?= dash($pg['metodo_pago']) ?></td>
                <td><?= dash($pg['referencia'],'—') ?></td>
                <td>C$ <?= number_format((float)$pg['monto'],2) ?></td>
                <td><?= dash($pg['observacion'],'—') ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

</div>
</body>
</html>
