<?php
// Administrador/proveedores_reporte_deudas.php (Dashboard con gráficas)
session_start();
require_once __DIR__ . '/../conexion.php';
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') { header('Location: ../login.php'); exit; }

$conn = conectarDB();

// --- Resumen por proveedor ---
$sql = "
  SELECT pr.id_proveedor, pr.nombre, pr.nombre_comercial,
         COALESCE(SUM(c.total),0) AS total_compras,
         COALESCE(SUM(pg.pagado),0) AS total_pagado,
         COALESCE(SUM(c.total - COALESCE(pg.pagado,0)),0) AS saldo
  FROM proveedores pr
  LEFT JOIN compras c ON c.id_proveedor = pr.id_proveedor
  LEFT JOIN (
    SELECT id_compra, SUM(monto) AS pagado FROM pagos_proveedor GROUP BY id_compra
  ) pg ON pg.id_compra = c.id_compra
  GROUP BY pr.id_proveedor, pr.nombre, pr.nombre_comercial
  HAVING total_compras > 0
  ORDER BY saldo DESC
";
$res = $conn->query($sql);

// --- KPIs globales ---
$kpi = ['comprado'=>0.0,'pagado'=>0.0,'saldo'=>0.0,'proveedores'=>0];
$data = [];
if ($res && $res->num_rows) {
  while($r = $res->fetch_assoc()){
    $data[] = $r;
    $kpi['comprado'] += (float)$r['total_compras'];
    $kpi['pagado']   += (float)$r['total_pagado'];
    $kpi['saldo']    += (float)$r['saldo'];
  }
  $kpi['proveedores'] = count($data);
}

// --- Datos para gráficas ---
$labels = [];
$comprado = [];
$pagado = [];
$saldo = [];
$topN = 10; // top por saldo para el bar chart
$ranked = $data;
usort($ranked, fn($a,$b)=> ($b['saldo']<=>$a['saldo']));
$ranked = array_slice($ranked, 0, $topN);

foreach ($ranked as $row) {
  $labels[]   = $row['nombre'] . (empty($row['nombre_comercial'])?'':" ({$row['nombre_comercial']})");
  $comprado[] = round((float)$row['total_compras'],2);
  $pagado[]   = round((float)$row['total_pagado'],2);
  $saldo[]    = round((float)$row['saldo'],2);
}

// Dona: composición global
$pieLabels = ['Pagado','Saldo'];
$pieData = [round($kpi['pagado'],2), round(max($kpi['saldo'],0),2)];

