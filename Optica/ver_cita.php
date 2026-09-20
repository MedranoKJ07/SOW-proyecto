<?php
// ver_cita.php (secretaria)
session_start();
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'secretaria') {
  header('Location: login.php'); exit;
}

require_once __DIR__.'/conexion.php';
$conn = conectarDB();

$id = filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if(!$id){ exit('Cita no encontrada.'); }

$stmt = $conn->prepare("SELECT c.*, p.nombre, p.telefono, p.cedula
                        FROM citas_medicas c
                        JOIN pacientes p ON p.id = c.paciente_id
                        WHERE c.id = ?");
$stmt->bind_param("i",$id);
$stmt->execute();
$cita = $stmt->get_result()->fetch_assoc();
if(!$cita){ exit('Cita no encontrada.'); }
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8"><title>Detalles de la Cita</title>
<style>
  body{font-family:Segoe UI,Tahoma,Arial,sans-serif;background:#f4f6f9;padding:40px}
  .container{background:#fff;max-width:640px;margin:auto;padding:28px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.1)}
  h2{color:#1a237e;margin:0 0 16px}
  p{margin:8px 0}
  a{display:inline-block;margin-top:16px;color:#1976d2;text-decoration:none}
  a:hover{text-decoration:underline}
</style>
</head><body>
<div class="container">
  <h2>Detalles de la Cita</h2>
  <p><strong>Paciente:</strong> <?= htmlspecialchars($cita['nombre']) ?></p>
  <p><strong>Teléfono:</strong> <?= htmlspecialchars($cita['telefono']) ?></p>
  <p><strong>Cédula:</strong> <?= htmlspecialchars($cita['cedula'] ?? 'No registrada') ?></p>
  <p><strong>Fecha:</strong> <?= htmlspecialchars($cita['fecha']) ?></p>
  <p><strong>Hora:</strong> <?= htmlspecialchars($cita['hora']) ?></p>
  <p><strong>Motivo:</strong> <?= htmlspecialchars($cita['motivo'] ?? '') ?></p>
  <a href="panel_secretaria.php">← Volver</a>
</div>
</body></html>
