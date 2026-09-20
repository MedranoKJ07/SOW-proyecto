<?php
require '../vendor/autoload.php';
require_once '../conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$conn = conectarDB();

// Consulta de gastos
$query = "SELECT id_gasto, descripcion, monto, fecha, tipo FROM gastos_operativos ORDER BY fecha DESC";
$resultado = $conn->query($query);

// Crear hoja Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Gastos Operativos');

// Encabezados
$encabezados = ['ID', 'Descripción', 'Monto (C$)', 'Fecha', 'Tipo'];
$sheet->fromArray($encabezados, null, 'A1');

// ✅ Estilo para encabezado
$sheet->getStyle('A1:E1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

// ✅ Insertar datos
$fila = 2;
while ($row = $resultado->fetch_assoc()) {
    $sheet->setCellValue("A{$fila}", $row['id_gasto']);
    $sheet->setCellValue("B{$fila}", $row['descripcion']);
    $sheet->setCellValue("C{$fila}", number_format($row['monto'], 2, '.', ''));
    $sheet->setCellValue("D{$fila}", $row['fecha']);
    $sheet->setCellValue("E{$fila}", $row['tipo']);

    // Estilo para fila de datos
    $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);

    $fila++;
}

// ✅ Autoajustar columnas
foreach (range('A', 'E') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Descargar archivo
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="gastos_operativos.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
