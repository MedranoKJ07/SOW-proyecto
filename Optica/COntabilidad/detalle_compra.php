<?php
require_once '../conexion.php';
$conn = conectarDB();
if (!$conn) { die('Error de conexión'); }
$conn->set_charset('utf8mb4');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  echo "ID inválido.";
  exit;
}

$stmt = $conn->prepare("
  SELECT 
    d.id_detalle_compra,
    d.producto,
    d.cantidad,
    d.costo_unitario,
    (d.cantidad * d.costo_unitario) AS subtotal
  FROM detalle_compra d
  WHERE d.id_compra = ?
  ORDER BY d.id_detalle_compra ASC
");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
  echo "<em>Sin detalles para esta compra.</em>";
  exit;
}

echo '<table style="width:100%; border-collapse:collapse; background:#fff;">';
echo '<thead><tr style="background:#e5e7eb;">
        <th style="padding:8px; border:1px solid #cbd5e1;">#</th>
        <th style="padding:8px; border:1px solid #cbd5e1;">Producto</th>
        <th style="padding:8px; border:1px solid #cbd5e1; text-align:right;">Cantidad</th>
        <th style="padding:8px; border:1px solid #cbd5e1; text-align:right;">Costo unitario (C$)</th>
        <th style="padding:8px; border:1px solid #cbd5e1; text-align:right;">Subtotal (C$)</th>
      </tr></thead><tbody>';

$i = 1;
$total = 0;
while ($r = $res->fetch_assoc()) {
  $total += (float)$r['subtotal'];
  echo '<tr>
          <td style="padding:8px; border:1px solid #e5e7eb; text-align:center;">' . $i++ . '</td>
          <td style="padding:8px; border:1px solid #e5e7eb;">' . htmlspecialchars($r['producto']) . '</td>
          <td style="padding:8px; border:1px solid #e5e7eb; text-align:right;">' . (float)$r['cantidad'] . '</td>
          <td style="padding:8px; border:1px solid #e5e7eb; text-align:right;">' . number_format((float)$r['costo_unitario'], 2) . '</td>
          <td style="padding:8px; border:1px solid #e5e7eb; text-align:right;">' . number_format((float)$r['subtotal'], 2) . '</td>
        </tr>';
}
echo '<tr style="background:#f8fafc; font-weight:bold;">
        <td colspan="4" style="padding:8px; border:1px solid #cbd5e1; text-align:right;">Total detalle</td>
        <td style="padding:8px; border:1px solid #cbd5e1; text-align:right;">' . number_format($total, 2) . '</td>
      </tr>';
echo '</tbody></table>';
