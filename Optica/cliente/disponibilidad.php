<?php
require_once __DIR__ . '/../conexion.php';
header('Content-Type: application/json');

date_default_timezone_set('America/Managua');
$tz = new DateTimeZone('America/Managua');

$tipo  = $_GET['tipo']  ?? '';
$fecha = $_GET['fecha'] ?? '';
$debug = isset($_GET['debug']);

$tipos_validos = ['primera','control','entrega','otro'];
if (!in_array($tipo, $tipos_validos, true)) {
  echo json_encode(['error'=>'tipo_invalido','got_tipo'=>$tipo,'horas'=>[]]); exit;
}

$fechaObj = DateTime::createFromFormat('Y-m-d', $fecha, $tz);
$fechaValida = $fechaObj && $fechaObj->format('Y-m-d') === $fecha;
if (!$fechaValida) {
  echo json_encode(['error'=>'fecha_invalida','got_fecha'=>$fecha,'esperado'=>'YYYY-MM-DD','horas'=>[]]); exit;
}

$durMap = ['primera'=>30,'control'=>40,'entrega'=>15,'otro'=>30];
$dur = $durMap[$tipo] ?? 30;

$inicio = DateTime::createFromFormat('Y-m-d H:i:s', $fecha.' 08:00:00', $tz);
$fin    = DateTime::createFromFormat('Y-m-d H:i:s', $fecha.' 17:00:00', $tz);
if (!$inicio || !$fin) { echo json_encode(['error'=>'no_se_pudo_parsear_inicio_fin','horas'=>[]]); exit; }
if ($inicio >= $fin)   { echo json_encode(['error'=>'inicio_mayor_o_igual_que_fin','horas'=>[]]); exit; }

$slots = [];
$cursor = clone $inicio;
for ($safe=0; $cursor < $fin && $safe < 500; $cursor->modify("+{$dur} minutes"), $safe++) {
  $slots[] = $cursor->format('H:i:00');
}
if (!$slots) { echo json_encode(['error'=>'no_se_generaron_slots','horas'=>[]]); exit; }

/* >>> FIX: trabajar siempre con $slotsFiltrados <<< */
$slotsFiltrados = $slots;

/* Filtrar horas pasadas si la fecha es hoy, con margen */
$hoy   = (new DateTime('now', $tz))->format('Y-m-d');
$ahora = new DateTime('now', $tz);
$leadMin = 15; // minutos de antelación
if ($fecha === $hoy) {
  $cutoff = (clone $ahora)->modify("+{$leadMin} minutes");
  $slotsFiltrados = array_values(array_filter($slotsFiltrados, function($h) use ($cutoff, $fecha, $tz) {
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $fecha.' '.$h, $tz);
    return $dt > $cutoff;
  }));
}

$ocup = [];
try {
  $conn = conectarDB(); if(!$conn) throw new Exception('sin_conexion_bd');
  $conn->set_charset('utf8mb4');

  $sql = "SELECT hora, estado
          FROM citas_medicas
          WHERE fecha = ?
            AND estado IN ('pendiente','en_atencion','atendida','realizada')";
  $st = $conn->prepare($sql);
  if (!$st) throw new Exception('prepare_fallo: '.$conn->error);
  $st->bind_param('s', $fecha);
  $st->execute();
  $res = $st->get_result();
  while ($row = $res->fetch_assoc()) {
    $h = substr($row['hora'],0,5).':00'; // normalizar
    $ocup[$h] = $row['estado'];
  }
} catch (Throwable $e) {
  if ($debug) {
    echo json_encode([
      'warn'=>'error_sql',
      'message'=>$e->getMessage(),
      'slots_generados'=>$slots,
      'slots_hoy_filtrados'=>$slotsFiltrados,
      'horas'=>$slotsFiltrados
    ]); exit;
  }
  echo json_encode(['horas'=>$slotsFiltrados]); exit;
}

/* Aplicar ocupación sobre los filtrados */
$libres = array_values(array_filter($slotsFiltrados, fn($h)=>empty($ocup[$h])));

/* Si hoy quedó vacío por hora, devolver no-ocupados sin filtro de “pasado” */
if ($fecha === $hoy && empty($libres)) {
  $libres = array_values(array_filter($slots, fn($h)=>empty($ocup[$h])));
}

$out = ['horas'=>$libres];
if ($debug) {
  $out['debug'] = [
    'params'=>['tipo'=>$tipo,'fecha'=>$fecha],
    'dur'=>$dur,
    'hoy'=>$hoy,
    'ahora'=>$ahora->format('H:i:s'),
    'inicio'=>$inicio->format('H:i:s'),
    'fin'=>$fin->format('H:i:s'),
    'slots_count'=>count($slots),
    'slots_sample'=>array_slice($slots,0,5),
    'slots_hoy_count'=>count($slotsFiltrados),
    'ocup_count'=>count($ocup),
    'ocup_sample'=>array_slice(array_keys($ocup),0,5),
  ];
}
echo json_encode($out);
