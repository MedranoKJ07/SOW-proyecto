<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'secretaria') {
    header('Location: login.php');
    exit;
}

include_once 'conexion.php';
$conn = conectarDB();

$id = $_GET['id'] ?? null;
$mensaje = '';

if (!$id) {
    echo "Cita no encontrada.";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM citas_medicas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$cita = $stmt->get_result()->fetch_assoc();

if (!$cita) {
    echo "Cita no encontrada.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $motivo = $_POST['motivo'];

    $stmt = $conn->prepare("UPDATE citas_medicas SET fecha = ?, hora = ?, motivo = ? WHERE id = ?");
    $stmt->bind_param("sssi", $fecha, $hora, $motivo, $id);

    if ($stmt->execute()) {
        $mensaje = "✅ Cita actualizada correctamente.";
    } else {
        $mensaje = "❌ Error al actualizar la cita.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cita</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            padding: 40px;
        }
        .container {
            background: #fff;
            max-width: 600px;
            margin: auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 {
            color: #1a237e;
            margin-bottom: 20px;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        button {
            padding: 12px 20px;
            background-color: #1976d2;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background-color: #0d47a1;
        }
        .mensaje {
            background: #e0f7fa;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: bold;
            color: #00695c;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Editar Cita</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje"><?= $mensaje ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Fecha</label>
        <input type="date" name="fecha" value="<?= $cita['fecha'] ?>" required>

        <label>Hora</label>
        <input type="time" name="hora" value="<?= $cita['hora'] ?>" required>

        <label>Motivo</label>
        <textarea name="motivo" rows="3"><?= $cita['motivo'] ?></textarea>

        <button type="submit">Guardar Cambios</button>
    </form>
    <a href="panel_secretaria.php">← Volver</a>
</div>
</body>
</html>
