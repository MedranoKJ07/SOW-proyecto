<?php
// Administrador/inventario.php (Dashboard de Inventario)
session_start();
require_once __DIR__ . '/../conexion.php';
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../login.php'); exit;
}

$conn = conectarDB();

// Resumen general
$sql = "
  SELECT producto,
         SUM(cantidad) AS total_unidades,
         AVG(costo_unitario) AS costo_promedio,
         SUM(cantidad * costo_unitario) AS valor_total
  FROM detalle_compra
  GROUP BY producto
  ORDER BY total_unidades DESC
";
$res = $conn->query($sql);

// KPIs
$kpi = ['productos'=>0,'unidades'=>0,'valor'=>0.0];
$data = [];
if ($res && $res->num_rows) {
  while($r=$res->fetch_assoc()){
    $data[] = $r;
    $kpi['productos']++;
    $kpi['unidades'] += (int)$r['total_unidades'];
    $kpi['valor']    += (float)$r['valor_total'];
  }
}

// Top productos
$topN = 10;
$top = array_slice($data, 0, $topN);
$labels = [];
$unidades = [];
$valores = [];
foreach($top as $t){
  $labels[] = $t['producto'];
  $unidades[] = (int)$t['total_unidades'];
  $valores[] = round((float)$t['valor_total'],2);
}

// Helper
function h($v){return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Inventario | Óptica West</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../css/bootstrap.min.css">
<style>
:root{
  --bg:#0b1220;--surface:#121b36;--surface2:#0e162b;
  --primary:#2f6df6;--accent:#0eeaff;--text:#e7ecff;
  --muted:#9fb0d9;--success:#11d18b;--shadow:0 12px 32px rgba(0,0,0,.35);
}
body{
  margin:0;background:
    radial-gradient(1200px 600px at -5% -10%, #1b2b57 0%, transparent 60%),
    radial-gradient(900px 500px at 110% 10%, #1a274a 0%, transparent 60%),
    linear-gradient(180deg,var(--bg) 0%,#0a0f1c 60%);
  color:var(--text);font-family:"Inter",system-ui,Segoe UI,Roboto,Arial,sans-serif;
}
.wrap{max-width:1200px;margin:28px auto;padding:0 16px}
.header{background:linear-gradient(135deg,#13234a,#0f1d3d);border:1px solid rgba(255,255,255,.06);
  border-radius:18px;box-shadow:var(--shadow);padding:18px 20px;display:flex;justify-content:space-between;align-items:center}
.btn-ghost{color:var(--text);border:1px solid rgba(255,255,255,.16);padding:.6rem 1rem;border-radius:12px;text-decoration:none}
.btn-ghost:hover{background:rgba(255,255,255,.06)}
.grid-kpi{display:grid;gap:16px;grid-template-columns:repeat(3,1fr);margin:18px 0}
.kpi{background:linear-gradient(180deg,var(--surface),var(--surface2));border:1px solid rgba(255,255,255,.06);
  border-radius:16px;padding:16px 18px;box-shadow:var(--shadow)}
.kpi .label{color:var(--muted);font-size:.92rem}
.kpi .value{font-size:26px;font-weight:700;margin-top:6px}
.panel{background:linear-gradient(180deg,var(--surface),var(--surface2));
  border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:16px;box-shadow:var(--shadow)}
.table{width:100%;border-collapse:separate;border-spacing:0 8px}
.table thead th{color:var(--muted);font-weight:600;border-bottom:1px solid rgba(255,255,255,.08)}
.table tbody tr{background:linear-gradient(180deg,#0f1833,#0b1327);border:1px solid rgba(255,255,255,.07)}
.table tbody td{padding:10px 12px}
.table tbody tr td:first-child{border-radius:10px 0 0 10px}
.table tbody tr td:last-child{border-radius:0 10px 10px 0}
@media(max-width:1000px){.grid-kpi{grid-template-columns:1fr 1fr}}
@media(max-width:640px){.grid-kpi{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">

  <div class="header">
    <h1>🏷️ Inventario general</h1>
    <a class="btn-ghost" href="./proveedores.php">⬅ Volver al módulo</a>
  </div>

  <div class="grid-kpi">
    <div class="kpi"><div class="label">Productos registrados</div><div class="value"><?= number_format($kpi['productos']) ?></div></div>
    <div class="kpi"><div class="label">Unidades totales</div><div class="value"><?= number_format($kpi['unidades']) ?></div></div>
    <div class="kpi"><div class="label">Valor total (C$)</div><div class="value" style="color:var(--success)"><?= number_format($kpi['valor'],2) ?></div></div>
  </div>

  <div class="panel" style="margin-top:18px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
      <div style="font-weight:700">📊 Top <?= (int)min($topN, count($labels)) ?> productos por valor</div>
      <a class="btn-ghost btn-sm" href="#" id="dlChart">📥 Descargar gráfica</a>
    </div>
    <canvas id="barChart" height="140"></canvas>
  </div>

  <div class="panel" style="margin-top:18px">
    <div style="font-weight:700;margin-bottom:8px">📋 Detalle del inventario</div>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Producto</th>
            <th class="text-end">Unidades</th>
            <th class="text-end">Costo promedio</th>
            <th class="text-end">Valor total (C$)</th>
          </tr>
        </thead>
        <tbody>
          <?php if($data): $i=1; foreach($data as $r): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= h($r['producto']) ?></td>
            <td class="text-end"><?= number_format($r['total_unidades']) ?></td>
            <td class="text-end"><?= number_format($r['costo_promedio'],2) ?></td>
            <td class="text-end"><?= number_format($r['valor_total'],2) ?></td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="5" class="text-center" style="color:var(--muted);padding:16px">Sin datos registrados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const labels = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
const unidades = <?= json_encode($unidades) ?>;
const valores = <?= json_encode($valores) ?>;
const cPrimary='rgba(47,109,246,0.85)', cAccent='rgba(14,234,255,0.85)', grid='rgba(255,255,255,0.1)', txt='rgba(231,236,255,0.8)';

const ctx=document.getElementById('barChart').getContext('2d');
const chart=new Chart(ctx,{
  type:'bar',
  data:{
    labels,
    datasets:[
      {label:'Unidades',data:unidades,backgroundColor:cAccent,borderRadius:6},
      {label:'Valor (C$)',data:valores,backgroundColor:cPrimary,borderRadius:6}
    ]
  },
  options:{
    responsive:true,
    scales:{x:{ticks:{color:txt}},y:{ticks:{color:txt},grid:{color:grid}}},
    plugins:{legend:{labels:{color:txt}}}
  }
});

document.getElementById('dlChart')?.addEventListener('click',e=>{
  e.preventDefault();
  const a=document.createElement('a');
  a.href=chart.toBase64Image('image/png',1.0);
  a.download='inventario.png';
  a.click();
});
</script>
</body>
</html>
