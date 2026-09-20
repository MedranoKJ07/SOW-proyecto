<?php
require_once '../conexion.php';
$conn = conectarDB();

$id_proveedor = $_POST['id_proveedor'];
$fecha = $_POST['fecha'];
$productos = $_POST['producto'];
$cantidades = $_POST['cantidad'];
$costos = $_POST['costo_unitario'];

$total = 0;
foreach ($cantidades as $i => $c) {
    $total += $c * $costos[$i];
}

// Insertar en compras
$stmt = $conn->prepare("INSERT INTO compras (id_proveedor, fecha, total) VALUES (?, ?, ?)");
$stmt->bind_param("isd", $id_proveedor, $fecha, $total);
$stmt->execute();
$id_compra = $stmt->insert_id;

// Insertar en detalle_compra
for ($i = 0; $i < count($productos); $i++) {
    $stmt2 = $conn->prepare("INSERT INTO detalle_compra (id_compra, producto, cantidad, costo_unitario) VALUES (?, ?, ?, ?)");
    $stmt2->bind_param("isid", $id_compra, $productos[$i], $cantidades[$i], $costos[$i]);
    $stmt2->execute();
}

// Registrar egreso en movimientos_caja
$desc = "Compra registrada con proveedor ID $id_proveedor";
$stmt3 = $conn->prepare("INSERT INTO movimientos_caja (tipo, descripcion, monto, fecha, id_compra) VALUES ('Egreso', ?, ?, ?, ?)");
$stmt3->bind_param("sdsi", $desc, $total, $fecha, $id_compra);
$stmt3->execute();

header("Location: panel_contabilidad.php?op=registrar_compra&ok=1");
exit;
