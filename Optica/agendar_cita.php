<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'secretaria') {
    header('Location: login.php');
    exit;
}

include 'conexion.php';
$conn = conectarDB();

$mensaje = '';
$pacientes = [];

$query = "SELECT id, nombre FROM pacientes";
$resultado = $conn->query($query);
while ($fila = $resultado->fetch_assoc()) {
    $pacientes[] = $fila;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paciente_id = $_POST['paciente_id'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $motivo = $_POST['motivo'];

    // Verificar si ya hay una cita a esa fecha y hora
    $stmtCheck = $conn->prepare("SELECT id FROM citas_medicas WHERE fecha = ? AND hora = ?");
    $stmtCheck->bind_param("ss", $fecha, $hora);
    $stmtCheck->execute();
    $resultado = $stmtCheck->get_result();

    if ($resultado->num_rows > 0) {
        $mensaje = "❌ Ya existe una cita agendada para esa fecha y hora. Elige otro horario.";
    } else {
        $stmt = $conn->prepare("INSERT INTO citas_medicas (paciente_id, fecha, hora, motivo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $paciente_id, $fecha, $hora, $motivo);

        if ($stmt->execute()) {
            $mensaje = "✅ Cita agendada exitosamente.";
        } else {
            $mensaje = "❌ Error al agendar la cita.";
        }
        $stmt->close();
    }

    $stmtCheck->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agendar Cita</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 40px;
        }

        .form-container {
            background: #ffffff;
            max-width: 600px;
            margin: auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        h2 {
            text-align: center;
            color: #1a237e;
        }

        label {
            font-weight: 600;
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 15px;
        }

        button {
            margin-top: 20px;
            width: 100%;
            background: #1976d2;
            color: white;
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #0d47a1;
        }

        .mensaje {
            text-align: center;
            font-weight: bold;
            margin-top: 20px;
            padding: 10px;
            border-radius: 8px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #1976d2;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Agendar Cita (Paciente Existente)</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje <?= strpos($mensaje, '✅') !== false ? 'success' : 'error' ?>">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label for="paciente_id">Selecciona el paciente:</label>
        <select name="paciente_id" required>
            <option value="">-- Seleccionar paciente --</option>
            <?php foreach ($pacientes as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="fecha">Fecha:</label>
        <input type="date" name="fecha" required>

        <label for="hora">Hora:</label>
        <input type="time" name="hora" required>

        <label for="motivo">Motivo de la cita:</label>
        <textarea name="motivo" rows="3" required></textarea>

        <button type="submit">Agendar Cita</button>
    </form>

    <a href="panel_secretaria.php" class="back-link">← Volver al panel</a>
</div>

</body>
</html>
