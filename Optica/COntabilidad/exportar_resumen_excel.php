<?php
require_once '../conexion.php';
require '../vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$conn = conectarDB();

$mes = $_GET['mes'] ?? date('m');
$anio = $_GET['anio'] ?? date('Y');

// ========= INGRESOS =========
$pagos = $conn->query("
    SELECT p.monto, p.fecha_pago, p.metodo_pago, f.id AS factura_id, pa.nombre AS cliente
    FROM pagos p
    JOIN facturas f ON p.factura_id = f.id
    JOIN pacientes pa ON f.paciente_id = pa.id
    WHERE MONTH(p.fecha_pago) = $mes AND YEAR(p.fecha_pago) = $anio
");

$facturas_directas = $conn->query("
    SELECT f.fecha, f.total, pa.nombre AS cliente
    FROM facturas f
    JOIN pacientes pa ON f.paciente_id = pa.id
    WHERE f.estado_pago = 'pagado' AND f.deuda = 0
    AND f.id NOT IN (SELECT factura_id FROM pagos)
    AND MONTH(f.fecha) = $mes AND YEAR(f.fecha) = $anio
");

$total_pagos = 0;
$detalle_pagos = [];
while ($row = $pagos->fetch_assoc()) {
    $detalle_pagos[] = $row;
    $total_pagos += $row['monto'];
}

$total_directas = 0;
$detalle_facturas = [];
while ($row = $facturas_directas->fetch_assoc()) {
    $detalle_facturas[] = $row;
    $total_directas += $row['total'];
}

$ingresos = $total_pagos + $total_directas;

// ========= EGRESOS =========
$egresos = $conn->query("
    SELECT SUM(monto) AS total
    FROM movimientos_caja
    WHERE tipo = 'Egreso' AND id_gasto IS NOT NULL
    AND MONTH(fecha) = $mes AND YEAR(fecha) = $anio
")->fetch_assoc()['total'] ?? 0;

// ========= COMPRAS DETALLADAS =========
$compras = $conn->query("
    SELECT c.fecha, pr.nombre AS proveedor, dc.producto, dc.cantidad, dc.costo_unitario
    FROM compras c
    JOIN proveedores pr ON c.id_proveedor = pr.id_proveedor
    JOIN detalle_compra dc ON dc.id_compra = c.id_compra
    WHERE MONTH(c.fecha) = $mes AND YEAR(c.fecha) = $anio
");



$total_compras = 0;
$detalle_compras = [];
while ($row = $compras->fetch_assoc()) {
    $row['subtotal'] = $row['cantidad'] * $row['costo_unitario'];
    $detalle_compras[] = $row;
    $total_compras += $row['subtotal'];
}

// ========= BALANCE =========
$balance = $ingresos - ($egresos + $total_compras);

// ========= GENERAR EXCEL =========
$spreadsheet = new Spreadsheet();

// === Hoja 1: Resumen ===
$resumen = $spreadsheet->getActiveSheet();
$resumen->setTitle('Resumen Contable');
$resumen->fromArray([
    ['Categoría', 'Monto (C$)'],
    ['👥 Ingresos por clientes', $ingresos],
    ['🧾 Egresos operativos', $egresos],
    ['🛒 Compras de productos', $total_compras],
    ['📌 Balance Final', $balance]
]);

// === Hoja 2: Pagos ===
$pagosSheet = $spreadsheet->createSheet();
$pagosSheet->setTitle('Detalle de Pagos');
$pagosSheet->fromArray(['Cliente', 'Fecha de Pago', 'Monto', 'Método de Pago', 'Factura ID'], NULL, 'A1');
$row = 2;
foreach ($detalle_pagos as $p) {
    $pagosSheet->fromArray([
        $p['cliente'],
        $p['fecha_pago'],
        $p['monto'],
        $p['metodo_pago'],
        $p['factura_id']
    ], NULL, "A$row");
    $row++;
}

// === Hoja 3: Facturas Completas ===
$facturasSheet = $spreadsheet->createSheet();
$facturasSheet->setTitle('Facturas Completas');
$facturasSheet->fromArray(['Cliente', 'Fecha', 'Total'], NULL, 'A1');
$row = 2;
foreach ($detalle_facturas as $f) {
    $facturasSheet->fromArray([
        $f['cliente'],
        $f['fecha'],
        $f['total']
    ], NULL, "A$row");
    $row++;
}

// === Hoja 4: Compras Detalladas ===
$comprasSheet = $spreadsheet->createSheet();
$comprasSheet->setTitle('Detalle de Compras');
$comprasSheet->fromArray(['Fecha', 'Proveedor', 'Producto', 'Cantidad', 'Costo Unitario', 'Subtotal'], NULL, 'A1');
$row = 2;
foreach ($detalle_compras as $c) {
    $comprasSheet->fromArray([
        $c['fecha'],
        $c['proveedor'],
        $c['producto'],
        $c['cantidad'],
        $c['costo_unitario'],
        $c['subtotal']
    ], NULL, "A$row");
    $row++;
}

// === Autoajustar columnas
foreach ($spreadsheet->getAllSheets() as $sheet) {
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

// === Descargar
$filename = "Balance_Resumen_{$mes}_{$anio}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
