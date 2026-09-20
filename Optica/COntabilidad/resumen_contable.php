<?php
require_once '../conexion.php';
$conn = conectarDB();

$mes = date('m');
$anio = date('Y');

// 🔵 Ingresos por pagos de clientes
// 1. Ingresos por pagos registrados
$pagos = $conn->query("
    SELECT SUM(monto) AS total_pagos
    FROM pagos
    WHERE MONTH(fecha_pago) = $mes AND YEAR(fecha_pago) = $anio
")->fetch_assoc()['total_pagos'] ?? 0;

// 2. Ingresos por facturas pagadas completamente sin abonos
$facturas_directas = $conn->query("
    SELECT SUM(total) AS total_directos
    FROM facturas
    WHERE estado_pago = 'pagado'
      AND deuda = 0
      AND id NOT IN (SELECT DISTINCT factura_id FROM pagos)
      AND MONTH(fecha) = $mes AND YEAR(fecha) = $anio
")->fetch_assoc()['total_directos'] ?? 0;

// Total ingresos combinados
$ingresos = floatval($pagos) + floatval($facturas_directas);


// 🔴 Egresos operativos registrados
$gastos = $conn->query("
    SELECT SUM(monto) AS total_egresos
    FROM movimientos_caja
    WHERE tipo = 'Egreso' AND id_gasto IS NOT NULL AND MONTH(fecha) = $mes AND YEAR(fecha) = $anio
")->fetch_assoc()['total_egresos'] ?? 0;

// 🟡 Compras de productos
$compras = $conn->query("
    SELECT SUM(total) AS total_compras
    FROM compras
    WHERE MONTH(fecha) = $mes AND YEAR(fecha) = $anio
")->fetch_assoc()['total_compras'] ?? 0;

// 🧮 Balance final
$balance = $ingresos - ($gastos + $compras);

// 🎨 Definir clase de color y emoji según el balance
if ($balance > 0) {
    $clase_balance = 'color: green; font-weight: bold;';
    $emoji_balance = '✅';
} elseif ($balance < 0) {
    $clase_balance = 'color: red; font-weight: bold;';
    $emoji_balance = '📉';
} else {
    $clase_balance = 'font-weight: bold;';
    $emoji_balance = '➖';
}
?>

<h2 style="color: #1e40af; margin-bottom: 20px;">📊 Resumen Contable - <?= date('F Y') ?></h2>

<table style="width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden;">
    <thead style="background: #e2e8f0;">
        <tr>
            <th style="padding: 12px; text-align: left;">📁 Categoría</th>
            <th style="padding: 12px; text-align: right;">💰 Monto (C$)</th>
        </tr>
    </thead>
    <tbody>
        <tr style="border-bottom: 1px solid #e5e7eb;">
            <td style="padding: 10px;">👥 Ingresos por clientes</td>
            <td style="padding: 10px; text-align: right;"><?= number_format($ingresos ?? 0, 2, '.', ',') ?></td>
        </tr>
        <tr style="border-bottom: 1px solid #e5e7eb;">
            <td style="padding: 10px;">🧾 Egresos operativos</td>
            <td style="padding: 10px; text-align: right;"><?= number_format($gastos ?? 0, 2, '.', ',') ?></td>
        </tr>
        <tr style="border-bottom: 1px solid #e5e7eb;">
            <td style="padding: 10px;">🛒 Compras de productos</td>
            <td style="padding: 10px; text-align: right;"><?= number_format($compras ?? 0, 2, '.', ',') ?></td>
        </tr>
        <tr style="background: #e0f2fe;">
            <td style="padding: 12px;">📌 Balance Final <?= $emoji_balance ?></td>
            <td style="padding: 12px; text-align: right; <?= $clase_balance ?>">
                <?= number_format($balance, 2, '.', ',') ?>
            </td>
        </tr>
    </tbody>
</table>
<form action="exportar_resumen_excel.php" method="get" style="margin-top: 20px;">
  <input type="hidden" name="mes" value="<?= $mes ?>">
  <input type="hidden" name="anio" value="<?= $anio ?>">
  <button type="submit" style="background:#0f766e; color:white; padding:10px 18px; border:none; border-radius:6px;">
    📥 Descargar Excel 
  </button>
</form>

