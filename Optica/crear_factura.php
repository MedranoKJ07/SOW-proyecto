<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'secretaria') {
    header('Location: login.php');
    exit;
}

include('conexion.php');
$conn = conectarDB();

$paciente = null;
$historial = null;
$deuda = 0.00;

// Buscar paciente y traer último historial clínico
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paciente_nombre'])) {
    $paciente_nombre = $_POST['paciente_nombre'];
    $sql = "SELECT * FROM pacientes WHERE nombre LIKE ?";
    $stmt = $conn->prepare($sql);
    $nombre_like = "%$paciente_nombre%";
    $stmt->bind_param('s', $nombre_like);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $paciente = $result->fetch_assoc();

        $sql_historial = "SELECT * FROM historial_clinico WHERE paciente_id = ? ORDER BY fecha DESC LIMIT 1";
        $stmt_historial = $conn->prepare($sql_historial);
        $stmt_historial->bind_param('i', $paciente['id']);
        $stmt_historial->execute();
        $historial_result = $stmt_historial->get_result();
        if ($historial_result->num_rows > 0) {
            $historial = $historial_result->fetch_assoc();
        }

        $sql_deuda = "SELECT deuda FROM facturas WHERE paciente_id = ? ORDER BY fecha DESC LIMIT 1";
        $stmt_deuda = $conn->prepare($sql_deuda);
        $stmt_deuda->bind_param('i', $paciente['id']);
        $stmt_deuda->execute();
        $deuda_result = $stmt_deuda->get_result();
        if ($deuda_result->num_rows > 0) {
            $deuda = $deuda_result->fetch_assoc()['deuda'];
        }
    }
}

