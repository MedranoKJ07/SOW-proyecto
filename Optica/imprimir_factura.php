<?php
require_once('conexion.php');
$conn = conectarDB();

$id_factura = $_GET['id'] ?? null;

if (!$id_factura) {
    echo "Factura no encontrada.";
    exit;
}

$sql = "
SELECT 
    f.*, 
    p.nombre, p.telefono, p.edad, 
    h.tipo_lente AS h_tipo_lente,
    h.esfera_od AS h_esfera_od, h.cilindro_od AS h_cilindro_od, h.eje_od AS h_eje_od,
    h.adicion_od AS h_adicion_od, h.altura_od AS h_altura_od, h.dip_od AS h_dip_od,
    h.avs_lentes_od AS h_avs_lentes_od, h.avc_lentes_od AS h_avc_lentes_od,
    h.esfera_oi AS h_esfera_oi, h.cilindro_oi AS h_cilindro_oi, h.eje_oi AS h_eje_oi,
    h.adicion_oi AS h_adicion_oi, h.altura_oi AS h_altura_oi, h.dip_oi AS h_dip_oi,
    h.avs_lentes_oi AS h_avs_lentes_oi, h.avc_lentes_oi AS h_avc_lentes_oi,
    h.diagnostico AS h_diagnostico, h.observaciones AS h_observaciones
FROM facturas f
JOIN pacientes p ON f.paciente_id = p.id
LEFT JOIN historial_clinico h ON h.paciente_id = p.id
WHERE f.id = ?
ORDER BY h.id DESC
LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_factura);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Factura no encontrada.";
    exit;
}

$factura = $result->fetch_assoc();
function safe($key) {
    global $factura;
    return htmlspecialchars($factura[$key] ?? '');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background-color: #f9f9f9;
        }

        .factura {
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }

        h2, h3 {
            color: #1e293b;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 15px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
        }

        th {
            background-color: #e2e8f0;
            color: #1f2937;
            text-align: left;
        }

        .btn-imprimir {
            margin-top: 30px;
            padding: 10px 20px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        @media print {
            .btn-imprimir {
                display: none;
            }
        }
        .btn-volver {
    display: inline-block;
    margin-top: 20px;
    padding: 10px 20px;
    background-color: #64748B;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
}
.btn-volver:hover {
    background-color: #475569;
}
    </style>
</head>
<body>
<a href="panel_secretaria.php"class="btn-volver">volver al Inicio</a>
<div class="factura">
    <h2>Óptica Digital - Factura #<?= safe('id') ?></h2>

    <h3>🧍 Datos del Paciente</h3>
    <p><strong>Nombre:</strong> <?= safe('nombre') ?></p>
    <p><strong>Teléfono:</strong> <?= safe('telefono') ?></p>
    <p><strong>Edad:</strong> <?= safe('edad') ?> años</p>

    <h3>🧾 Detalles de Factura</h3>
    <p><strong>Fecha:</strong> <?= safe('fecha') ?></p>
    <p><strong>Total:</strong> C$ <?= number_format($factura['total'], 2) ?></p>
    <p><strong>Deuda:</strong> C$ <?= number_format($factura['deuda'], 2) ?></p>
    

    <h3> Resumen Óptico</h3>
    <table>
        <tr>
            <th>Campo</th>
            <th>OD (Derecho)</th>
            <th>OI (Izquierdo)</th>
        </tr>
        <tr><td>Esfera</td><td><?= safe('h_esfera_od') ?></td><td><?= safe('h_esfera_oi') ?></td></tr>
        <tr><td>Cilindro</td><td><?= safe('h_cilindro_od') ?></td><td><?= safe('h_cilindro_oi') ?></td></tr>
        <tr><td>Eje</td><td><?= safe('h_eje_od') ?></td><td><?= safe('h_eje_oi') ?></td></tr>
        <tr><td>Adición</td><td><?= safe('h_adicion_od') ?></td><td><?= safe('h_adicion_oi') ?></td></tr>
        <tr><td>Altura</td><td><?= safe('h_altura_od') ?></td><td><?= safe('h_altura_oi') ?></td></tr>
        <tr><td>DIP</td><td><?= safe('h_dip_od') ?></td><td><?= safe('h_dip_oi') ?></td></tr>
        <tr><td>AVS</td><td><?= safe('h_avs_lentes_od') ?></td><td><?= safe('h_avs_lentes_oi') ?></td></tr>
        <tr><td>AVC</td><td><?= safe('h_avc_lentes_od') ?></td><td><?= safe('h_avc_lentes_oi') ?></td></tr>
        <tr><td>Tipo de Lente</td><td colspan="2"><?= safe('h_tipo_lente') ?></td></tr>
        <tr><td>Diagnóstico</td><td colspan="2"><?= safe('h_diagnostico') ?></td></tr>
        <tr><td>Observaciones</td><td colspan="2"><?= safe('h_observaciones') ?></td></tr>
    </table>
    
    <button class="btn-imprimir" onclick="window.print()">🖨 Imprimir Factura</button>
</div>

</body>
</html>

