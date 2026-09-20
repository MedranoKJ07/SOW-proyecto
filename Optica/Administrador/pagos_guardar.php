<?php
// Administrador/pagos_guardar.php
session_start();
require_once __DIR__ . '/../conexion.php';
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../login.php'); exit;
}

$conn = conectarDB();

// Leer POST
$csrf        = $_POST['csrf'] ?? '';
$id_prov     = (int)($_POST['id_proveedor'] ?? 0);
$id_compra   = (int)($_POST['id_compra'] ?? 0);
$fecha_pago  = $_POST['fecha_pago'] ?? date('Y-m-d');
$monto       = (float)($_POST['monto'] ?? 0);
$metodo      = $_POST['metodo_pago'] ?? 'efectivo';
$ref         = trim($_POST['referencia'] ?? '');
$obs         = trim($_POST['observacion'] ?? '');

// Validaciones básicas
if ($csrf === '' || $csrf !== ($_SESSION['csrf'] ?? '')) {
  header("Location: ./pagos_crear.php?id_proveedor={$id_prov}&err=Token+CSRF+inválido"); exit;
}
if ($id_prov <= 0 || $id_compra <= 0 || $monto <= 0) {
  header("Location: ./pagos_crear.php?id_proveedor={$id_prov}&err=Datos+inválidos"); exit;
}

// Verificar que la compra exista y sea del proveedor
$stmt = $conn->prepare("SELECT id_compra, id_proveedor, total FROM compras WHERE id_compra = ?");
$stmt->bind_param("i", $id_compra);
$stmt->execute();
$compra = $stmt->get_result()->fetch_assoc();
if (!$compra || (int)$compra['id_proveedor'] !== $id_prov) {
  header("Location: ./pagos_crear.php?id_proveedor={$id_prov}&err=Compra+no+pertenece+al+proveedor"); exit;
}

// Obtener saldo actual (sin vista)
$salq = $conn->prepare("
  SELECT c.total, COALESCE(p.pagado,0) AS pagado, (c.total - COALESCE(p.pagado,0)) AS saldo
  FROM compras c
  LEFT JOIN (
    SELECT id_compra, SUM(monto) AS pagado FROM pagos_proveedor GROUP BY id_compra
  ) p ON p.id_compra = c.id_compra
  WHERE c.id_compra = ?
");
$salq->bind_param("i", $id_compra);
$salq->execute();
$sal = $salq->get_result()->fetch_assoc();
$saldo = $sal ? (float)$sal['saldo'] : (float)$compra['total'];

if ($monto > $saldo + 0.0001) {
  header("Location: ./pagos_crear.php?id_proveedor={$id_prov}&err=El+monto+excede+el+saldo+actual"); exit;
}

// Insertar pago
$ins = $conn->prepare("
  INSERT INTO pagos_proveedor (id_compra, fecha_pago, monto, metodo_pago, referencia, observacion)
  VALUES (?,?,?,?,?,?)
");
$ins->bind_param("isdsss", $id_compra, $fecha_pago, $monto, $metodo, $ref, $obs);
$ins->execute();

// Redirigir con éxito
header("Location: ./proveedores_ver.php?id={$id_prov}");
