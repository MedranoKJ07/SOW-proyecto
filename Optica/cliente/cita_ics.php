<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/helpers.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('ID inválido');
}

$conn = conectarDB();
if (!$conn) {
    die('Error de conexión a la base de datos');
}
$conn->set_charset('utf8mb4');

// ⚠️ Asegúrate de usar tu tabla correcta: citas_medicas
$sql = "SELECT id, fecha, hora, tipo
        FROM citas_medicas
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$e = $result->fetch_assoc();
if (!$e) {
    die('Cita no encontrada');
}

// Traducir tipo → nombre y duración
$nombreServicio = [
    'primera' => 'Consulta general',
    'control' => 'Examen de la vista',
    'entrega' => 'Retiro/entrega de lentes',
    'otro'    => 'Otro servicio'
][$e['tipo']] ?? 'Servicio';

$durMap = [
    'primera' => 30,
    'control' => 40,
    'entrega' => 15,
    'otro'    => 30
];
$duracion = $durMap[$e['tipo']] ?? 30;

// Generar tiempos
$dtstart = "{$e['fecha']} {$e['hora']}";
$dtend   = date('Y-m-d H:i:s', strtotime($dtstart . " + {$duracion} minutes"));

// Crear archivo .ics
$ics = cita_to_ics([
    'uid'         => "cita-{$e['id']}@optica",
    'summary'     => "Cita: {$nombreServicio}",
    'description' => "Recuerde llegar 10 minutos antes.",
    'location'    => "Óptica West, Ciudad Sandino",
    'dtstart'     => $dtstart,
    'dtend'       => $dtend,
]);

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="cita-'.$e['id'].'.ics"');
echo $ics;
