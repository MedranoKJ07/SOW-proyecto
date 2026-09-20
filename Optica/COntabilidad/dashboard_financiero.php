<?php
// Contabilidad/dashboard_financiero.php (funciona con inv_movimientos + productos)
session_start();
require_once __DIR__ . '/../conexion.php';
if (!isset($_SESSION['usuario']) || !in_array(($_SESSION['rol'] ?? ''), ['admin','doctor','secretaria'])) {
  header('Location: ../login.php'); exit;
}
$conn = conectarDB();

/* ===== Helpers ===== */
function fetch_value($stmt){
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_row() : [0];
  return (float)($row[0] ?? 0);
}
function table_exists(mysqli $c, string $t): bool {
  $r = $c->query("SHOW TABLES LIKE '$t'");
  return $r && $r->num_rows > 0;
}
function pick_col(mysqli $c, string $table, array $cands): ?string {
  foreach ($cands as $col) {
    $r = $c->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
    if ($r && $r->num_rows > 0) return $col;
  }
  return null;
}

/* ===== Filtros (mes actual por defecto) ===== */
$hoy = new DateTime('now');
$desde = $_GET['desde'] ?? (new DateTime($hoy->format('Y-m-01')))->format('Y-m-d');
$hasta = $_GET['hasta'] ?? (new DateTime($hoy->format('Y-m-t')))->format('Y-m-d');

