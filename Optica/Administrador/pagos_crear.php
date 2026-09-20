<?php
// Administrador/pagos_crear.php
session_start();
require_once __DIR__ . '/../conexion.php';
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../login.php'); exit;
}

$conn = conectarDB();

// Helpers
function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// CSRF
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }

// Parámetros
$id_proveedor = (int)($_GET['id_proveedor'] ?? $_POST['id_proveedor'] ?? 0);
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

if ($id_proveedor <= 0) {
  header('Location: ./proveedores_listar.php?err=Proveedor+inválido'); exit;
}

// Proveedor
$stmt = $conn->prepare("SELECT id_proveedor, nombre, nombre_comercial FROM proveedores WHERE id_proveedor=?");
$stmt->bind_param("i", $id_proveedor);
$stmt->execute();
$proveedor = $stmt->get_result()->fetch_assoc();
if (!$proveedor) {
  header('Location: ./proveedores_listar.php?err=No+existe+el+proveedor'); exit;
}

// Compras con saldo (sin necesidad de vista)
$q = $conn->prepare("
  SELECT c.id_compra, c.fecha, c.num_documento, c.total,
         COALESCE(p.pagado,0) AS pagado,
         (c.total - COALESCE(p.pagado,0)) AS saldo
  FROM compras c
  LEFT JOIN (
    SELECT id_compra, SUM(monto) AS pagado
    FROM pagos_proveedor GROUP BY id_compra
  ) p ON p.id_compra = c.id_compra
  WHERE c.id_proveedor = ? AND (c.total - COALESCE(p.pagado,0)) > 0
  ORDER BY c.fecha DESC, c.id_compra DESC
");
$q->bind_param("i", $id_proveedor);
$q->execute();
$comprasConSaldo = $q->get_result();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Registrar pago | Proveedor #<?= (int)$id_proveedor ?></title>
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
.wrap{max-width:900px;margin:28px auto;padding:0 16px}
.header{
  background:linear-gradient(135deg,#13234a,#0f1d3d);
  border:1px solid rgba(255,255,255,.06); border-radius:18px; box-shadow:var(--shadow);
  padding:18px 20px; display:flex; align-items:center; justify-content:space-between; gap:14px;
}
.header h1{font-size:20px;margin:0}
.btn-ghost{color:var(--text);border:1px solid rgba(255,255,255,.16);padding:.6rem 1rem;border-radius:12px;text-decoration:none}
.btn-ghost:hover{background:rgba(255,255,255,.06)}
.panel{
  background:linear-gradient(180deg,var(--surface) 0%,var(--surface-2) 100%);
  border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:16px;box-shadow:var(--shadow);
}
.form-control, .form-select{
  background:#0b1327 !important; color:var(--text); border:1px solid rgba(255,255,255,.12);
}
label{color:var(--muted)}
.alert{border-radius:12px}
.btn-primary{background:var(--primary);border:none;border-radius:12px}
.btn-secondary{border-radius:12px}
.small-muted{color:var(--muted);font-size:.92rem}
</style>
</head>
<body>
<div class="wrap">

  <div class="header">
    <h1>💳 Registrar pago — Proveedor: <?= h($proveedor['nombre']) ?></h1>
    <div style="display:flex;gap:8px">
      <a class="btn-ghost" href="./proveedores_ver.php?id=<?= (int)$proveedor['id_proveedor'] ?>">⬅ Volver</a>
      <a class="btn-ghost" href="./proveedores.php">Módulo</a>
    </div>
  </div>

  <div class="panel" style="margin-top:18px">
    <?php if ($msg): ?>
      <div class="alert alert-success"><?= h($msg) ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
      <div class="alert alert-danger"><?= h($err) ?></div>
    <?php endif; ?>

    <?php if ($comprasConSaldo->num_rows === 0): ?>
      <div class="alert alert-info">Este proveedor no tiene compras con saldo pendiente.</div>
      <a class="btn btn-secondary" href="./proveedores_ver.php?id=<?= (int)$proveedor['id_proveedor'] ?>">Volver</a>
    <?php else: ?>
      <form method="post" action="./pagos_guardar.php" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
        <input type="hidden" name="id_proveedor" value="<?= (int)$proveedor['id_proveedor'] ?>">

        <div class="mb-3">
          <label class="form-label">Compra con saldo</label>
          <select name="id_compra" class="form-select" required>
            <option value="">— Selecciona una compra —</option>
            <?php while($c = $comprasConSaldo->fetch_assoc()): ?>
              <?php
                $label = sprintf(
                  "ID #%d | Doc: %s | Fecha: %s | Total: C$ %.2f | Pagado: C$ %.2f | Saldo: C$ %.2f",
                  $c['id_compra'],
                  trim((string)($c['num_documento'] ?? '')) !== '' ? $c['num_documento'] : 's/n',
                  $c['fecha'],
                  $c['total'],
                  $c['pagado'],
                  $c['saldo']
                );
              ?>
              <option value="<?= (int)$c['id_compra'] ?>"><?= h($label) ?></option>
            <?php endwhile; ?>
          </select>
          <div class="small-muted">Solo aparecen compras cuyo saldo &gt; 0.</div>
        </div>

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Fecha de pago</label>
            <input type="date" name="fecha_pago" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Monto (C$)</label>
            <input type="number" name="monto" step="0.01" min="0.01" class="form-control" placeholder="0.00" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Método de pago</label>
            <select name="metodo_pago" class="form-select" required>
              <option value="efectivo">Efectivo</option>
              <option value="transferencia">Transferencia</option>
              <option value="cheque">Cheque</option>
              <option value="tarjeta">Tarjeta</option>
            </select>
          </div>
        </div>

        <div class="mb-3" style="margin-top:12px">
          <label class="form-label">Referencia (opcional)</label>
          <input type="text" name="referencia" class="form-control" maxlength="80" placeholder="Nº de transacción, cheque, etc.">
        </div>

        <div class="mb-3">
          <label class="form-label">Observación (opcional)</label>
          <textarea name="observacion" class="form-control" rows="2" placeholder="Nota del pago..."></textarea>
        </div>

        <div class="d-flex gap-2">
          <button class="btn btn-primary" type="submit">Guardar pago</button>
          <a class="btn btn-secondary" href="./proveedores_ver.php?id=<?= (int)$proveedor['id_proveedor'] ?>">Cancelar</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<script>
// Validación rápida en cliente
document.querySelector('form')?.addEventListener('submit', function(e){
  const sel = this.querySelector('[name="id_compra"]');
  const monto = parseFloat(this.querySelector('[name="monto"]').value || '0');
  if (!sel.value) { alert('Selecciona una compra.'); e.preventDefault(); return; }
  if (isNaN(monto) || monto <= 0) { alert('Monto inválido.'); e.preventDefault(); }
});
</script>
</body>
</html>
