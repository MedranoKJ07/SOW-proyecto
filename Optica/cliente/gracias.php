<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/helpers.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (empty($_SESSION['ultima_cita'])) { 
  header('Location: ./index.php'); 
  exit; 
}

$cita_id = (int)$_SESSION['ultima_cita'];

$conn = conectarDB(); 
$conn->set_charset('utf8mb4');

$sql = "SELECT id, fecha, hora, motivo, tipo FROM citas_medicas WHERE id=?";
$stmt = $conn->prepare($sql); 
$stmt->bind_param("i", $cita_id); 
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();
if(!$info){ die('Cita no encontrada'); }

$nombreServicio = [
  'primera' => 'Consulta general',
  'control' => 'Examen de la vista',
  'entrega' => 'Retiro/entrega de lentes',
  'otro'    => 'Otro'
][$info['tipo']] ?? 'Servicio';

// Número de WhatsApp de la clínica (solo dígitos, sin + ni espacios)
$numeroClinica = '58124610'; // cambia por el tuyo

// Nombre del cliente (desde sesión o genérico)
$nombreCliente = $_SESSION['ultima_cita_nombre'] ?? 'cliente';

// Mensaje personalizado
$msg = rawurlencode("Hola, soy $nombreCliente. Confirmo mi cita de $nombreServicio el {$info['fecha']} a las {$info['hora']}. aviso de algún cambio ha este numero");
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cita confirmada | Óptica West</title>
  <link rel="stylesheet" href="./styless.css">
</head>
<body>
<div class="container">
  <div class="card" style="text-align:center; padding:32px 20px;">
    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#60a5fa" style="margin-bottom:12px">
      <circle cx="12" cy="12" r="9" stroke="#60a5fa" stroke-width="1.5"/>
      <path stroke="#60a5fa" stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25L15 9.75"/>
    </svg>
    <h2 style="margin:0 0 8px; font-size:1.4rem;">¡Tu cita fue registrada con éxito!</h2>
    <p class="small" style="margin-bottom:24px;">Guarda los detalles y confirma por WhatsApp para asegurar tu espacio.</p>

    <div style="text-align:left; max-width:400px; margin:0 auto; font-size:1rem; color:#e2e8f0;">
      <p><b>Servicio:</b> <?= h($nombreServicio) ?></p>
      <p><b>Fecha:</b> <?= h($info['fecha']) ?></p>
      <p><b>Hora:</b> <?= h($info['hora']) ?></p>
      <?php if(!empty($info['motivo'])): ?>
        <p><b>Motivo:</b> <?= h($info['motivo']) ?></p>
      <?php endif; ?>
    </div>

    <div style="margin-top:28px; display:flex; flex-direction:column; gap:12px; align-items:center;">
      <a class="btn" href="https://wa.me/505<?= $numeroClinica ?>?text=<?= $msg ?>" target="_blank" rel="noopener">
        📱 Confirmar por WhatsApp
      </a>
      <a class="btn secondary" href="./index.php">Agendar otra cita</a>
    </div>
  </div>
</div>
</body>
</html>