// Helper seguro
function h($v){ return htmlspecialchars((string)($v??''), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Dashboard de Deudas | Óptica West</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../css/bootstrap.min.css">
<style>
:root{
  --bg:#0b1220; --surface:#121b36; --surface2:#0e162b;
  --primary:#2f6df6; --primary-2:#7aa2ff; --accent:#0eeaff;
  --text:#e7ecff; --muted:#9fb0d9; --success:#11d18b; --danger:#ff5a79;
  --shadow:0 12px 32px rgba(0,0,0,.35);
}
body{
  margin:0;background:
    radial-gradient(1200px 600px at -5% -10%, #1b2b57 0%, transparent 60%),
    radial-gradient(900px 500px at 110% 10%, #1a274a 0%, transparent 60%),
    linear-gradient(180deg, var(--bg) 0%, #0a0f1c 60%);
  color:var(--text); font-family:"Inter", system-ui, Segoe UI, Roboto, Arial, sans-serif;
}
.wrap{max-width:1200px;margin:28px auto;padding:0 16px}
.header{background:linear-gradient(135deg,#13234a,#0f1d3d);border:1px solid rgba(255,255,255,.06);
  border-radius:18px;box-shadow:var(--shadow);padding:18px 20px;display:flex;justify-content:space-between;align-items:center}
.btn-ghost{color:var(--text);border:1px solid rgba(255,255,255,.16);padding:.6rem 1rem;border-radius:12px;text-decoration:none}
.btn-ghost:hover{background:rgba(255,255,255,.06)}
.grid-kpi{display:grid;gap:16px;grid-template-columns:repeat(4,1fr);margin:18px 0}
.kpi{background:linear-gradient(180deg,var(--surface),var(--surface2));border:1px solid rgba(255,255,255,.06);
  border-radius:16px;padding:16px 18px;box-shadow:var(--shadow);position:relative;overflow:hidden}
.kpi .label{color:var(--muted);font-size:.92rem}
.kpi .value{font-size:24px;font-weight:700;margin-top:6px}
.panel{background:linear-gradient(180deg,var(--surface),var(--surface2));border:1px solid rgba(255,255,255,.06);
  border-radius:16px;padding:16px;box-shadow:var(--shadow)}
.grid{display:grid;gap:16px;grid-template-columns:2fr 1fr}
.table{width:100%;border-collapse:separate;border-spacing:0 8px}
.table thead th{color:var(--muted);font-weight:600;border-bottom:1px solid rgba(255,255,255,.08)}
.table tbody tr{background:linear-gradient(180deg,#0f1833,#0b1327);border:1px solid rgba(255,255,255,.07)}
.table tbody td{padding:10px 12px}
.table tbody tr td:first-child{border-radius:10px 0 0 10px}
.table tbody tr td:last-child{border-radius:0 10px 10px 0}
.tag{padding:.18rem .5rem;border-radius:999px;font-size:.82rem;border:1px solid rgba(255,255,255,.1)}
.tag-ok{color:#c8ffe7;border-color:rgba(17,209,139,.35);background:rgba(17,209,139,.10)}
.tag-danger{color:#ffd4dd;border-color:rgba(255,90,121,.35);background:rgba(255,90,121,.10)}
a.btn-mini{display:inline-block;padding:.45rem .75rem;border-radius:10px;text-decoration:none;font-size:.9rem;border:1px solid rgba(255,255,255,.16);color:#fff}
a.btn-mini:hover{background:rgba(255,255,255,.06)}
@media(max-width:1000px){ .grid{grid-template-columns:1fr} .grid-kpi{grid-template-columns:1fr 1fr} }
@media(max-width:640px){ .grid-kpi{grid-template-columns:1fr} }
</style>
</head>
<body>
<div class="wrap">

  <!-- Header -->
  <div class="header">
    <h1>📊 Dashboard de Deudas  </h1>
    <div style="display:flex;gap:8px">
      <a class="btn-ghost" href="./proveedores.php">⬅ Volver al módulo</a>
      <a class="btn-ghost" href="./proveedores_listar.php">Ver listado</a>
    </div>
  </div>

  <!-- KPIs -->
  <div class="grid-kpi">
    <div class="kpi">
      <div class="label">Proveedores con movimiento</div>
      <div class="value"><?= number_format($kpi['proveedores']) ?></div>
    </div>
    <div class="kpi">
      <div class="label">Total comprado (C$)</div>
      <div class="value"><?= number_format($kpi['comprado'],2) ?></div>
    </div>
    <div class="kpi">
      <div class="label">Total pagado (C$)</div>
      <div class="value" style="color:var(--success)"><?= number_format($kpi['pagado'],2) ?></div>
    </div>
    <div class="kpi">
      <div class="label">Saldo pendiente (C$)</div>
      <div class="value" style="color:<?= $kpi['saldo']>0 ? 'var(--danger)':'var(--success)' ?>"><?= number_format($kpi['saldo'],2) ?></div>
    </div>
  </div>

  <!-- Charts + Tabla -->
  <div class="grid">
    <!-- Chart barras -->
    <div class="panel">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <div style="font-weight:700">Top <?= (int)min($topN, max(count($labels),0)) ?> saldos por proveedor</div>
        <div>
          <a class="btn-mini" id="dlBarPng" href="#">📥 PNG</a>
        </div>
      </div>
      <canvas id="barChart" height="140"></canvas>
    </div>

    <!-- Chart dona -->
    <div class="panel">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <div style="font-weight:700">Composición global</div>
        <div>
          <a class="btn-mini" id="dlPiePng" href="#">📥 PNG</a>
        </div>
      </div>
      <canvas id="pieChart" height="220"></canvas>
    </div>
  </div>

  <!-- Tabla detalle -->
  <div class="panel" style="margin-top:16px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
      <div style="font-weight:700">Detalle por proveedor</div>
      <div>
        <a class="btn-mini" href="#" onclick="window.print()">🖨️ Imprimir</a>
        <a class="btn-mini" href="#" id="exportCsv">⬇️ CSV</a>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table align-middle" id="tblDetalle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Proveedor</th>
            <th class="text-end">Comprado (C$)</th>
            <th class="text-end">Pagado (C$)</th>
            <th class="text-end">Saldo (C$)</th>
            <th class="text-center" style="width:140px">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($data): ?>
            <?php foreach ($data as $r): ?>
            <tr>
              <td>#<?= (int)$r['id_proveedor'] ?></td>
              <td>
                <strong><?= h($r['nombre']) ?></strong>
                <?php if(!empty($r['nombre_comercial'])): ?>
                  <div style="color:var(--muted)"><?= h($r['nombre_comercial']) ?></div>
                <?php endif; ?>
              </td>
              <td class="text-end"><?= number_format((float)$r['total_compras'],2) ?></td>
              <td class="text-end"><?= number_format((float)$r['total_pagado'],2) ?></td>
              <td class="text-end">
                <span class="tag <?= ((float)$r['saldo']>0?'tag-danger':'tag-ok') ?>">
                  <?= number_format((float)$r['saldo'],2) ?>
                </span>
              </td>
              <td class="text-center">
                <a class="btn-mini" href="./proveedores_ver.php?id=<?= (int)$r['id_proveedor'] ?>">Ver detalle</a>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6" class="text-center" style="color:var(--muted);padding:16px">Sin registros de deudas.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
// Datos PHP -> JS
const labels   = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
const comprado = <?= json_encode($comprado) ?>;
const pagado   = <?= json_encode($pagado) ?>;
const saldo    = <?= json_encode($saldo) ?>;
const pieLabels= <?= json_encode($pieLabels, JSON_UNESCAPED_UNICODE) ?>;
const pieData  = <?= json_encode($pieData) ?>;

// Paleta acorde al tema
const cPrimary = 'rgba(47,109,246,0.85)';     // comprado
const cSuccess = 'rgba(17,209,139,0.85)';     // pagado
const cDanger  = 'rgba(255,90,121,0.85)';     // saldo
const gridClr  = 'rgba(231,236,255,0.15)';
const tickClr  = 'rgba(231,236,255,0.8)';

const ctxBar = document.getElementById('barChart').getContext('2d');
const barChart = new Chart(ctxBar, {
  type: 'bar',
  data: {
    labels,
    datasets: [
      { label: 'Comprado', data: comprado, backgroundColor: cPrimary, borderRadius: 6 },
      { label: 'Pagado',   data: pagado,   backgroundColor: cSuccess, borderRadius: 6 },
      { label: 'Saldo',    data: saldo,    backgroundColor: cDanger,  borderRadius: 6 }
    ]
  },
  options: {
    responsive: true,
    scales: {
      x: { ticks: { color: tickClr }, grid: { display:false } },
      y: { ticks: { color: tickClr }, grid: { color: gridClr } }
    },
    plugins: {
      legend: { labels: { color: tickClr } },
      tooltip: { callbacks: { label: (ctx)=> `${ctx.dataset.label}: C$ ${Number(ctx.parsed.y||0).toFixed(2)}` } }
    }
  }
});

const ctxPie = document.getElementById('pieChart').getContext('2d');
const pieChart = new Chart(ctxPie, {
  type: 'doughnut',
  data: {
    labels: pieLabels,
    datasets: [{ data: pieData, backgroundColor: [cSuccess, cDanger], hoverOffset: 8 }]
  },
  options: {
    cutout: '60%',
    plugins: { legend: { labels: { color: tickClr } } }
  }
});

// Descargar imágenes
document.getElementById('dlBarPng')?.addEventListener('click', (e)=>{
  e.preventDefault();
  const url = barChart.toBase64Image('image/png', 1.0);
  const a = document.createElement('a'); a.href = url; a.download = 'top_saldos.png'; a.click();
});
document.getElementById('dlPiePng')?.addEventListener('click', (e)=>{
  e.preventDefault();
  const url = pieChart.toBase64Image('image/png', 1.0);
  const a = document.createElement('a'); a.href = url; a.download = 'composicion_global.png'; a.click();
});

// Exportar CSV simple del detalle
document.getElementById('exportCsv')?.addEventListener('click', (e)=>{
  e.preventDefault();
  const rows = [['ID','Proveedor','Comprado','Pagado','Saldo']];
  document.querySelectorAll('#tblDetalle tbody tr').forEach(tr=>{
    const tds = tr.querySelectorAll('td');
    if (tds.length>=5){
      const id = tds[0].innerText.replace('#','').trim();
      const prov = tds[1].innerText.trim().replace(/\s+/g,' ');
      const comprado = tds[2].innerText.replace(/[^\d.,-]/g,'').trim();
      const pagado = tds[3].innerText.replace(/[^\d.,-]/g,'').trim();
      const saldo = tds[4].innerText.replace(/[^\d.,-]/g,'').trim();
      rows.push([id, prov, comprado, pagado, saldo]);
    }
  });
  const csv = rows.map(r=>r.map(v => `"${(v+'').replace(/"/g,'""')}"`).join(',')).join('\r\n');
  const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a'); a.href = url; a.download = 'reporte_deudas.csv'; a.click();
  URL.revokeObjectURL(url);
});
</script>
</body>
</html>
