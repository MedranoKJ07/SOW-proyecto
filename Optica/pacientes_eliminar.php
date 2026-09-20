<?php
session_start();
require_once __DIR__ . '/conexion.php'; // ajusta si tu archivo está en otra carpeta

// Autorización (ajusta a tus roles)
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'] ?? '', ['admin','secretaria'])) {
  header('Location: login.php'); exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: pacientes_listar.php?err=ID+inválido'); exit; }

$db = conectarDB(); if(!$db){ die('Error de conexión'); }
$db->set_charset('utf8mb4');

try {
  $db->begin_transaction();

  // 1) borrar citas del paciente (hijos)
  $q1 = $db->prepare("DELETE FROM citas_medicas WHERE paciente_id = ?");
  $q1->bind_param('i', $id);
  $q1->execute();
  $q1->close();

  // 2) borrar paciente (padre)
  $q2 = $db->prepare("DELETE FROM pacientes WHERE id = ?");
  $q2->bind_param('i', $id);
  $q2->execute();
  $rows = $q2->affected_rows;
  $q2->close();

  if ($rows <= 0) { throw new Exception('No se encontró el paciente.'); }

  $db->commit();
  header('Location: pacientes_listar.php?ok=Paciente+eliminado+correctamente');
  exit;

} catch (Throwable $e) {
  $db->rollback();
  header('Location: pacientes_listar.php?err=' . urlencode('No se pudo eliminar: '.$e->getMessage()));
  exit;
}
