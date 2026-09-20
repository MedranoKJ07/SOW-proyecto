<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ' . BASE_URL . 'login.php');
  exit;
}

$db = conectarDB();

// helper para que si una query falla, te diga el error real
function q(mysqli $db, string $sql): mysqli_result {
  $r = $db->query($sql);
  if (!$r) {
    die("SQL ERROR: " . $db->error . "<br><pre>$sql</pre>");
  }
  return $r;
}

// --- KPIs ---
$stats = ['proveedores'=>0,'compras'=>0,'deudas'=>0.00];

$r = q($db, "SELECT COUNT(*) c FROM proveedores");
$stats['proveedores'] = (int)$r->fetch_assoc()['c'];

$r = q($db, "SELECT COUNT(*) c FROM compras");
$stats['compras'] = (int)$r->fetch_assoc()['c'];

// OJO: pagos_proveedor (sin S) es tu tabla real
$sqlDeudas = "
  SELECT COALESCE(SUM(c.total - COALESCE(p.pagado,0)),0) AS saldo
  FROM compras c
  LEFT JOIN (
    SELECT id_compra, SUM(monto) pagado
    FROM pagos_proveedor
    GROUP BY id_compra
  ) p ON p.id_compra = c.id_compra
";
$r = q($db, $sqlDeudas);
$stats['deudas'] = (float)$r->fetch_assoc()['saldo'];

// --- Top 5 proveedores con mayor saldo ---
$sqlTop = "
  SELECT pr.id_proveedor, pr.nombre, pr.nombre_comercial,
         SUM(c.total) total, SUM(COALESCE(p.pagado,0)) pagado,
         SUM(c.total-COALESCE(p.pagado,0)) saldo
  FROM proveedores pr
  JOIN compras c ON c.id_proveedor = pr.id_proveedor
  LEFT JOIN (
    SELECT id_compra, SUM(monto) pagado
    FROM pagos_proveedor
    GROUP BY id_compra
  ) p ON p.id_compra = c.id_compra
  GROUP BY pr.id_proveedor, pr.nombre, pr.nombre_comercial
  ORDER BY saldo DESC
  LIMIT 5
";
$top = q($db, $sqlTop);

// --- Búsqueda rápida ---
$buscar = trim($_GET['q'] ?? '');
?>

