<?php
// Administrador/compras_guardar.php
session_start();
require_once __DIR__ . '/../conexion.php';
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../login.php'); exit;
}

$conn = conectarDB();

$id_prov  = (int)($_POST['id_proveedor'] ?? 0);
$fecha    = $_POST['fecha'] ?? date('Y-m-d');
$doc      = trim($_POST['num_documento'] ?? '');
$total    = (float)($_POST['total'] ?? 0);
$productos= $_POST['producto'] ?? [];
$cant     = $_POST['cantidad'] ?? [];
$costo    = $_POST['costo_unitario'] ?? [];

if ($id_prov<=0 || $total<=0 || empty($productos)) {
  header("Location: ./compras_crear.php?id_proveedor={$id_prov}&err=Datos+inválidos"); exit;
}

// Insertar compra
$stmt = $conn->prepare("INSERT INTO compras (id_proveedor, fecha, num_documento, total) VALUES (?,?,?,?)");
$stmt->bind_param("issd", $id_prov, $fecha, $doc, $total);
$stmt->execute();
$id_compra = $conn->insert_id;

// Insertar detalle
$det = $conn->prepare("INSERT INTO detalle_compra (id_compra, producto, cantidad, costo_unitario) VALUES (?,?,?,?)");
for ($i=0; $i<count($productos); $i++) {
  $prod = trim($productos[$i]);
  $cantv= (int)$cant[$i];
  $costv= (float)$costo[$i];
  if ($prod==='' || $cantv<=0 || $costv<=0) continue;
  $det->bind_param("isid", $id_compra, $prod, $cantv, $costv);
  $det->execute();
}

// Redirigir al perfil del proveedor
header("Location: ./proveedores_ver.php?id={$id_prov}&msg=Compra+registrada");
