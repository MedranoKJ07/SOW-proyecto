<?php
session_start();

include_once 'conexion.php';
$conn = conectarDB();
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $edad = $_POST['edad'];

    $stmt = $conn->prepare("INSERT INTO pacientes (nombre, telefono, edad) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $nombre, $telefono, $edad);

    if ($stmt->execute()) {
        echo "Paciente insertado correctamente.";
    } else {
        echo "Error al insertar paciente: " . $conn->error;
    }

    $stmt->close();
}
?>

<form method="POST">
    <label>Nombre: <input type="text" name="nombre" required></label><br>
    <label>Teléfono: <input type="text" name="telefono" required></label><br>
    <label>Edad: <input type="number" name="edad" required></label><br>
    <button type="submit">Guardar paciente</button>
</form>
<a href="panel_doctor.php">← Volver al panel</a>
