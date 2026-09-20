<?php
// ==== Helpers básicos ====

/** Escapa HTML de forma segura */
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/** Crea/retorna token CSRF en sesión */
function make_csrf() {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
  }
  return $_SESSION['csrf'];
}

/** Verifica token CSRF */
function check_csrf($token) {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$token);
}

/** Normaliza cédula: quita guiones/espacios y mayúsculas */
function normalizeCedula(?string $v): string {
  $v = strtoupper(trim($v ?? ''));
  return preg_replace('/[\s-]+/', '', $v);
}


// ==== NUEVO: utilidades para validar edad vs cédula (DDMMYY) ==== //

/**
 * Extrae fecha de nacimiento desde cédula (busca 6 dígitos continuos DDMMYY).
 * Retorna: ['ok'=>bool, 'birth_date'=>'YYYY-MM-DD'|null, 'reason'=>?string]
 */
function birthdate_from_cedula(string $cedula): array {
  $c = normalizeCedula($cedula);

  // Nos quedamos solo con dígitos para localizar DDMMYY con precisión
  $digits = preg_replace('/\D+/', '', $c);
  $len = strlen($digits);
  if ($len < 6) {
    return ['ok'=>false, 'birth_date'=>null, 'reason'=>'No hay suficientes dígitos para una fecha'];
  }

  // 1) Intento preferente: formato NNN + DDMMYY + XXXX (cédula nica)
  //    El bloque DDMMYY empieza en índice 3 (0-based)
  $candidatos = [];
  if ($len >= 9) {
    $seg = substr($digits, 3, 6);
    $candidatos[] = $seg;
  }

  // 2) Fallback robusto: barrer todas las ventanas de 6 dígitos
  for ($i = 0; $i <= $len - 6; $i++) {
    $seg = substr($digits, $i, 6);
    // evita repetir si ya lo agregamos arriba
    if (!in_array($seg, $candidatos, true)) $candidatos[] = $seg;
  }

  $tz = new DateTimeZone('America/Managua');
  $today = new DateTimeImmutable('now', $tz);

  foreach ($candidatos as $ddmmyy) {
    $dd = (int)substr($ddmmyy, 0, 2);
    $mm = (int)substr($ddmmyy, 2, 2);
    $yy = (int)substr($ddmmyy, 4, 2);

    if ($dd < 1 || $dd > 31 || $mm < 1 || $mm > 12) {
      continue; // esta ventana no forma DD/MM válidos; prueba la siguiente
    }

    // Probar 2000+YY y 1900+YY
    foreach ([2000+$yy, 1900+$yy] as $year) {
      $dateStr = sprintf('%04d-%02d-%02d', $year, $mm, $dd);
      $d = DateTime::createFromFormat('Y-m-d', $dateStr, $tz);
      if (!($d && $d->format('Y-m-d') === $dateStr)) continue;

      $age = $today->diff($d)->y;
      if ($age >= 0 && $age <= 120) {
        return ['ok'=>true, 'birth_date'=>$dateStr, 'reason'=>null];
      }
    }
  }

  return ['ok'=>false, 'birth_date'=>null, 'reason'=>'La parte DDMMYY no se encontró en una posición válida'];
}


/** Calcula edad (años) desde 'YYYY-MM-DD' */
function age_from_date(string $birth_date): int {
  $tz = new DateTimeZone('America/Managua');
  $b = DateTime::createFromFormat('Y-m-d', $birth_date, $tz);
  if (!$b) return -1;
  $now = new DateTime('now', $tz);
  return $now->diff($b)->y;
}

/**
 * Valida edad ingresada vs cédula (estricto).
 * Retorna: ['valid'=>bool, 'computed_age'=>?int, 'birth_date'=>?string, 'message'=>string]
 */
