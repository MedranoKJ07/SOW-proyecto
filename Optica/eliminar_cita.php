<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'secretaria') {
    header('Location: login.php');
    exit;
}

include_once 'conexion.php';
$conn = conectarDB();

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "Cita no válida.";
    exit;
}

$stmt = $conn->prepare("DELETE FROM citas_medicas WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: panel_secretaria.php?deleted=true");
} else {
    echo "❌ Error al eliminar la cita.";
}