/* ===== KPIs ===== */
// Ingresos (pagos de clientes)
$q_ingresos = $conn->prepare("
  SELECT COALESCE(SUM(monto),0)
  FROM pagos
  WHERE DATE(fecha_pago) BETWEEN ? AND ?
");
$q_ingresos->bind_param('ss', $desde, $hasta);
$ingresos_tot = fetch_value($q_ingresos);

// Egresos: Gastos operativos + Compras (pagos a proveedores)
$q_gop = $conn->prepare("
  SELECT COALESCE(SUM(monto),0)
  FROM gastos_operativos
  WHERE DATE(fecha) BETWEEN ? AND ?
");
$q_gop->bind_param('ss', $desde, $hasta);
$gop_tot = fetch_value($q_gop);

$q_comp = $conn->prepare("
  SELECT COALESCE(SUM(total),0)
  FROM compras
  WHERE DATE(fecha) BETWEEN ? AND ?
");
$q_comp->bind_param('ss', $desde, $hasta);
$compras_tot = fetch_value($q_comp);

$egresos_tot = $gop_tot + $compras_tot;
$neto = $ingresos_tot - $egresos_tot;

/* ===== Serie mensual (últimos 12 meses) ===== */
$serie_labels=[]; $serie_ing=[]; $serie_egr=[];
$periodo = new DatePeriod(
  (new DateTime('first day of -11 months'))->setTime(0,0),
  new DateInterval('P1M'),
  (new DateTime('first day of next month'))->setTime(0,0)
);
foreach ($periodo as $m) {
  $ini = $m->format('Y-m-01');
  $fin = $m->format('Y-m-t');
  $serie_labels[] = $m->format('M Y');

  $stmtI = $conn->prepare("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE DATE(fecha_pago) BETWEEN ? AND ?");
  $stmtI->bind_param('ss', $ini, $fin);
  $ing = fetch_value($stmtI);

  $stmtGO = $conn->prepare("SELECT COALESCE(SUM(monto),0) FROM gastos_operativos WHERE DATE(fecha) BETWEEN ? AND ?");
  $stmtGO->bind_param('ss', $ini, $fin);
  $go = fetch_value($stmtGO);

  $stmtC = $conn->prepare("SELECT COALESCE(SUM(total),0) FROM compras WHERE DATE(fecha) BETWEEN ? AND ?");
  $stmtC->bind_param('ss', $ini, $fin);
  $cm = fetch_value($stmtC);

  $serie_ing[] = round($ing,2);
  $serie_egr[] = round($go + $cm, 2);
}

/* ===== Servicios más demandados (TOP 5) ===== */
$top_labels=[]; $top_values=[];
if ($conn->query("SHOW TABLES LIKE 'detalle_factura'")->num_rows &&
    $conn->query("SHOW TABLES LIKE 'servicios'")->num_rows) {
  $sqlTop = "
    SELECT s.nombre AS servicio, COUNT(*) AS veces
    FROM detalle_factura df
    JOIN servicios s ON s.id = df.servicio_id
    JOIN facturas f   ON f.id = df.factura_id
    LEFT JOIN pagos p ON p.factura_id = f.id
    WHERE DATE(COALESCE(p.fecha_pago, f.fecha)) BETWEEN ? AND ?
    GROUP BY s.id
    ORDER BY veces DESC
    LIMIT 5
  ";
  $stmtTop = $conn->prepare($sqlTop);
  $stmtTop->bind_param('ss', $desde, $hasta);
  $stmtTop->execute();
  $r = $stmtTop->get_result();
  while($row = $r->fetch_assoc()){ $top_labels[]=$row['servicio']; $top_values[]=(int)$row['veces']; }
}

/* ===== Inventario crítico (Productos + Compras) ===== */
/*
  Calcula stock = SUM(detalle_compra.cantidad) agrupado por nombre
  y lo compara con productos.stock_minimo. Empareja por nombre (case-insensitive).
*/
$criticos = [];
$criticos_error = '';

// Verificamos tablas mínimas
if (!table_exists($conn,'productos')) {
  $criticos_error = "No existe la tabla productos.";
} elseif (!table_exists($conn,'detalle_compra')) {
  $criticos_error = "No existe la tabla detalle_compra.";
} else {

  // Consulta: entradas por compras (sumadas por nombre de producto)
  // y cruce con productos por nombre (normalizado a LOWER(TRIM()))
  $sqlCrit = "
    SELECT 
      p.id_producto,
      p.nombre,
      COALESCE(c.entradas,0) AS stock,
      COALESCE(p.stock_minimo,0) AS stock_minimo
    FROM productos p
    LEFT JOIN (
      SELECT LOWER(TRIM(d.producto)) AS nom_compras,
             SUM(d.cantidad) AS entradas
      FROM detalle_compra d
      GROUP BY LOWER(TRIM(d.producto))
    ) c
      ON LOWER(TRIM(p.nombre)) = c.nom_compras
    WHERE p.stock_minimo IS NOT NULL
      AND COALESCE(c.entradas,0) <= p.stock_minimo
    ORDER BY stock ASC, p.stock_minimo ASC
    LIMIT 100
  ";

  if ($resC = $conn->query($sqlCrit)) {
    while ($row = $resC->fetch_assoc()) { $criticos[] = $row; }
  } else {
    $criticos_error = "Error calculando inventario crítico: " . $conn->error;
  }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Dashboard Financiero</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="contabilidad.css?v=7">
  <style>
    .wrap{max-width:1200px;margin:32px auto;padding:8px}
    .topbar{display:flex;gap:12px;align-items:center;justify-content:space-between;margin-bottom:12px}
    .btn{background:#2563eb;color:#fff;border:none;padding:10px 14px;border-radius:10px;cursor:pointer;text-decoration:none}
    .filters input{padding:8px 10px;border:1px solid #e5e7eb;border-radius:10px}
    .kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin:16px 0}
    .card{background:#fff;border-radius:16px;box-shadow:0 10px 25px rgba(0,0,0,.08);padding:18px}
    .kpi-title{font-size:14px;color:#64748b}
    .kpi-value{font-size:26px;font-weight:700;margin-top:4px}
    .grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}
    .table{width:100%;border-collapse:separate;border-spacing:0 8px}
    .table th{font-size:12px;color:#64748b;text-align:left;padding:8px}
    .table td{background:#f8fafc;padding:10px;border-top-left-radius:8px;border-bottom-left-radius:8px}
    .table td:last-child{border-top-right-radius:8px;border-bottom-right-radius:8px}
    .badge-neg{color:#ef4444;font-weight:600}
    @media (max-width:900px){.kpis{grid-template-columns:1fr 1fr}.grid2{grid-template-columns:1fr}}
  </style>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
</head>
<body style="background:linear-gradient(120deg,#9ae6ff,#7c4dff20)">
  <div class="wrap">
    <div class="topbar">
      <a class="btn" href="panel_contabilidad.php">← Volver</a>
      <form class="filters" method="get" style="display:flex;gap:8px;align-items:center">
        <label>Desde <input type="date" name="desde" value="<?php echo htmlspecialchars($desde); ?>"></label>
        <label>Hasta <input type="date" name="hasta" value="<?php echo htmlspecialchars($hasta); ?>"></label>
        <button class="btn" type="submit">Aplicar</button>
      </form>
    </div>

    <h1 style="margin:6px 0 10px 0">Dashboard financiero</h1>

    <div class="kpis">
      <div class="card">
        <div class="kpi-title">Ventas totales (<?php echo $desde; ?> → <?php echo $hasta; ?>)</div>
        <div class="kpi-value">C$ <?php echo number_format($ingresos_tot,2); ?></div>
      </div>
      <div class="card">
        <div class="kpi-title">Gastos totales (Operativos + Proveedores)</div>
        <div class="kpi-value">C$ <?php echo number_format($egresos_tot,2); ?></div>
        <small style="color:#64748b">Operativos: C$ <?php echo number_format($gop_tot,2); ?> · Compras: C$ <?php echo number_format($compras_tot,2); ?></small>
      </div>
      <div class="card">
        <div class="kpi-title">Ganancia / pérdida neta</div>
        <div class="kpi-value" style="color:<?php echo $neto>=0?'#16a34a':'#ef4444'; ?>">
          C$ <?php echo number_format($neto,2); ?>
        </div>
      </div>
      <div class="card">
        <div class="kpi-title">Servicios más demandados (TOP 5)</div>
        <div class="kpi-value"><?php echo count($top_labels)?htmlspecialchars($top_labels[0]):'—'; ?></div>
        <small style="color:#64748b">
          <?php echo count($top_labels)>1 ? 'Luego: '.htmlspecialchars(implode(', ', array_slice($top_labels,1))) : (count($top_labels)?'':'Sin datos en el periodo.'); ?>
        </small>
      </div>
    </div>

    <div class="grid2">
      <div class="card">
        <h3 style="margin:0 0 8px 0">Ingresos vs Egresos (últimos 12 meses)</h3>
        <canvas id="lineIE" height="160"></canvas>
      </div>
      <div class="card">
        <h3 style="margin:0 0 8px 0">Servicios más demandados</h3>
        <canvas id="barTop" height="160"></canvas>
      </div>
    </div>

    <div class="card" style="margin-top:16px">
      <h3 style="margin:0 0 10px 0">Inventario crítico</h3>

      <?php if ($criticos_error): ?>
        <p style="color:#ef4444"><?php echo htmlspecialchars($criticos_error); ?></p>
      <?php endif; ?>

      <?php if(empty($criticos) && !$criticos_error): ?>
        <p style="color:#64748b">No hay productos en nivel crítico.</p>
      <?php elseif(!empty($criticos)): ?>
      <table class="table">
        <thead><tr><th>ID</th><th>Producto</th><th>Stock</th><th>Mínimo</th><th>Estado</th></tr></thead>
        <tbody>
          <?php foreach($criticos as $p):
            $falt = max(0, $p['stock_minimo'] - $p['stock']); ?>
            <tr>
              <td style="width:80px"><?php echo (int)$p['id_producto']; ?></td>
              <td><?php echo htmlspecialchars($p['nombre']); ?></td>
              <td style="width:120px"><?php echo (float)$p['stock']; ?></td>
              <td style="width:120px"><?php echo (float)$p['stock_minimo']; ?></td>
              <td style="width:200px">
                <?php echo $falt>0 ? '<span class="badge-neg">⚠ Faltan '.(float)$falt.'</span>' : 'Al límite'; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

<script>
const labels    = <?php echo json_encode($serie_labels); ?>;
const dataIng   = <?php echo json_encode($serie_ing); ?>;
const dataEgr   = <?php echo json_encode($serie_egr); ?>;
const topLabels = <?php echo json_encode($top_labels); ?>;
const topValues = <?php echo json_encode($top_values); ?>;

new Chart(document.getElementById('lineIE'), {
  type:'line',
  data:{ labels, datasets:[
    { label:'Ingresos', data:dataIng, tension:.35 },
    { label:'Egresos', data:dataEgr, tension:.35 }
  ]},
  options:{ responsive:true, plugins:{ legend:{ position:'bottom'} }, scales:{ y:{ beginAtZero:true } } }
});
new Chart(document.getElementById('barTop'), {
  type:'bar',
  data:{ labels:topLabels, datasets:[{ label:'Veces', data:topValues }]},
  options:{ responsive:true, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true, precision:0 } } }
});
</script>
</body>
</html>