<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Módulo de Proveedores | Óptica West</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="<?= BASE_URL ?>css/bootstrap.min.css">
<style>
:root{
  --bg:#0b1220;            /* fondo general negro-azul */
  --bg-soft:#0f1730;       /* paneles */
  --surface:#121b36;       /* tarjetas */
  --surface-2:#0e162b;
  --primary:#2f6df6;       /* azul principal */
  --primary-2:#7aa2ff;     /* azul claro */
  --accent:#0eeaff;        /* cian acento */
  --text:#e7ecff;          /* texto claro */
  --muted:#9fb0d9;         /* texto secundario */
  --success:#11d18b;
  --danger:#ff5a79;
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
.cards{display:grid; gap:16px; grid-template-columns:repeat(3,1fr)}
.card{
  background: linear-gradient(180deg, #0f1833 0%, #0b1327 100%);
  border:1px solid rgba(255,255,255,.06); border-radius:16px; padding:18px; box-shadow: var(--shadow);
  transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
  text-decoration:none; color:var(--text); display:flex; flex-direction:column; gap:8px; min-height:130px;
}
.card:hover{ transform: translateY(-2px); border-color: rgba(14,234,255,.35); box-shadow: 0 20px 48px rgba(0,0,0,.55); }
.card .title{font-weight:700; letter-spacing:.2px}
.card .desc{color:var(--muted); font-size:.95rem}
.card .cta{margin-top:auto; color:var(--primary-2)}
.badge-dot{
  display:inline-flex; align-items:center; gap:8px; font-size:.92rem; color:var(--muted)
}
.badge-dot i{width:8px; height:8px; background:var(--accent); display:inline-block; border-radius:50%}
.panel{
  background: linear-gradient(180deg, var(--surface) 0%, var(--surface-2) 100%);
  border:1px solid rgba(255,255,255,.06); border-radius:16px; padding:16px; box-shadow: var(--shadow);
}
.table{
  width:100%; border-collapse:separate; border-spacing:0 8px;
}
.table th, .table td{ padding:12px 14px; }
.table thead th{ color:var(--muted); font-weight:600; border-bottom:1px solid rgba(255,255,255,.08)}
.table tbody tr{
  background: linear-gradient(180deg, #0f1833, #0b1327);
  border:1px solid rgba(255,255,255,.07);
}
.table tbody tr:hover{ border-color: rgba(47,109,246,.35) }
.table tbody tr td:first-child{ border-radius:10px 0 0 10px }
.table tbody tr td:last-child{ border-radius:0 10px 10px 0 }
.tag{
  padding:.18rem .5rem; border-radius:999px; font-size:.82rem; border:1px solid rgba(255,255,255,.1);
}
.tag-danger{ color:#ffd4dd; border-color:rgba(255,90,121,.35); background:rgba(255,90,121,.10)}
.tag-ok{ color:#c8ffe7; border-color:rgba(17,209,139,.35); background:rgba(17,209,139,.10)}
.search{
  display:flex; gap:8px; align-items:center; margin:14px 0 6px 0;
}
.search input{
  flex:1; background:#0b1327; color:var(--text); border:1px solid rgba(255,255,255,.12);
  padding:.7rem .9rem; border-radius:12px; outline:none;
}
.search button{
  background:var(--primary); color:#fff; border:none; padding:.7rem 1rem; border-radius:12px;
}
@media (max-width: 1000px){
  .cards{grid-template-columns:1fr 1fr}
  .grid-kpi{grid-template-columns:1fr}
}
@media (max-width: 640px){
  .cards{grid-template-columns:1fr}
}
a.btn-mini{
  display:inline-block; padding:.45rem .75rem; border-radius:10px; text-decoration:none; font-size:.9rem;
  border:1px solid rgba(255,255,255,.16); color:#fff;
}
a.btn-mini:hover{ background:rgba(255,255,255,.06) }
</style>
</head>
<body>
  <div class="wrap">

    <!-- Header -->
    <div class="header">
      <div>
        <h1>🏭 Módulo de Proveedores</h1>
        <small>Administra proveedores, compras, pagos y deudas desde un solo lugar.</small>
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        <a class="btn-ghost" href="<?= BASE_URL ?>Administrador/panelAdmin.php">⬅ Volver al panel</a>
        <a class="btn-ghost" href="<?= BASE_URL ?>logout.php">Cerrar sesión</a>
      </div>
    </div>

    <!-- KPIs -->
    <div class="grid-kpi">
      <div class="kpi">
        <div class="label">Proveedores</div>
        <div class="value"><?= number_format($stats['proveedores']) ?></div>
        <div class="badge-dot" style="margin-top:6px"><i></i> activos/registrados</div>
      </div>
      <div class="kpi">
        <div class="label">Compras registradas</div>
        <div class="value"><?= number_format($stats['compras']) ?></div>
        <div class="badge-dot" style="margin-top:6px"><i></i> módulo contable</div>
      </div>
      <div class="kpi">
        <div class="label">Deuda total (C$)</div>
        <div class="value" style="color:<?= $stats['deudas']>0 ? 'var(--danger)':'var(--success)' ?>">
          <?= number_format($stats['deudas'],2) ?>
        </div>
        <div class="badge-dot" style="margin-top:6px"><i></i> saldo = total − pagos</div>
      </div>
    </div>

    <!-- Acciones principales -->
    <div class="cards">
      <a class="card" href="<?= BASE_URL ?>Administrador/proveedores_listar.php">
        <div class="title">📋 Listado de proveedores</div>
        <div class="desc">Busca, filtra y abre el perfil de cada proveedor.</div>
        <div class="cta">Abrir →</div>
      </a>

      <a class="card" href="<?= BASE_URL ?>Administrador/proveedores_listar.php">
        <div class="title">🧾 Compras por proveedor</div>
        <div class="desc">Ingresa al proveedor y revisa todas sus compras con pagos y saldo.</div>
        <div class="cta">Ir al listado →</div>
      </a>

      <a class="card" href="<?= BASE_URL ?>Administrador/pagos_crear.php">
        <div class="title">💳 Registrar pago</div>
        <div class="desc">Abona a una compra con saldo desde el perfil o directamente aquí.</div>
        <div class="cta">Seleccionar proveedor →</div>
      </a>

      <a class="card" href="<?= BASE_URL ?>Administrador/compras_crear.php">
        <div class="title">➕ Nueva compra</div>
        <div class="desc">Crea una compra y agrega ítems (detalle_compra). Actualiza inventario luego.</div>
        <div class="cta">Crear →</div>
      </a>

      <a class="card" href="<?= BASE_URL ?>Administrador/proveedores_reporte_deudas.php">
        <div class="title">📈 Reporte de deudas</div>
        <div class="desc">Resumen de comprado, pagado y saldo por proveedor. Exportable.</div>
        <div class="cta">Ver reporte →</div>
      </a>

      <a class="card" href="<?= BASE_URL ?>Administrador/inventario.php">
        <div class="title">🏷️ Inventario</div>
        <div class="desc">Consulta stock y movimientos ligados a compras confirmadas.</div>
        <div class="cta">Abrir →</div>
      </a>
    </div>
   <a class="card" href="<?= BASE_URL ?>Administrador/proveedores_crear.php">
        <div class="title">➕ Nuevo proveedor</div>
        <div class="desc">Registra un nuevo proveedor con su información de contacto y dirección.</div>
        <div class="cta">Agregar →</div>
      </a>

    <!-- Buscador directo (redirecciona al listado con el query) -->
    <div class="panel" style="margin-top:18px">
      <form class="search" method="get" action="<?= BASE_URL ?>Administrador/proveedores_listar.php">
        <input type="text" name="q" value="<?= htmlspecialchars($buscar) ?>"
               placeholder="Buscar proveedor por nombre, comercial, correo o teléfono…">
        <button type="submit">Buscar</button>
      </form>
    </div>
       

    <!-- Top deudores -->
    <div class="panel" style="margin-top:16px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <div class="title" style="font-weight:700">🔥 Top 5 proveedores con mayor saldo</div>
        <a class="btn-mini" href="<?= BASE_URL ?>Administrador/proveedores_reporte_deudas.php">Ver todo</a>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th style="width:60px;">ID</th>
              <th>Proveedor</th>
              <th class="text-end">Comprado (C$)</th>
              <th class="text-end">Pagado (C$)</th>
              <th class="text-end">Saldo (C$)</th>
              <th style="width:150px" class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($top && $top->num_rows): ?>
              <?php while($t=$top->fetch_assoc()): ?>
              <tr>
                <td>#<?= (int)$t['id_proveedor'] ?></td>
                <td>
                  <strong><?= htmlspecialchars($t['nombre']) ?></strong>
                  <?php if (!empty($t['nombre_comercial'])): ?>
                    <div class="text-muted" style="color:var(--muted)"><?= htmlspecialchars($t['nombre_comercial']) ?></div>
                  <?php endif; ?>
                </td>
                <td class="text-end"><?= number_format((float)$t['total'],2) ?></td>
                <td class="text-end"><?= number_format((float)$t['pagado'],2) ?></td>
                <td class="text-end">
                  <span class="tag <?= ((float)$t['saldo']>0 ? 'tag-danger':'tag-ok') ?>">
                    <?= number_format((float)$t['saldo'],2) ?>
                  </span>
                </td>
                <td class="text-center">
                  <a class="btn-mini" href="<?= BASE_URL ?>Administrador/proveedores_ver.php?id=<?= (int)$t['id_proveedor'] ?>">Ver compras</a>
                </td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="6" class="text-center" style="color:var(--muted);padding:16px">Sin datos suficientes.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Ayuda -->
    <div class="panel" style="margin:16px 0 28px">
      <div class="title" style="font-weight:700; margin-bottom:6px;">¿Cómo usar este módulo?</div>
      <ol style="margin:0; padding-left:18px; color:var(--muted)">
        <li>Entra a <b>Listado de proveedores</b> y abre un proveedor.</li>
        <li>En su perfil verás <b>compras</b>, <b>pagos</b> y <b>saldo</b>. Desde ahí puedes <b>registrar abonos</b>.</li>
        <li>Usa <b>Nueva compra</b> para cargar facturas y su detalle de productos (detalle_compra).</li>
      </ol>
    </div>

  </div>
</body>
</html>
