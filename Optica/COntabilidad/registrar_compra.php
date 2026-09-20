<?php
require_once '../conexion.php';
$conn = conectarDB();

// Obtener proveedores para el <select>
$proveedores = $conn->query("SELECT id_proveedor, nombre FROM proveedores");
?>

<div class="form-panel">
    <h2>Registrar Compra</h2>

    <form method="POST" action="guardar_compra.php">
        <label>Proveedor:</label>
        <select name="id_proveedor" required>
            <option value="">Seleccione un proveedor</option>
            <?php while ($p = $proveedores->fetch_assoc()): ?>
                <option value="<?= $p['id_proveedor'] ?>"><?= $p['nombre'] ?></option>
            <?php endwhile; ?>
        </select>

        <label>Fecha de Compra:</label>
        <input type="date" name="fecha" required>

        <hr>
        <h3>Productos comprados</h3>

        <div id="productos">
            <div class="producto-item">
                <input type="text" name="producto[]" placeholder="Nombre del producto" required>
                <input type="number" name="cantidad[]" placeholder="Cantidad" min="1" required>
                <input type="number" name="costo_unitario[]" placeholder="Costo unitario" step="0.01" required>
                <button type="button" onclick="eliminarProducto(this)">Eliminar producto</button>
            </div>
        </div>

        <button type="button" onclick="agregarProducto()">Agregar otro producto</button>

        <br><br>
        <button type="submit">Registrar Compra</button>
    </form>
</div>

<script>
function agregarProducto() {
    const div = document.createElement('div');
    div.classList.add('producto-item');
    div.innerHTML = `
        <input type="text" name="producto[]" placeholder="Nombre del producto" required>
        <input type="number" name="cantidad[]" placeholder="Cantidad" min="1" required>
        <input type="number" name="costo_unitario[]" placeholder="Costo unitario" step="0.01" required>
        <button type="button" onclick="eliminarProducto(this)">eliminar </button>
    `;
    document.getElementById('productos').appendChild(div);
}

function eliminarProducto(btn) {
    btn.parentElement.remove();
}
</script>