function validate_age_vs_cedula(?string $cedula, ?int $edad): array {
  if ($edad === null) {
    return ['valid'=>true, 'computed_age'=>null, 'birth_date'=>null, 'message'=>'Sin edad ingresada'];
  }
  $cedula = (string)$cedula;
  if (trim($cedula) === '') {
    return ['valid'=>true, 'computed_age'=>null, 'birth_date'=>null, 'message'=>'Sin cédula para validar'];
  }

  $res = birthdate_from_cedula($cedula);
  if (!$res['ok']) {
    return ['valid'=>false, 'computed_age'=>null, 'birth_date'=>null, 'message'=>'Error con cédula: '.$res['reason']];
  }
  $calc = age_from_date($res['birth_date']);
  if ($calc < 0) {
    return ['valid'=>false, 'computed_age'=>null, 'birth_date'=>$res['birth_date'], 'message'=>'No se pudo calcular la edad'];
  }

  if ($calc === $edad) {
    return ['valid'=>true, 'computed_age'=>$calc, 'birth_date'=>$res['birth_date'], 'message'=>'Edad correcta'];
  }

  // Si quieres tolerancia ±1 año, reemplaza la línea anterior por:
  // if (abs($calc - $edad) <= 1) { return ['valid'=>true, ...]; }

  return [
    'valid'=>false,
    'computed_age'=>$calc,
    'birth_date'=>$res['birth_date'],
    'message'=>sprintf('La edad ingresada (%d) no coincide con la calculada desde la cédula (%d). Fecha: %s',
                       $edad, $calc, $res['birth_date'])
  ];
}

// ==== Validaciones de formulario de cita ==== //

function validar_nombre_cliente(?string $nombre): array {
  $nombre = trim($nombre ?? '');

  if ($nombre === '') {
    return ['ok' => false, 'message' => 'El nombre es obligatorio.'];
  }

  if (strlen($nombre) < 3 || strlen($nombre) > 80) {
    return ['ok' => false, 'message' => 'El nombre debe tener entre 3 y 80 caracteres.'];
  }

  if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/u', $nombre)) {
    return ['ok' => false, 'message' => 'El nombre solo debe contener letras y espacios.'];
  }

  return ['ok' => true, 'message' => 'Nombre válido.'];
}

function validar_telefono_cliente(?string $telefono): array {
  $telefono = trim($telefono ?? '');

  if ($telefono === '') {
    return ['ok' => false, 'message' => 'El teléfono es obligatorio.'];
  }

  if (!preg_match('/^[0-9]{8}$/', $telefono)) {
    return ['ok' => false, 'message' => 'El teléfono debe tener exactamente 8 dígitos.'];
  }

  if (preg_match('/^0+$/', $telefono)) {
    return ['ok' => false, 'message' => 'El teléfono no puede ser solo ceros.'];
  }

  return ['ok' => true, 'message' => 'Teléfono válido.'];
}

function validar_cedula_nica(?string $cedula): array {
  $cedulaOriginal = trim($cedula ?? '');

  if ($cedulaOriginal === '') {
    return ['ok' => false, 'message' => 'La cédula es obligatoria.'];
  }

  $cedulaNormalizada = normalizeCedula($cedulaOriginal);

  if (!preg_match('/^[0-9]{13}[A-Z]$/', $cedulaNormalizada)) {
    return [
      'ok' => false,
      'message' => 'La cédula no tiene un formato válido. Ejemplo: 001-010105-0001A.'
    ];
  }

  return ['ok' => true, 'message' => 'Cédula válida.'];
}

function validar_edad_cliente($edad): array {
  if ($edad === null || $edad === '') {
    return ['ok' => false, 'message' => 'La edad es obligatoria.'];
  }

  if (!filter_var($edad, FILTER_VALIDATE_INT)) {
    return ['ok' => false, 'message' => 'La edad debe ser un número entero.'];
  }

  $edad = (int)$edad;

  if ($edad < 1 || $edad > 120) {
    return ['ok' => false, 'message' => 'La edad ingresada no es válida.'];
  }

  return ['ok' => true, 'message' => 'Edad válida.'];
}
// ==== (Opcional) ICS si lo usas desde aquí ==== //

function cita_to_ics(array $e): string {
  $lines = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//Optica//Portal Citas//ES',
    'CALSCALE:GREGORIAN',
    'METHOD:PUBLISH',
    'BEGIN:VEVENT',
    'UID:'.$e['uid'],
    'SUMMARY:'.$e['summary'],
    'DESCRIPTION:'.$e['description'],
    'DTSTART:'.gmdate('Ymd\THis\Z', strtotime($e['dtstart'])),
    'DTEND:'.gmdate('Ymd\THis\Z', strtotime($e['dtend'])),
    'LOCATION:'.$e['location'],
    'END:VEVENT',
    'END:VCALENDAR',
  ];
  return implode("\r\n", $lines);
}
