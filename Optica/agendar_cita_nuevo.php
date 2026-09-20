<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'secretaria') {
    header('Location: login.php');
    exit;
}

include_once 'conexion.php';
$conn = conectarDB();
$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre   = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $edad     = intval($_POST['edad']);
    $cedula   = trim($_POST['cedula'] ?? '');
    $fecha    = $_POST['fecha'];
    $hora     = $_POST['hora'];
    $motivo   = trim($_POST['motivo']);

    $minutos = date('i', strtotime($hora));
    $horaFormateada = date('H:i', strtotime($hora));
    $horaBD = date('H:i:s', strtotime($hora)); // importante para MySQL TIME

    // Validaciones
    if (!preg_match('/^[0-9]{8}$/', $telefono)) {
        $mensaje = "❌ El teléfono debe contener exactamente 8 números.";
    } elseif ($edad < 0 || $edad >= 100) {
        $mensaje = "❌ La edad debe ser un número entre 0 y 99.";
    } elseif (!empty($cedula) && !preg_match('/^[0-9\-]+[A-Za-z]?$/', $cedula)) {
        $mensaje = "❌ La cédula no tiene un formato válido.";
    } elseif (!in_array($minutos, ['00', '30'])) {
        $mensaje = "❌ La hora de la cita debe ser en intervalos de 30 minutos.";
    } elseif ($horaFormateada < '08:00' || $horaFormateada > '17:00') {
        $mensaje = "❌ Solo se permiten citas entre las 08:00 AM y las 05:00 PM.";
    } else {

        // Verificar disponibilidad en la fecha y hora
        $checkStmt = $conn->prepare("SELECT id FROM citas_medicas WHERE fecha = ? AND hora = ?");
        $checkStmt->bind_param("ss", $fecha, $horaBD);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $mensaje = "❌ Ya existe una cita programada para esa fecha y hora. Por favor elige otro horario.";
        } else {
            if ($edad < 16) {
                $cedula = '';
            }

            // Transacción
            $conn->begin_transaction();

            try {
                // Insertar nuevo paciente
                $stmtPaciente = $conn->prepare("INSERT INTO pacientes (nombre, telefono, edad, cedula) VALUES (?, ?, ?, ?)");
                $stmtPaciente->bind_param("ssis", $nombre, $telefono, $edad, $cedula);

                if (!$stmtPaciente->execute()) {
                    throw new Exception("Error al registrar paciente: " . $stmtPaciente->error);
                }

                $paciente_id = $stmtPaciente->insert_id;

                // Agendar cita
                $stmtCita = $conn->prepare("INSERT INTO citas_medicas (paciente_id, fecha, hora, motivo) VALUES (?, ?, ?, ?)");
                $stmtCita->bind_param("isss", $paciente_id, $fecha, $horaBD, $motivo);

                if (!$stmtCita->execute()) {
                    throw new Exception("Error al agendar cita: " . $stmtCita->error);
                }

                $conn->commit();

                $stmtPaciente->close();
                $stmtCita->close();
                $checkStmt->close();
                $conn->close();

                header("Location: panel_secretaria.php?msg=agendada");
                exit;

            } catch (Exception $e) {
                $conn->rollback();
                $mensaje = "❌ " . $e->getMessage();
            }
        }

        $checkStmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agendar Cita - Nuevo Paciente</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f4f8;
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

        input, textarea {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 15px;
            box-sizing: border-box;
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
            margin-bottom: 20px;
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
    <h2>Agendar Cita para Nuevo Paciente</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje <?= strpos($mensaje, '✅') !== false ? 'success' : 'error' ?>">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label for="nombre">Nombre del paciente:</label>
        <input type="text" name="nombre" required>

        <label for="telefono">Teléfono:</label>
        <input 
            type="text" 
            name="telefono" 
            required
            maxlength="8"
            pattern="[0-9]{8}"
            inputmode="numeric"
            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
            title="Ingrese exactamente 8 números"
        >

        <label for="edad">Edad:</label>
        <input 
            type="number" 
            name="edad" 
            required
            min="0"
            max="99"
            oninput="if(this.value > 99) this.value = 99;"
        >

        <div id="grupoCedula">
            <label for="cedula">Cédula:</label>
            <input 
                type="text" 
                name="cedula"
                maxlength="20"
                placeholder="000-000000-0000A"
            >
        </div>

        <label for="fecha">Fecha de la cita:</label>
        <input type="date" name="fecha" required>

        <label for="hora">Hora:</label>
        <input 
            type="time" 
            name="hora" 
            required 
            step="1800"
            min="08:00"
            max="17:00"
        >

        <label for="motivo">Motivo de la cita:</label>
        <textarea name="motivo" rows="3" required></textarea>

        <button type="submit">Agendar Cita</button>
    </form>

    <a href="panel_secretaria.php" class="back-link">← Volver al panel</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const edadInput = document.querySelector('input[name="edad"]');
    const cedulaGroup = document.getElementById('grupoCedula');
    const cedulaInput = document.querySelector('input[name="cedula"]');
    const horaInput = document.querySelector('input[name="hora"]');

    function toggleCedula() {
        const edad = parseInt(edadInput.value) || 0;

        if (edad >= 16) {
            cedulaGroup.style.display = 'block';
        } else {
            cedulaGroup.style.display = 'none';
            cedulaInput.value = '';
        }
    }

    function validarHora() {
        const valor = horaInput.value;
        if (!valor) return;

        const partes = valor.split(':');
        const horas = parseInt(partes[0], 10);
        const minutos = parseInt(partes[1], 10);

        const dentroHorario = (horas > 8 || (horas === 8 && minutos >= 0)) &&
                              (horas < 17 || (horas === 17 && minutos === 0));

        const intervaloValido = (minutos === 0 || minutos === 30);

        if (!dentroHorario || !intervaloValido) {
            alert('Solo se permiten citas cada 30 minutos entre 08:00 AM y 05:00 PM.');
            horaInput.value = '';
        }
    }

    edadInput.addEventListener('input', toggleCedula);
    horaInput.addEventListener('change', validarHora);

    toggleCedula();
});
</script>

</body>
</html>