<?php
date_default_timezone_set('America/Managua'); // Ajusta la zona horaria según tu ubicación.2
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'doctor' || !isset($_SESSION['paciente_id'])) {
    echo "Acceso no autorizado.";
    exit;
}

include('conexion.php');
$conn = conectarDB();

$id_paciente = $_SESSION['paciente_id'];
$nombre_paciente = $_SESSION['paciente_nombre'] ?? 'Desconocido';

$stmtHistorial = $conn->prepare("SELECT * FROM historial_clinico WHERE paciente_id = ?");
$stmtHistorial->bind_param("i", $id_paciente);
$stmtHistorial->execute();
$resultado = $stmtHistorial->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial Clínico - <?php echo htmlspecialchars($nombre_paciente); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background-color: #fff;
            color: #000;
        }
        .historial-item {
            margin-bottom: 40px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 20px;
        }
        h2, h3, h4 {
            margin-bottom: 5px;
        }
        p {
            margin: 3px 0;
        }
        .header {
            text-align: center;
            margin-bottom: 50px;
        }
    </style>
</head>
<body onload="window.print()">

<div class="header">
    <h2>Historial Clínico de:</h2>
    <h3>👤 Nombre: <?php echo htmlspecialchars($nombre_paciente); ?></h3>
    

    <p>🗓️ Fecha de impresión: <?php echo date('d/m/Y H:i'); ?></p>
</div>

<?php if ($resultado->num_rows > 0): ?>
    <?php while ($row = $resultado->fetch_assoc()): ?>
        <div class="historial-item">
            <h4>📅 Fecha: <?php echo $row['fecha']; ?></h4>
            <p><strong>Tipo de Lente:</strong> <?php echo $row['tipo_lente']; ?></p>
            <p><strong>Diagnóstico:</strong> <?php echo $row['diagnostico']; ?></p>
            <p><strong>Observaciones:</strong> <?php echo $row['observaciones']; ?></p>
            <p><strong>Medidas anteriores:</strong> <?php echo $row['medidas_anteriores']; ?></p>

            <table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse; width: 100%; text-align: left;">
    <thead>
        <tr>
            <th style="background-color: #f2f2f2; text-align: center;">Características</th>
            <th style="background-color: #f2f2f2; text-align: center;">(OD)</th>
            <th style="background-color: #f2f2f2; text-align: center;">(OI)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Esfera</strong></td>
            <td><?php echo $row['esfera_od']; ?></td>
            <td><?php echo $row['esfera_oi']; ?></td>
        </tr>
        <tr>
            <td><strong>Cilindro</strong></td>
            <td><?php echo $row['cilindro_od']; ?></td>
            <td><?php echo $row['cilindro_oi']; ?></td>
        </tr>
        <tr>
            <td><strong>Eje</strong></td>
            <td><?php echo $row['eje_od']; ?></td>
            <td><?php echo $row['eje_oi']; ?></td>
        </tr>
        <tr>
            <td><strong>Adición</strong></td>
            <td><?php echo $row['adicion_od']; ?></td>
            <td><?php echo $row['adicion_oi']; ?></td>
        </tr>
        <tr>
            <td><strong>AVS</strong></td>
            <td><?php echo $row['avs_lentes_od']; ?></td>
            <td><?php echo $row['avs_lentes_oi']; ?></td>
        </tr>
        <tr>
            <td><strong>AVC</strong></td>
            <td><?php echo $row['avc_lentes_od']; ?></td>
            <td><?php echo $row['avc_lentes_oi']; ?></td>
        </tr>
        <tr>
            <td><strong>DIP</strong></td>
            <td><?php echo $row['dip_od']; ?></td>
            <td><?php echo $row['dip_oi']; ?></td>
        </tr>
        <tr>
            <td><strong>Altura</strong></td>
            <td><?php echo $row['altura_od']; ?></td>
            <td><?php echo $row['altura_oi']; ?></td>
        </tr>
    </tbody>
</table>

    <?php endwhile; ?>
<?php else: ?>
    <p>Este paciente no tiene historial clínico registrado.</p>
<?php endif; ?>

</body>
</html>
