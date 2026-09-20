<?php
include_once 'conexion.php';
$conn = conectarDB();

// Obtener lista de pacientes para el select
$pacientes = [];
$result = $conn->query("SELECT id, nombre FROM pacientes ORDER BY nombre ASC");
while ($row = $result->fetch_assoc()) {
    $pacientes[] = $row;
}

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $paciente_id = $_POST['paciente_id'];
    $fecha = $_POST['fecha'];

    $tipo_lente_array = $_POST['tipo_lente'] ?? [];
    $tipo_lente = implode(', ', $tipo_lente_array);

    $campos = [
        'esfera_od', 'cilindro_od', 'eje_od',
        'adicion_od', 'avs_lentes_od', 'avc_lentes_od',
        'dip_od', 'altura_od',
        'esfera_oi', 'cilindro_oi', 'eje_oi',
        'adicion_oi', 'avs_lentes_oi', 'avc_lentes_oi',
        'dip_oi', 'altura_oi', 'diagnostico',
        'observaciones', 'medidas_anteriores'
    ];

    foreach ($campos as $campo) {
        $$campo = $_POST[$campo] ?? '';
    }

    $sql = "INSERT INTO historial_clinico (
        paciente_id, fecha,
        esfera_od, cilindro_od, eje_od, adicion_od, avs_lentes_od, avc_lentes_od, dip_od, altura_od,
        esfera_oi, cilindro_oi, eje_oi, adicion_oi, avs_lentes_oi, avc_lentes_oi, dip_oi, altura_oi,
        tipo_lente, diagnostico, observaciones, medidas_anteriores
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "Error en la preparación de la consulta: " . $conn->error;
        exit();
    }

    $stmt->bind_param("isssssssssssssssssssss", 
        $paciente_id, $fecha,
        $esfera_od, $cilindro_od, $eje_od, $adicion_od, $avs_lentes_od, $avc_lentes_od, $dip_od, $altura_od,
        $esfera_oi, $cilindro_oi, $eje_oi, $adicion_oi, $avs_lentes_oi, $avc_lentes_oi, $dip_oi, $altura_oi,
        $tipo_lente, $diagnostico, $observaciones, $medidas_anteriores
    );

    if ($stmt->execute()) {
        echo "<script>alert('✅ Historial guardado correctamente.'); window.location.href='panel_doctor.php';</script>";
    } else {
        echo "Error al guardar historial: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!-- FORMULARIO -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Historial Clínico</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: auto;
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #2c3e50;
        }

        label {
            display: block;
            margin-top: 15px;
            color: #34495e;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
        }

        .checkbox-group {
            margin-top: 10px;
        }

        .checkbox-group label {
            font-weight: normal;
            margin-right: 15px;
        }

        .form-group {
            margin-top: 20px;
        }

        .form-footer {
            text-align: right;
            margin-top: 30px;
        }

        button {
            background-color: #2979ff;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s ease;
        }

        button:hover {
            background-color: #186cd1;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #2979ff;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        hr {
            margin: 30px 0;
            border: 0;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>📝 Registrar Historial Clínico</h2>
    <form method="POST">
        <!-- Paciente -->
        <label for="paciente_id">Seleccionar paciente:</label>
        <select name="paciente_id" required>
            <option value="" disabled selected>-- Elige un paciente --</option>
            <?php foreach ($pacientes as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Fecha -->
        <label for="fecha">Fecha:</label>
        <input type="date" name="fecha" required>

        <hr>

        <!-- Ojo Derecho -->
        <div class="form-group">
            <h3>OD</h3>
            <label>Esfera:</label><input type="text" name="esfera_od">
            <label>Cilindro:</label><input type="text" name="cilindro_od">
            <label>Eje:</label><input type="text" name="eje_od">
            <label>Adición:</label><input type="text" name="adicion_od">
            <label>AVS:</label><input type="text" name="avs_lentes_od">
            <label>AVC:</label><input type="text" name="avc_lentes_od">
            <label>DIP:</label><input type="text" name="dip_od">
            <label>Altura:</label><input type="text" name="altura_od">
        </div>

        <!-- Ojo Izquierdo -->
        <div class="form-group">
            <h3>OI</h3>
            <label>Esfera:</label><input type="text" name="esfera_oi">
            <label>Cilindro:</label><input type="text" name="cilindro_oi">
            <label>Eje:</label><input type="text" name="eje_oi">
            <label>Adición:</label><input type="text" name="adicion_oi">
            <label>AVS:</label><input type="text" name="avs_lentes_oi">
            <label>AVC:</label><input type="text" name="avc_lentes_oi">
            <label>DIP:</label><input type="text" name="dip_oi">
            <label>Altura:</label><input type="text" name="altura_oi">
        </div>

        <!-- Tipo de lente -->
        <div class="form-group">
            <label>Tipo de lentes:</label>
            <div class="checkbox-group">
                <label><input type="checkbox" name="tipo_lente[]" value="blue ray"> Blue Ray</label>
                <label><input type="checkbox" name="tipo_lente[]" value="transition"> Transition</label>
                <label><input type="checkbox" name="tipo_lente[]" value="fotocromatico"> Fotocromático</label>
                <label><input type="checkbox" name="tipo_lente[]" value="bifocal"> Bifocal</label>
            </div>
        </div>

        <!-- Diagnóstico -->
        <label>Diagnóstico:</label>
        <input type="text" name="diagnostico">

        <!-- Observaciones -->
        <label>Observaciones:</label>
        <textarea name="observaciones" rows="4"></textarea>

        <!-- Medidas anteriores -->
        <label>Medidas anteriores:</label>
        <textarea name="medidas_anteriores" rows="3"></textarea>

        <!-- Botón -->
        <div class="form-footer">
            <button type="submit">Guardar historial</button>
        </div>
    </form>

    <a href="panel_doctor.php" class="back-link">← Volver al panel</a>
</div>

</body>
</html>
