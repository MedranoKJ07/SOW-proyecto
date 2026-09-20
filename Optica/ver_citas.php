<?php
// ver_citas.php (doctor)
session_start();
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'doctor') {
  header('Location: login.php'); exit;
}

require_once __DIR__.'/conexion.php';
$conn = conectarDB();

// Trae TODO para que el flujo avance en la misma pantalla
$sql = "SELECT c.*, p.nombre AS nombre_paciente
        FROM citas_medicas c
        JOIN pacientes p ON p.id = c.paciente_id
        WHERE c.estado IN ('pendiente','en_atencion','atendida')
        ORDER BY c.fecha ASC, c.hora ASC";
$rs = $conn->query($sql);

// Mensaje flash por querystring (opcional)
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8"><title>Citas médicas</title>
<style>
  body{font-family:Segoe UI,Arial,sans-serif;background:#f5f8ff;padding:40px}
  .back-btn{display:inline-block;margin-bottom:20px;background:#2979ff;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none}
  table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,.08)}
  th,td{padding:12px 14px;border-bottom:1px solid #e9eef6}
  th{background:#2979ff;color:#fff;text-align:left}
  .estado{font-weight:600;text-transform:capitalize}
  .pendiente{color:#ff9800}.en_atencion{color:#0288d1}.atendida{color:#2e7d32}.cancelada{color:#c62828}
  .row-actions{display:flex;gap:8px}
  form{display:inline}
  .btn{border:0;border-radius:6px;padding:6px 10px;color:#fff;cursor:pointer;font-weight:600}
  .btn-checkin{background:#6d4c41}
  .btn-iniciar{background:#0288d1}
  .btn-atender{background:#2e7d32}
  .muted{color:#9e9e9e}
  .msg{margin:0 0 16px 0;padding:10px 14px;border-radius:8px;background:#e8f5e9}
</style>
</head><body>

<a class="back-btn" href="panel_doctor.php">⬅ Panel del doctor</a>

<?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<h2>📆 Citas programadas</h2>
<table>
  <thead>
    <tr>
      <th>Paciente</th>
      <th>Fecha</th>
      <th>Hora</th>
      <th>Motivo</th>
      <th>Estado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
  <?php if ($rs && $rs->num_rows): while($row=$rs->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($row['nombre_paciente']) ?></td>
      <td><?= date('d/m/Y', strtotime($row['fecha'])) ?></td>
      <td><?= date('H:i', strtotime($row['hora'])) ?></td>
      <td><?= htmlspecialchars($row['motivo'] ?? '') ?></td>
      <td class="estado <?= $row['estado'] ?>"><?= $row['estado'] ?></td>
      <td class="row-actions">
        <?php if (empty($row['checkin_en'])): ?>
          <!-- 1) CHECK-IN -->
          <form method="post" action="marcar_checkin.php"
                onsubmit="return confirm('¿Confirmar check-in de la cita?')">
            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
            <button class="btn btn-checkin" type="submit">Check-in</button>
          </form>
        <?php endif; ?>

        <?php if (!empty($row['checkin_en']) && empty($row['inicio_atencion'])): ?>
          <!-- 2) INICIAR ATENCIÓN -->
          <form method="post" action="iniciar_atencion.php"
                onsubmit="return confirm('¿Iniciar atención de la cita?')">
            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
            <button class="btn btn-iniciar" type="submit">Iniciar</button>
          </form>
        <?php endif; ?>

        <?php if (!empty($row['inicio_atencion']) && $row['estado']!=='atendida'): ?>
          <!-- 3) MARCAR ATENDIDA -->
          <form method="post" action="marcar_atendida.php"
                onsubmit="return confirm('¿Marcar la cita como atendida?')">
            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
            <button class="btn btn-atender" type="submit">Atender</button>
          </form>
        <?php endif; ?>

        <?php if ($row['estado']==='atendida'): ?>
          <span class="muted">✔ Atendida</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endwhile; else: ?>
    <tr><td colspan="6">No hay citas.</td></tr>
  <?php endif; ?>
  </tbody>
</table>

</body></html>
