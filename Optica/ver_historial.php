<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'doctor') {
    header('Location: login.php');
    exit;
}

include_once 'conexion.php';
$conn = conectarDB();

$result = $conn->query("SELECT hc.*, p.nombre AS nombre_paciente 
                        FROM historial_clinico hc 
                        JOIN pacientes p ON hc.paciente_id = p.id 
                        ORDER BY hc.fecha DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial Clínico</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, #eef3f8, #f6fbff);
            margin: 0;
            padding: 40px 20px;
            color: #2c3e50;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }

        .table-container {
            max-width: 1200px;
            margin: 0 auto;
            overflow-x: auto;
            background-color: #ffffff;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            padding: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        thead {
            background-color: #3446b0;
            color: white;
            text-transform: none;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.3px;
        }

        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: top;
        }

        tr:nth-child(even) {
            background-color: #f9f9fc;
        }

        th.sticky {
            position: sticky;
            top: 0;
            background-color: #3446b0;
            z-index: 1;
        }

        .highlight {
            font-weight: bold;
            color: #1a237e;
        }

        td small {
            font-size: 12px;
            color: #888;
        }

        .volver {
            display: inline-block;
            margin: 30px auto 0;
            padding: 12px 24px;
            background-color: #3f51b5;
            color: #fff;
            font-weight: bold;
            border-radius: 10px;
            text-decoration: none;
            transition: 0.3s ease;
            text-align: center;
        }

        .volver:hover {
            background-color: #303f9f;
        }

        .tag {
            display: inline-block;
            background-color: #e3f2fd;
            color: #1976d2;
            font-weight: bold;
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 8px;
        }

        .scroll-hint {
            text-align: center;
            font-size: 13px;
            color: #888;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            body {
                padding: 20px 10px;
            }

            table {
                font-size: 13px;
            }

            .volver {
                display: block;
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <h2>Historial Clínico de Pacientes</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th class="sticky">Paciente</th>
                    <th class="sticky">Fecha</th>
                    <th class="sticky">OD (Esf / Cil / Eje)</th>
                    <th class="sticky">Adición / AVS / AVC OD</th>
                    <th class="sticky">DIP / Altura OD</th>
                    <th class="sticky">OI (Esf / Cil / Eje)</th>
                    <th class="sticky">Adición / AVS / AVC OI</th>
                    <th class="sticky">DIP / Altura OI</th>
                    <th class="sticky">Tipo de Lente</th>
                    <th class="sticky">Diagnóstico</th>
                    <th class="sticky">Observaciones</th>
                    <th class="sticky">Medidas Anteriores</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class="highlight"><?= htmlspecialchars($row['nombre_paciente']) ?></td>
                    <td><?= date('d/m/Y', strtotime($row['fecha'])) ?></td>

                    <td><?= $row['esfera_od'].' / '.$row['cilindro_od'].' / '.$row['eje_od'] ?></td>
                    <td>
                        <?= $row['adicion_od'] ?><br>
                        <small>AVS: <?= $row['avs_lentes_od'] ?> / AVC: <?= $row['avc_lentes_od'] ?></small>
                    </td>
                    <td><?= $row['dip_od'] ?> / <?= $row['altura_od'] ?></td>

                    <td><?= $row['esfera_oi'].' / '.$row['cilindro_oi'].' / '.$row['eje_oi'] ?></td>
                    <td>
                        <?= $row['adicion_oi'] ?><br>
                        <small>AVS: <?= $row['avs_lentes_oi'] ?> / AVC: <?= $row['avc_lentes_oi'] ?></small>
                    </td>
                    <td><?= $row['dip_oi'] ?> / <?= $row['altura_oi'] ?></td>

                    <td><span class="tag"><?= htmlspecialchars($row['tipo_lente']) ?></span></td>
                    <td><?= htmlspecialchars($row['diagnostico']) ?></td>
                    <td><?= nl2br(htmlspecialchars($row['observaciones'])) ?></td>
                    <td><?= nl2br(htmlspecialchars($row['medidas_anteriores'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="scroll-hint">← Desliza horizontalmente si no ves toda la tabla →</div>
    </div>

    <div style="text-align: center;">
        <a class="volver" href="panel_doctor.php">← Volver al panel</a>
    </div>

</body>
</html>
