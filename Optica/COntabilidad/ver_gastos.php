<?php
require_once '../conexion.php';
$conn = conectarDB();

// Leer filtros
$tipo_filtro = $_GET['tipo'] ?? '';
$fecha_filtro = $_GET['fecha'] ?? '';
$descripcion_filtro = $_GET['descripcion'] ?? '';

// Construcción de la consulta
$query = "SELECT id_gasto, descripcion, monto, fecha, tipo FROM gastos_operativos WHERE 1";

if ($tipo_filtro !== '') {
    $query .= " AND tipo = '" . $conn->real_escape_string($tipo_filtro) . "'";
}
if ($fecha_filtro !== '') {
    $query .= " AND fecha = '" . $conn->real_escape_string($fecha_filtro) . "'";
}
if ($descripcion_filtro !== '') {
    $query .= " AND descripcion LIKE '%" . $conn->real_escape_string($descripcion_filtro) . "%'";
}

$query .= " ORDER BY fecha DESC";
$resultado = $conn->query($query);
?>

<!-- Filtro -->
<form method="GET" action="" class="filtro-gastos" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
    <input type="hidden" name="op" value="ver_gastos">

    <input type="text" name="descripcion" placeholder="Buscar descripción..." value="<?= htmlspecialchars($descripcion_filtro) ?>" style="padding: 8px; border-radius: 6px; border: 1px solid #ccc;">

    <select name="tipo" style="padding: 8px; border-radius: 6px;">
        <option value="">Tipo de gasto</option>
        <option value="Luz" <?= $tipo_filtro === 'Luz' ? 'selected' : '' ?>>Luz</option>
        <option value="Agua" <?= $tipo_filtro === 'Agua' ? 'selected' : '' ?>>Agua</option>
        <option value="Sueldos" <?= $tipo_filtro === 'Sueldos' ? 'selected' : '' ?>>Sueldos</option>
        <option value="Internet" <?= $tipo_filtro === 'Internet' ? 'selected' : '' ?>>Internet</option>
        <option value="Otro" <?= $tipo_filtro === 'Otro' ? 'selected' : '' ?>>Otro</option>
    </select>

    <input type="date" name="fecha" value="<?= htmlspecialchars($fecha_filtro) ?>" style="padding: 8px; border-radius: 6px;">

    <button type="submit" style="background: #2563EB; color: white; padding: 8px 16px; border: none; border-radius: 6px;">Filtrar</button>
    <a href="?op=ver_gastos" style="color: #2563EB; font-weight: bold; text-decoration: none; padding: 8px 16px;">Limpiar</a>
</form>

<!-- Resultados -->
<div class="tabla-panel">
    <h2>Gastos Operativos Registrados</h2>

    <?php if ($resultado && $resultado->num_rows > 0): ?>
        <table style="width: 100%; border-collapse: collapse; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
            <thead style="background: #f1f5f9;">
                <tr>
                    <th style="padding: 10px;">ID</th>
                    <th style="padding: 10px;">Descripción</th>
                    <th style="padding: 10px;">Monto (C$)</th>
                    <th style="padding: 10px;">Fecha</th>
                    <th style="padding: 10px;">Tipo</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($fila = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td style="padding: 8px; text-align: center;"><?= $fila['id_gasto'] ?></td>
                        <td style="padding: 8px;"><?= htmlspecialchars($fila['descripcion']) ?></td>
                        <td style="padding: 8px; text-align: right;"><?= number_format($fila['monto'], 2, '.', '') ?></td>
                        <td style="padding: 8px; text-align: center;"><?= $fila['fecha'] ?></td>
                        <td style="padding: 8px;"><?= $fila['tipo'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="mensaje" style="color: #666; margin-top: 20px;">🔍 No hay gastos registrados con los filtros seleccionados.</p>
    <?php endif; ?>
</div>

<!-- Botón de exportar -->
<a href="exportar_gastos_excel.php?tipo=<?= urlencode($tipo_filtro) ?>&fecha=<?= urlencode($fecha_filtro) ?>&descripcion=<?= urlencode($descripcion_filtro) ?>" 
   class="btn-exportar" 
   target="_blank">
   📤 Exportar a Excel
</a>
