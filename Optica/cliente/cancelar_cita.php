<?php
require_once 'conexion.php';
$conn = conectarDB();

$id = $_GET['id'] ?? null;

if ($id) {
    $sql = "UPDATE citas_medicas SET estado = 'cancelada' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        // Redirigir al apartado de "Quiénes Somos"
        header("Location: quienes_somos.php?cancel=ok");
        exit;
    } else {
        echo "Error al cancelar la cita.";
    }
} else {
    echo "ID de cita no especificado.";
}
?>
