<?php
// Administrador/api_citas_admin.php

session_start();
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../login.php'); exit;
}
require_once __DIR__ . '/../conexion.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  http_response_code(403); echo json_encode([]); exit;
}

$conn = conectarDB(); if (!$conn) { http_response_code(500); echo json_encode([]); exit; }
$conn->set_charset('utf8mb4');

$desde  = $_GET['desde'] ?? date('Y-m-01');
$hasta  = $_GET['hasta'] ?? date('Y-m-t');
$estado = $_GET['estado'] ?? '';
$doctor = (int)($_GET['doctor'] ?? 0);

// JOIN con usuarios (doctor) y pacientes
$sql = "SELECT 
          c.id,
          c.fecha,
          c.hora,
          c.estado,
          c.motivo,
          c.tipo,
          p.nombre AS paciente,
          p.telefono,
          u.nombre AS doctor
        FROM citas_medicas c
        JOIN pacientes p ON p.id = c.paciente_id
        LEFT JOIN usuarios u ON u.id = c.doctor_id
        WHERE c.fecha BETWEEN ? AND ?";

$params = [$desde, $hasta];
$types  = "ss";

if ($estado !== '') { $sql .= " AND c.estado = ?"; $params[] = $estado; $types .= "s"; }
if ($doctor > 0)    { $sql .= " AND c.doctor_id = ?"; $params[] = $doctor; $types .= "i"; }
$sql .= " ORDER BY c.fecha, c.hora";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$events = [];
while ($r = $res->fetch_assoc()) {
  // FullCalendar: start = fecha + hora
  $start = $r['fecha'] . 'T' . substr($r['hora'],0,8);
  $title = $r['paciente'];
  if (!empty($r['doctor'])) $title .= ' • ' . $r['doctor'];
  if (!empty($r['tipo']))   $title .= ' (' . $r['tipo'] . ')';

  $events[] = [
    'id'    => (int)$r['id'],
    'title' => $title,
    'start' => $start,
    'allDay'=> false,
    'extendedProps' => [
      'estado'   => $r['estado'],
      'paciente' => $r['paciente'],
      'telefono' => $r['telefono'],
      'doctor'   => $r['doctor'],
      'motivo'   => $r['motivo'],
      'tipo'     => $r['tipo'],
    ]
  ];
}
echo json_encode($events, JSON_UNESCAPED_UNICODE);
