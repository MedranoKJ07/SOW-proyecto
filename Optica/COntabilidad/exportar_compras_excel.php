<?php
require_once '../conexion.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$conn = conectarDB();

$query = "
    SELECT c.id_compra, p.nombre AS proveedor, c.fecha, c.total
    FROM compras c
    JOIN proveedores p ON c.id_proveedor = p.id_proveedor
    ORDER BY c.fecha DESC
";
$resultado = $conn->query($query);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Compras');

//  Encabezados
$encabezados = ['ID', 'Proveedor', 'Fecha', 'Total (C$)'];
$sheet->fromArray($encabezados, null, 'A1');

// Estilo de encabezados
$sheet->getStyle('A1:D1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '45818e']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

//  Rellenar datos
$fila = 2;
while ($row = $resultado->fetch_assoc()) {
    $sheet->setCellValue("A{$fila}", $row['id_compra']);
    $sheet->setCellValue("B{$fila}", $row['proveedor']);
    $sheet->setCellValue("C{$fila}", $row['fecha']);
    $sheet->setCellValue("D{$fila}", $row['total']);

    // Aplicar bordes y alineación por fila
    $sheet->getStyle("A{$fila}:D{$fila}")->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);

    $fila++;
}

// Autoajustar columnas
foreach (range('A', 'D') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

//  Descargar Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="compras_registradas.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
