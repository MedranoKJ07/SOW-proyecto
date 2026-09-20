<?php
// Administrador/ver_cita.php (solo lectura para Admin)
session_start();
require_once __DIR__ . '/../conexion.php';

if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../login.php'); exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { die('ID inválido'); }

$db = conectarDB(); if(!$db){ die('Error de conexión'); }
$db->set_charset('utf8mb4');

$sql = "SELECT 
          c.id, c.fecha, c.hora, c.estado, c.motivo, c.tipo, c.origen,
          c.checkin_en, c.inicio_atencion, c.fin_atencion,
          c.diagnostico, c.indicaciones, c.notas,
          p.nombre AS paciente, p.telefono, p.edad, p.cedula,
          u.nombre AS doctor
        FROM citas_medicas c
        JOIN pacientes p ON p.id = c.paciente_id
        LEFT JOIN usuarios u ON u.id = c.doctor_id
        WHERE c.id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('i',$id);
$stmt->execute();
$res = $stmt->get_result();
$C = $res? $res->fetch_assoc() : null;
if (!$C) { die('Cita no encontrada'); }

function h($s){ return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Detalle de cita</title>
<link rel="stylesheet" href="dashboard_Admin.css?v=8">
<style>
  :root{
    --azul:#1f55ff;
    --azul-600:#2a5fff;
    --negro:#0b1020;
    --borde:#e5e9f2;
    --bg:#f6f8ff;
    --sombra:0 10px 24px rgba(0,0,0,.06);
  }

  body{
    font-family: "Segoe UI", Arial, sans-serif;
    background: var(--bg);
    margin:0; padding:24px;
    color:var(--negro);
  }

  .wrap{
    max-width: 980px; margin:0 auto;
    background:#fff; border:1px solid var(--borde);
    border-radius:20px; padding:28px;
    box-shadow: var(--sombra);
  }

  h1{
    margin:0 0 12px; text-align:center;
    font-size:30px; font-weight:800; color:var(--negro);
  }

  .grid{
    display:grid; grid-template-columns:1fr 1fr;
    gap:18px; margin-top:18px;
  }

  .card{
    background:#fff; border:1px solid var(--borde);
    border-radius:14px; padding:16px 18px;
    box-shadow:0 4px 12px rgba(0,0,0,.04);
  }

  .label{
    font-size:12.5px; letter-spacing:.2px;
    color:#111827; font-weight:700; margin-bottom:6px;
  }

  .val{ font-weight:800; font-size:16px; color:var(--negro); }
  .val-lg{ font-size:22px; line-height:1.15; }

  .muted{ color:#4b5563; }

  pre{
    white-space:pre-wrap; background:#f9fbff;
    border:1px solid #e2e7f3; border-radius:10px;
    padding:10px; margin-top:6px; color:var(--negro);
  }

  .btns{ display:flex; justify-content:center; gap:12px; margin-top:24px; }
  .btn{
    background:var(--azul); color:#fff; text-decoration:none;
    padding:10px 18px; border-radius:10px; font-weight:700;
    box-shadow:0 6px 14px rgba(31,85,255,.25); transition:.15s;
  }
  .btn:hover{ background:var(--azul-600); }
  .btn-outline{
    background:#fff; color:var(--azul); border:2px solid var(--azul);
    text-decoration:none; padding:10px 18px; border-radius:10px; font-weight:700;
  }
  .btn-outline:hover{ background:var(--azul); color:#fff; }

  /* Pill de estado */
  .pill{
    display:inline-block; padding:6px 10px; border-radius:999px;
    font-weight:800; font-size:13px; letter-spacing:.2px; color:#fff;
  }
  .pill--pendiente{ background:#1f55ff; }
  .pill--en_atencion{ background:#05599E; }
  .pill--atendida{ background:#3BAD15; }
  .pill--ausente{ background:#84ADAC; }
  .pill--cancelada{ background:#B8303C; }

  @media (max-width: 760px){
    .grid{ grid-template-columns:1fr; }
  }
</style>


</head>
<body>
<div class="wrap">
 <h1>Detalle de cita #<?= (int)$C['id'] ?></h1>

<div class="grid">
  <div class="card">
    <div class="label">Paciente</div>
    <div class="val val-lg"><?= h($C['paciente']) ?></div>

    <div class="label">Teléfono</div>
    <div class="val val-lg"><?= h($C['telefono']) ?></div>

    <div class="label">Edad / Cédula</div>
    <div class="val"><?= h($C['edad']) ?> <span class="muted">·</span> <?= h($C['cedula']) ?></div>
  </div>

  <div class="card">
    <div class="label">Doctor</div>
    <div class="val"><?= h($C['doctor'] ?: '—') ?></div>

    <div class="label">Fecha y hora</div>
    <div class="val"><?= h($C['fecha']) ?> <span class="muted">·</span> <?= h(substr($C['hora'],0,5)) ?></div>

    <div class="label">Estado</div>
    <?php
      $estado = (string)$C['estado'];
      $cls = 'pill';
      $map = ['pendiente','en_atencion','atendida','ausente','cancelada'];
      if (in_array($estado,$map)) $cls .= ' pill--'.$estado;
    ?>
    <div><span class="<?= $cls ?>"><?= h($estado) ?></span></div>
  </div>

  <div class="card">
    <div class="label">Motivo</div>
    <div class="val"><?= h($C['motivo']) ?></div>

    <div class="label">Tipo / Origen</div>
    <div class="val"><?= h($C['tipo']) ?> <span class="muted">·</span> <?= h($C['origen']) ?></div>

    <div class="label">Check-in / Inicio / Fin</div>
    <div class="val">
      <?= h($C['checkin_en'] ?: '·') ?> <span class="muted">·</span>
      <?= h($C['inicio_atencion'] ?: '·') ?> <span class="muted">·</span>
      <?= h($C['fin_atencion'] ?: '·') ?>
    </div>
  </div>

  <div class="card">
    <div class="label">Diagnóstico</div>
    <pre><?= h($C['diagnostico']) ?></pre>

    <div class="label">Indicaciones</div>
    <pre><?= h($C['indicaciones']) ?></pre>

    <div class="label">Notas</div>
    <pre><?= h($C['notas']) ?></pre>
  </div>
</div>

<div class="btns">
  <a class="btn-outline" href="agenda_admin.php">← Volver a Agenda</a>
</div>

</div>
</body>
</html>
