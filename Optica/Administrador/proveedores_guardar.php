<?php
session_start();
require_once __DIR__ . '/../conexion.php';
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../login.php'); exit;
}

$conn = conectarDB();

$nombre = trim($_POST['nombre'] ?? '');
$comercial = trim($_POST['nombre_comercial'] ?? '');
$tel = trim($_POST['telefono'] ?? '');
$mail = trim($_POST['email'] ?? '');
$ciudad = trim($_POST['ciudad'] ?? '');
$depto = trim($_POST['departamento'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');

if ($nombre === '') {
  header('Location: ./proveedores_crear.php?err=Nombre+requerido'); exit;
}

$stmt = $conn->prepare("
  INSERT INTO proveedores (nombre, nombre_comercial, telefono, email, ciudad, departamento, direccion, estado)
  VALUES (?, ?, ?, ?, ?, ?, ?, 1)
");
$stmt->bind_param("sssssss", $nombre, $comercial, $tel, $mail, $ciudad, $depto, $direccion);
$stmt->execute();

header('Location: ./proveedores_listar.php?msg=Proveedor+registrado');
