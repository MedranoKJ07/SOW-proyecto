<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'doctor') {
    header('Location: login.php');
    exit;
}

include 'conexion.php';
$conn = conectarDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pago'])) {
    $factura_id = $_POST['factura_id'];
    $monto_pago = floatval($_POST['monto_pago']);
    $metodo_pago = $_POST['metodo_pago'];
    $fecha_pago = date('Y-m-d');

    // Insertar el pago
    $sql_pago = "INSERT INTO pagos (factura_id, fecha_pago, monto, metodo_pago) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql_pago);
    $stmt->bind_param("isds", $factura_id, $fecha_pago, $monto_pago, $metodo_pago);
    $stmt->execute();
    $stmt->close();

    // Obtener deuda actual
    $sql_deuda = "SELECT deuda FROM facturas WHERE id = ?";
    $stmt = $conn->prepare($sql_deuda);
    $stmt->bind_param("i", $factura_id);
    $stmt->execute();
    $stmt->bind_result($deuda_actual);
    $stmt->fetch();
    $stmt->close();

    $nueva_deuda = $deuda_actual - $monto_pago;
    $estado = ($nueva_deuda <= 0) ? 'pagado' : 'pendiente';

    // Actualizar factura
    $sql_actualizar = "UPDATE facturas SET deuda = ?, estado_pago = ? WHERE id = ?";
    $stmt = $conn->prepare($sql_actualizar);
    $stmt->bind_param("dsi", $nueva_deuda, $estado, $factura_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Pago registrado exitosamente'); location.href='ver_facturas.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Facturas con Deuda - Doctor</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #eef4fb;
            padding: 40px;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #1d4ed8;
            color: white;
        }

        .btn {
            background: #059669;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn:hover {
            background: #047857;
        }

        .panel-link {
            background: #1d4ed8;
            color: white;
            padding: 10px 18px;
            display: inline-block;
            margin-bottom: 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }

        #formularioPago {
            display: none;
            background: #fff;
            max-width: 500px;
            margin: 30px auto;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        #formularioPago input, #formularioPago select, #formularioPago button {
            width: 100%;
            padding: 12px;
            margin-top: 12px;
            border: 1px solid #ccc;
            border-radius: 10px;
        }

        #formularioPago button {
            background-color: #1d4ed8;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>

<a href="panel_doctor.php" class="panel-link">← Volver al Panel del Doctor</a>

<h2>Facturas con Deuda Pendiente</h2>

<table>
    <thead>
        <tr>
            <th>Paciente</th>
            <th>Tipo de Lente</th>
            <th>Total</th>
            <th>Deuda</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sql = "SELECT f.id, p.nombre, f.tipo_lente, f.total, f.deuda 
                FROM facturas f
                INNER JOIN pacientes p ON f.paciente_id = p.id
                WHERE f.deuda > 0
                ORDER BY f.fecha DESC";

        $resultado = $conn->query($sql);

        if ($resultado->num_rows > 0) {
            while ($row = $resultado->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['nombre'] ?? '') . "</td>";

                echo "<td>" . htmlspecialchars($row['tipo_lente'] ?? '') . "</td>";

                echo "<td>C$ " . number_format($row['total'], 2) . "</td>";
                echo "<td>C$ " . number_format($row['deuda'], 2) . "</td>";
                echo "<td><button class='btn' onclick='mostrarFormularioPago(" . $row['id'] . ")'>Registrar Pago</button></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No hay facturas con deuda pendiente.</td></tr>";
        }
        ?>
    </tbody>
</table>

<div id="formularioPago">
    <h3>Registrar Pago</h3>
    <form method="POST">
        <input type="hidden" name="factura_id" id="factura_id">
        <label for="monto_pago">Monto a pagar:</label>
        <input type="number" name="monto_pago" step="0.01" required>
        <label for="metodo_pago">Método de Pago:</label>
        <select name="metodo_pago" required>
            <option value="efectivo">Efectivo</option>
            <option value="tarjeta">Tarjeta</option>
            <option value="transferencia">Transferencia</option>
        </select>
        <button type="submit" name="pago">Registrar</button>
    </form>
</div>

<script>
function mostrarFormularioPago(id) {
    document.getElementById('factura_id').value = id;
    document.getElementById('formularioPago').style.display = 'block';
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
}
</script>

</body>
</html>
