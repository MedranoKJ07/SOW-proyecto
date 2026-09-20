<?php
require_once '../conexion.php';
$conn = conectarDB();

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descripcion = $_POST['descripcion'];
    $monto = $_POST['monto'];
    $fecha = $_POST['fecha'];
    $tipo = $_POST['tipo'];

    // 🚨 Validación: gasto mínimo C$100
    if ($monto < 100) {
        $mensaje = "❌ El gasto mínimo permitido es C$100.";
    } else {

        $query = "INSERT INTO gastos_operativos (descripcion, monto, fecha, tipo) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sdss", $descripcion, $monto, $fecha, $tipo);
        $stmt->execute();

        $idGasto = $stmt->insert_id;

        $queryCaja = "INSERT INTO movimientos_caja (tipo, descripcion, monto, fecha, id_gasto)
                      VALUES ('Egreso', ?, ?, ?, ?)";
        $stmtCaja = $conn->prepare($queryCaja);
        $stmtCaja->bind_param("sdsi", $descripcion, $monto, $fecha, $idGasto);
        $stmtCaja->execute();

        $mensaje = "✅ Gasto registrado correctamente.";
    }
}
?>


<div class="form-panel">
    <h2>Registrar Gasto Operativo</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje"><?= $mensaje ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Descripción:</label>
        <input type="text" name="descripcion" required>

        <label>Monto (C$):</label>
        <input type="number" name="monto" step="0.01" required>

        <label>Fecha:</label>
        <input type="date" name="fecha" required>

        <label>Tipo de gasto:</label>
        <select name="tipo" required>
            <option value="Luz">Luz</option>
            <option value="Agua">Agua</option>
            <option value="Sueldos">Sueldos</option>
            <option value="Internet">Internet</option>
            <option value="Otro">Otro</option>
        </select>

        <button type="submit">Registrar Gasto</button>
    </form>
</div>