// Crear factura
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_factura'])) {
    $paciente_id = $_POST['paciente_id'];
    $fecha = $_POST['fecha'];
    $total = floatval($_POST['total']);
    $deuda = floatval($_POST['deuda']);
    $tipo_pago = $_POST['tipo_pago'];

    if ($tipo_pago === 'abono' && isset($_POST['abono'])) {
        $abono = floatval($_POST['abono']);
        $deuda = $total - $abono;
    }

    $estado_pago = ($deuda <= 0) ? 'pagado' : 'pendiente';

    // Tomar los datos clínicos del último historial
    $tipo_lente = $_POST['tipo_lente'] ?? null;
    $esfera_od = $_POST['esfera_od'] ?? null;
    $cilindro_od = $_POST['cilindro_od'] ?? null;
    $eje_od = $_POST['eje_od'] ?? null;
    $esfera_oi = $_POST['esfera_oi'] ?? null;
    $cilindro_oi = $_POST['cilindro_oi'] ?? null;
    $eje_oi = $_POST['eje_oi'] ?? null;
    $adicion = $_POST['adicion'] ?? null;
    $avs_lentes = $_POST['avs_lentes'] ?? null;
    $avc_lentes = $_POST['avc_lentes'] ?? null;
    $dip = $_POST['dip'] ?? null;
    $altura = $_POST['altura'] ?? null;
    $diagnostico = $_POST['diagnostico'] ?? null;
    $observaciones = $_POST['observaciones'] ?? null;
    $medidas_anteriores = $_POST['medidas_anteriores'] ?? null;

    $stmt_factura = $conn->prepare("
        INSERT INTO facturas (
            paciente_id, fecha, tipo_lente, esfera_od, cilindro_od, eje_od,
            esfera_oi, cilindro_oi, eje_oi, adicion, avs_lentes, avc_lentes,
            dip, altura, diagnostico, observaciones, medidas_anteriores,
            total, deuda, estado_pago
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt_factura->bind_param(
        'issssssssssssssssdds',
        $paciente_id, $fecha, $tipo_lente, $esfera_od, $cilindro_od, $eje_od,
        $esfera_oi, $cilindro_oi, $eje_oi, $adicion, $avs_lentes, $avc_lentes,
        $dip, $altura, $diagnostico, $observaciones, $medidas_anteriores,
        $total, $deuda, $estado_pago
    );

    $stmt_factura->execute();
    $factura_id = $conn->insert_id;

    echo "<script>
        alert('Factura creada correctamente');
        window.location.href = 'imprimir_factura.php?id=$factura_id';
    </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Factura</title>
    <link rel="stylesheet" href="estilos_factura.css">
</head>
<body>
<a href="panel_secretaria.php">← Volver al panel</a>
<div class="container">
    <h2>Buscar Paciente</h2>
    <form method="POST">
        <label for="paciente_nombre">Nombre del Paciente:</label>
        <input list="pacientes" name="paciente_nombre" required autocomplete="off">
        <datalist id="pacientes">
            <?php
            $query = $conn->query("SELECT nombre FROM pacientes ORDER BY nombre ASC");
            while ($row = $query->fetch_assoc()) {
                echo "<option value=\"" . htmlspecialchars($row['nombre']) . "\">";
            }
            ?>
        </datalist>
        <button type="submit">Buscar</button>
    </form>

<?php if ($paciente && $historial): ?>
    <hr>
    <h3>Crear Factura para <?= htmlspecialchars($paciente['nombre']) ?></h3>
    <form method="POST">
        <input type="hidden" name="paciente_id" value="<?= $paciente['id'] ?>">

        <label>Fecha:</label>
        <input type="date" name="fecha" required>

        <label>Costo de Consulta:</label>
        <input type="number" step="0.01" id="costo_consulta" required>

        <label>Costo de Lentes:</label>
        <input type="number" step="0.01" id="costo_lentes" required>

        <label>Total:</label>
        <input type="number" step="0.01" name="total" id="costo_total" readonly>

        <label>Tipo de Pago:</label>
        <select name="tipo_pago" id="tipo_pago" onchange="mostrarAbono()">
            <option value="completo">Pago Completo</option>
            <option value="abono">Abono</option>
        </select>

        <div id="abono_div" style="display:none;">
            <label>Abono:</label>
            <input type="number" step="0.01" name="abono" id="abono" oninput="calcularRestante()">
        </div>

        <label>Deuda:</label>
        <input type="number" name="deuda" id="deuda" readonly>

        <h4>Datos Clínicos (cargados automáticamente)</h4>

        <?php
        function campo($name, $label, $value) {
            echo "<label>$label:</label>";
            echo "<input type='text' name='$name' value='" . htmlspecialchars($value) . "' readonly>";
        }

        campo('tipo_lente', 'Tipo de lente', $historial['tipo_lente']);
        campo('esfera_od', 'Esfera OD', $historial['esfera_od']);
        campo('cilindro_od', 'Cilindro OD', $historial['cilindro_od']);
        campo('eje_od', 'Eje OD', $historial['eje_od']);
        campo('esfera_oi', 'Esfera OI', $historial['esfera_oi']);
        campo('cilindro_oi', 'Cilindro OI', $historial['cilindro_oi']);
        campo('eje_oi', 'Eje OI', $historial['eje_oi']);
        campo('adicion', 'Adición', $historial['adicion_od']);
        campo('avs_lentes', 'AVS Lentes', $historial['avs_lentes_od']);
        campo('avc_lentes', 'AVC Lentes', $historial['avc_lentes_od']);
        campo('dip', 'DIP', $historial['dip_od']);
        campo('altura', 'Altura', $historial['altura_od']);
        campo('diagnostico', 'Diagnóstico', $historial['diagnostico']);
        echo "<label>Medidas anteriores:</label><textarea name='medidas_anteriores' readonly>" . htmlspecialchars($historial['medidas_anteriores']) . "</textarea>";
        echo "<label>Observaciones:</label><textarea name='observaciones' readonly>" . htmlspecialchars($historial['observaciones']) . "</textarea>";
        ?>

        <button type="submit" name="crear_factura">Crear Factura</button>
    </form>
<?php endif; ?>
</div>

<script>
function mostrarAbono() {
    const tipoPago = document.getElementById('tipo_pago').value;
    document.getElementById('abono_div').style.display = tipoPago === 'abono' ? 'block' : 'none';
    calcularRestante();
}

function calcularTotal() {
    const consulta = parseFloat(document.getElementById('costo_consulta').value) || 0;
    const lentes = parseFloat(document.getElementById('costo_lentes').value) || 0;
    const total = consulta + lentes;
    document.getElementById('costo_total').value = total.toFixed(2);
    if (document.getElementById('tipo_pago').value === 'completo') {
        document.getElementById('deuda').value = '0.00';
    }
}

function calcularRestante() {
    const total = parseFloat(document.getElementById('costo_total').value) || 0;
    const abono = parseFloat(document.getElementById('abono').value) || 0;
    document.getElementById('deuda').value = (total - abono).toFixed(2);
}

document.getElementById('costo_consulta').addEventListener('input', calcularTotal);
document.getElementById('costo_lentes').addEventListener('input', calcularTotal);
</script>
</body>
</html>
