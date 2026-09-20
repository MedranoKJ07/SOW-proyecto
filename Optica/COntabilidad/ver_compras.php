<?php
session_start();
require_once '../conexion.php';

$conn = conectarDB();
if (!$conn) { die('Error de conexión'); }
$conn->set_charset('utf8mb4');

// Consulta SOLO de compras (sin unir detalle)
$sql = "
  SELECT 
    c.id_compra,
    DATE(c.fecha) AS fecha,
    c.total,
    p.nombre AS proveedor
  FROM compras c
  INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
  ORDER BY c.id_compra DESC
";

$resultado = $conn->query($sql);
if ($resultado === false) {
  die('Error en la consulta: ' . $conn->error);
}
?>
<h2>Compras Registradas</h2>

<?php if ($resultado->num_rows > 0): ?>
  <table style="width: 100%; border-collapse: collapse;">
    <thead style="background: #f1f5f9;">
      <tr>
        <th style="padding: 10px; border:1px solid #e5e7eb;">ID</th>
        <th style="padding: 10px; border:1px solid #e5e7eb;">Proveedor</th>
        <th style="padding: 10px; border:1px solid #e5e7eb;">Fecha</th>
        <th style="padding: 10px; border:1px solid #e5e7eb;">Total (C$)</th>
        <th style="padding: 10px; border:1px solid #e5e7eb;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $resultado->fetch_assoc()): ?>
        <tr>
          <td style="text-align:center; border:1px solid #e5e7eb;"><?= (int)$row['id_compra'] ?></td>
          <td style="border:1px solid #e5e7eb;"><?= htmlspecialchars($row['proveedor']) ?></td>
          <td style="text-align:center; border:1px solid #e5e7eb;"><?= htmlspecialchars($row['fecha']) ?></td>
          <td style="text-align:right; border:1px solid #e5e7eb;"><?= number_format((float)$row['total'], 2) ?></td>
          <td style="text-align:center; border:1px solid #e5e7eb;">
            <button class="detalle-btn" data-id="<?= (int)$row['id_compra'] ?>" style="padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; background:#fff; cursor:pointer;">
              Ver detalle
            </button>
          </td>
        </tr>
        <tr class="detalle-row" id="detalle-<?= (int)$row['id_compra'] ?>" style="display:none;">
          <td colspan="5" style="background:#f9fafb; border:1px solid #e5e7eb;">
            <div class="detalle-content" style="padding:10px; font-size:14px;">Cargando…</div>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
<?php else: ?>
  <p style="margin-top:20px; color:#666;">No hay compras registradas todavía.</p>
<?php endif; ?>

<a href="exportar_compras_excel.php"
   class="btn-exportar"
   style="display:inline-block; margin-top:20px; background:#2563EB; color:white; padding:10px 20px; border-radius:8px; text-decoration:none;">
  📤 Exportar a Excel
</a>

<script>
// Toggle + carga vía fetch
document.querySelectorAll('.detalle-btn').forEach(boton => {
  boton.addEventListener('click', async () => {
    const id = boton.dataset.id;
    const fila = document.getElementById('detalle-' + id);
    const contenido = fila.querySelector('.detalle-content');

    if (fila.style.display === 'none') {
      contenido.textContent = 'Cargando…';
      try {
        const res = await fetch('detalle_compra.php?id=' + encodeURIComponent(id));
        const html = await res.text();
        contenido.innerHTML = html;
      } catch (e) {
        contenido.textContent = 'Error al cargar el detalle.';
      }
      fila.style.display = '';
      boton.textContent = 'Ocultar detalle';
    } else {
      fila.style.display = 'none';
      boton.textContent = 'Ver detalle';
    }
  });
});
</script>
