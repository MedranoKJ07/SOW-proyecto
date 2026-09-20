<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/helpers.php';

date_default_timezone_set('America/Managua');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* =========================
   FUNCIONES BÁSICAS
========================= */

if (!function_exists('h')) {
  function h($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
}

if (!function_exists('normalizeCedula')) {
  function normalizeCedula(?string $v): string {
    $v = strtoupper(trim($v ?? ''));
    return preg_replace('/[\s-]+/', '', $v);
  }
}

function postFirst(array $keys): string {
  foreach ($keys as $k) {
    if (!array_key_exists($k, $_POST)) {
      continue;
    }

    $v = $_POST[$k];

    if (is_array($v)) {
      $v = reset($v);
    }

    $v = trim((string)$v);

    if ($v !== '') {
      return $v;
    }
  }

  return '';
}

function prepareOrFail(mysqli $db, string $sql): mysqli_stmt {
  $stmt = $db->prepare($sql);

  if (!$stmt) {
    throw new RuntimeException('Error en prepare: ' . $db->error);
  }

  return $stmt;
}

function colExists(mysqli $db, string $table, string $col): bool {
  $sql = "SELECT 1
          FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
          LIMIT 1";

  $st = $db->prepare($sql);
  $st->bind_param('ss', $table, $col);
  $st->execute();
  $st->store_result();

  $exists = $st->num_rows > 0;

  $st->close();

  return $exists;
}

/* =========================
   NORMALIZADORES
========================= */

function formatearCedulaNica(string $cedula_norm): string {
  $cedula_norm = normalizeCedula($cedula_norm);

  if (!preg_match('/^[0-9]{13}[A-Z]$/', $cedula_norm)) {
    return $cedula_norm;
  }

  return substr($cedula_norm, 0, 3) . '-' .
         substr($cedula_norm, 3, 6) . '-' .
         substr($cedula_norm, 9, 4) .
         substr($cedula_norm, 13, 1);
}

function normalizarHora(?string $s): ?string {
  $s = trim((string)$s);

  if ($s === '') {
    return null;
  }

  $s = str_ireplace(
    [' a. m.', ' p. m.', 'a. m.', 'p. m.', 'am', 'pm'],
    [' AM', ' PM', 'AM', 'PM', 'AM', 'PM'],
    $s
  );

  $s = preg_replace('/\s+/', ' ', $s);
  $s = str_replace('.', ':', $s);
  $s = strtoupper($s);

  $formatos = ['H:i:s', 'H:i', 'G:i', 'g:i A', 'h:i A', 'g A', 'h A', 'H', 'G'];

  foreach ($formatos as $f) {
    $dt = DateTime::createFromFormat('!' . $f, $s);

    if ($dt instanceof DateTime) {
      $errors = DateTime::getLastErrors();

      if ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)) {
        return $dt->format('H:i:00');
      }
    }
  }

  if (preg_match('/^\d{3,4}$/', $s)) {
    $h = (int)substr($s, 0, -2);
    $m = (int)substr($s, -2);

    if ($h >= 0 && $h <= 23 && $m >= 0 && $m < 60) {
      return sprintf('%02d:%02d:00', $h, $m);
    }
  }

  return null;
}

function dtValid(string $fmt, string $val): bool {
  $d = DateTime::createFromFormat($fmt, $val);
  return $d && $d->format($fmt) === $val;
}

function normalizarFecha(string $fecha_in): array {
  $fecha_in = trim($fecha_in);
  $dt = null;

  if (dtValid('d-m-Y', $fecha_in)) {
    $dt = DateTime::createFromFormat('d-m-Y', $fecha_in);
  } elseif (dtValid('Y-m-d', $fecha_in)) {
    $dt = DateTime::createFromFormat('Y-m-d', $fecha_in);
  }

  if (!$dt) {
    throw new InvalidArgumentException('Fecha inválida. Use DD-MM-YYYY o YYYY-MM-DD.');
  }

  return [
    'ui' => $dt->format('d-m-Y'),
    'db' => $dt->format('Y-m-d'),
  ];
}

function normalizarTipoCita(string $tipo): string {
  $t = mb_strtolower(trim($tipo), 'UTF-8');

  $map = [
    'primera'        => 'primera',
    'primera vez'    => 'primera',
    'consulta'       => 'primera',
    'nuevo'          => 'primera',
    'nueva'          => 'primera',

    'control'        => 'control',
    'revision'       => 'control',
    'revisión'       => 'control',
    'seguimiento'    => 'control',

    'entrega'        => 'entrega',
    'retiro'         => 'entrega',
    'recoger lentes' => 'entrega',

    'otro'           => 'otro',
    'otros'          => 'otro',
  ];

  return $map[$t] ?? 'otro';
}

/* =========================
   VALIDACIONES
========================= */

function validarNombreCliente(string $nombre): string {
  $nombre = preg_replace('/\s+/', ' ', trim($nombre));

  if ($nombre === '') {
    throw new InvalidArgumentException('El nombre es obligatorio.');
  }

  if (mb_strlen($nombre, 'UTF-8') < 3 || mb_strlen($nombre, 'UTF-8') > 80) {
    throw new InvalidArgumentException('El nombre debe tener entre 3 y 80 caracteres.');
  }

  if (preg_match('/\d/', $nombre)) {
    throw new InvalidArgumentException('El nombre no debe contener números.');
  }

  if (!preg_match('/^[\p{L}\s]+$/u', $nombre)) {
    throw new InvalidArgumentException('El nombre solo debe contener letras y espacios.');
  }

  return $nombre;
}

function validarTelefonoCliente(string $telefono): string {
  $telefono = preg_replace('/\D+/', '', trim($telefono));

  if ($telefono === '') {
    throw new InvalidArgumentException('El teléfono es obligatorio.');
  }

  if (!preg_match('/^[0-9]{8}$/', $telefono)) {
    throw new InvalidArgumentException('El teléfono debe tener exactamente 8 dígitos.');
  }

  if (preg_match('/^(\d)\1{7}$/', $telefono)) {
    throw new InvalidArgumentException('El teléfono no puede ser un número repetido como 00000000.');
  }

  return $telefono;
}

function validarCorreoCliente(string $correo): string {
  $correo = trim($correo);

  if ($correo === '') {
    return '';
  }

  if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    throw new InvalidArgumentException('El correo electrónico no es válido.');
  }

  return $correo;
}

function validarCedulaNicaCliente(string $cedula): string {
  $cedula_norm = normalizeCedula($cedula);

  if ($cedula_norm === '') {
    throw new InvalidArgumentException('La cédula es obligatoria.');
  }

  if (!preg_match('/^[0-9]{13}[A-Z]$/', $cedula_norm)) {
    throw new InvalidArgumentException('La cédula no tiene un formato válido. Ejemplo: 001-050505-1016N.');
  }

  if (preg_match('/^0{13}[A-Z]$/', $cedula_norm)) {
    throw new InvalidArgumentException('La cédula no puede estar compuesta solo por ceros.');
  }

  return $cedula_norm;
}

function validarEdadPorAnioCedula(string $cedula_norm, int $edad): array {
  $cedula_norm = normalizeCedula($cedula_norm);

  if (!preg_match('/^(\d{3})(\d{2})(\d{2})(\d{2})(\d{4})([A-Z])$/', $cedula_norm, $m)) {
    throw new InvalidArgumentException('La cédula no tiene un formato válido para calcular la edad.');
  }

  $dia = (int)$m[2];
  $mes = (int)$m[3];
  $yy  = (int)$m[4];

  $tz = new DateTimeZone('America/Managua');
  $anioActual = (int)(new DateTime('now', $tz))->format('Y');

  $anioNacimiento = 2000 + $yy;

  if ($anioNacimiento > $anioActual) {
    $anioNacimiento -= 100;
  }

  if (!checkdate($mes, $dia, $anioNacimiento)) {
    throw new InvalidArgumentException('La fecha de nacimiento dentro de la cédula no es válida.');
  }

  $edadCalculada = $anioActual - $anioNacimiento;

  if ($edadCalculada < 1 || $edadCalculada > 120) {
    throw new InvalidArgumentException('La edad calculada desde la cédula está fuera de rango.');
  }

  if ($edadCalculada !== $edad) {
    throw new InvalidArgumentException(
      'La edad ingresada (' . $edad . ') no coincide con el año de nacimiento de la cédula. Edad esperada: ' . $edadCalculada . '.'
    );
  }

  return [
    'edad_calculada' => $edadCalculada,
    'fecha_nacimiento' => sprintf('%04d-%02d-%02d', $anioNacimiento, $mes, $dia)
  ];
}

/* =========================
   PROCESO PRINCIPAL
========================= */

try {
  $csrf = $_POST['csrf'] ?? '';

  if (function_exists('check_csrf') && !check_csrf($csrf)) {
    throw new RuntimeException('CSRF inválido.');
  }

  $tipo_in     = postFirst(['tipo', 'tipo_cita']);
  $fecha_in    = postFirst(['fecha', 'fecha_cita']);
  $hora_in     = postFirst(['hora', 'hora_cita']);
  $nombre      = postFirst(['nombre', 'nombre_cliente', 'paciente_nombre', 'cliente', 'paciente']);
  $tel         = postFirst(['telefono', 'tel', 'celular', 'whatsapp', 'phone']);
  $correo      = postFirst(['correo', 'email', 'mail']);
  $motivo      = postFirst(['motivo', 'descripcion', 'observacion']);
  $cedula_form = postFirst(['cedula', 'cedula_visible', 'dni', 'documento', 'idcard', 'ced', 'cedula_norm']);
  $edad_in     = postFirst(['edad', 'age']);

  if (
    $tipo_in === '' ||
    $fecha_in === '' ||
    $hora_in === '' ||
    $nombre === '' ||
    $tel === '' ||
    $cedula_form === '' ||
    $edad_in === ''
  ) {
    throw new InvalidArgumentException('Datos incompletos. Revise nombre, teléfono, cédula, edad, fecha y hora.');
  }

  $nombre = validarNombreCliente($nombre);
  $tel = validarTelefonoCliente($tel);
  $correo = validarCorreoCliente($correo);
  $cedula_norm = validarCedulaNicaCliente($cedula_form);
  $cedula_formateada = formatearCedulaNica($cedula_norm);

  if (!preg_match('/^[0-9]{1,3}$/', (string)$edad_in)) {
    throw new InvalidArgumentException('La edad debe ser un número entero válido.');
  }

  $edad = (int)$edad_in;

  if ($edad < 1 || $edad > 120) {
    throw new InvalidArgumentException('Edad fuera de rango.');
  }

  validarEdadPorAnioCedula($cedula_norm, $edad);

  if (mb_strlen($motivo, 'UTF-8') > 500) {
    throw new InvalidArgumentException('El motivo no debe superar los 500 caracteres.');
  }

  $tipo = normalizarTipoCita($tipo_in);

  $fecha = normalizarFecha($fecha_in);
  $fecha_ui = $fecha['ui'];
  $fecha_db = $fecha['db'];

  $hora_norm = normalizarHora($hora_in);

  if ($hora_norm === null) {
    throw new InvalidArgumentException('Hora inválida.');
  }

  $db = conectarDB();

  if (!$db) {
    throw new RuntimeException('Error de conexión DB.');
  }

  $db->set_charset('utf8mb4');

  $db->begin_transaction();

  $paciente_id = null;

  $hasCedulaPacientes     = colExists($db, 'pacientes', 'cedula');
  $hasCedulaNormPacientes = colExists($db, 'pacientes', 'cedula_norm');
  $hasEdadPacientes       = colExists($db, 'pacientes', 'edad');
  $hasCorreoPacientes     = colExists($db, 'pacientes', 'correo');
  $hasActivoPacientes     = colExists($db, 'pacientes', 'activo');

  /* ===== BUSCAR PACIENTE POR CÉDULA ===== */

  if ($hasCedulaNormPacientes) {
    $sql = "SELECT id FROM pacientes WHERE cedula_norm = ? LIMIT 1";
    $q = prepareOrFail($db, $sql);
    $q->bind_param('s', $cedula_norm);
    $q->execute();
    $q->bind_result($paciente_id);

    if (!$q->fetch()) {
      $paciente_id = null;
    }

    $q->close();
  }

  if ($paciente_id === null && $hasCedulaPacientes) {
    $sql = "SELECT id FROM pacientes WHERE cedula = ? OR cedula = ? LIMIT 1";
    $q = prepareOrFail($db, $sql);
    $q->bind_param('ss', $cedula_formateada, $cedula_norm);
    $q->execute();
    $q->bind_result($paciente_id);

    if (!$q->fetch()) {
      $paciente_id = null;
    }

    $q->close();
  }

  /* ===== BUSCAR PACIENTE POR TELÉFONO Y NOMBRE ===== */

  if ($paciente_id === null) {
    $sql = "SELECT id FROM pacientes WHERE telefono = ? AND nombre = ? LIMIT 1";
    $q = prepareOrFail($db, $sql);
    $q->bind_param('ss', $tel, $nombre);
    $q->execute();
    $q->bind_result($paciente_id);

    if (!$q->fetch()) {
      $paciente_id = null;
    }

    $q->close();
  }

  /* ===== CREAR O ACTUALIZAR PACIENTE ===== */

  if ($paciente_id === null) {
    $cols  = ['nombre', 'telefono'];
    $types = 'ss';
    $bind  = [$nombre, $tel];

    if ($hasEdadPacientes) {
      $cols[] = 'edad';
      $types .= 'i';
      $bind[] = $edad;
    }

    if ($hasCorreoPacientes) {
      $cols[] = 'correo';
      $types .= 's';
      $bind[] = $correo !== '' ? $correo : null;
    }

    if ($hasCedulaPacientes) {
      $cols[] = 'cedula';
      $types .= 's';
      $bind[] = $cedula_formateada;
    }

    if ($hasCedulaNormPacientes) {
      $cols[] = 'cedula_norm';
      $types .= 's';
      $bind[] = $cedula_norm;
    }

    if ($hasActivoPacientes) {
      $cols[] = 'activo';
      $types .= 'i';
      $bind[] = 1;
    }

    $sql = "INSERT INTO pacientes (" . implode(',', $cols) . ")
            VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";

    $q = prepareOrFail($db, $sql);
    $q->bind_param($types, ...$bind);
    $q->execute();

    if ($q->affected_rows <= 0) {
      throw new RuntimeException('No se pudo crear el paciente.');
    }

    $paciente_id = $q->insert_id;
    $q->close();

  } else {
    $set   = ['nombre = ?', 'telefono = ?'];
    $types = 'ss';
    $bind  = [$nombre, $tel];

    if ($hasEdadPacientes) {
      $set[] = 'edad = ?';
      $types .= 'i';
      $bind[] = $edad;
    }

    if ($hasCorreoPacientes) {
      $set[] = 'correo = ?';
      $types .= 's';
      $bind[] = $correo !== '' ? $correo : null;
    }

    if ($hasCedulaPacientes) {
      $set[] = 'cedula = ?';
      $types .= 's';
      $bind[] = $cedula_formateada;
    }

    if ($hasCedulaNormPacientes) {
      $set[] = 'cedula_norm = ?';
      $types .= 's';
      $bind[] = $cedula_norm;
    }

    if ($hasActivoPacientes) {
      $set[] = 'activo = ?';
      $types .= 'i';
      $bind[] = 1;
    }

    $types .= 'i';
    $bind[] = $paciente_id;

    $sql = "UPDATE pacientes SET " . implode(', ', $set) . " WHERE id = ?";

    $q = prepareOrFail($db, $sql);
    $q->bind_param($types, ...$bind);
    $q->execute();
    $q->close();
  }

  if (!$paciente_id || (int)$paciente_id <= 0) {
    throw new RuntimeException('No se obtuvo el ID del paciente.');
  }

  /* ===== VALIDAR DUPLICADO DE CITA ===== */

  $sql = "SELECT id
          FROM citas_medicas
          WHERE fecha = ?
            AND hora = ?
            AND estado <> 'cancelada'
          LIMIT 1";

  $q = prepareOrFail($db, $sql);
  $q->bind_param('ss', $fecha_db, $hora_norm);
  $q->execute();
  $q->bind_result($cita_existente);

  if ($q->fetch()) {
    $q->close();
    throw new RuntimeException('Ya existe una cita agendada para esa fecha y hora.');
  }

  $q->close();

  /* ===== CREAR CITA ===== */

  $has_creado_en_citas = colExists($db, 'citas_medicas', 'creado_en');
  $has_token_citas     = colExists($db, 'citas_medicas', 'token');
  $has_origen_citas    = colExists($db, 'citas_medicas', 'origen');

  $estado = 'pendiente';
  $token  = $has_token_citas ? bin2hex(random_bytes(32)) : null;

  $cols  = ['paciente_id', 'fecha', 'hora', 'motivo', 'tipo', 'estado'];
  $types = 'isssss';
  $bind  = [$paciente_id, $fecha_db, $hora_norm, $motivo, $tipo, $estado];

  if ($has_origen_citas) {
    $cols[] = 'origen';
    $types .= 's';
    $bind[] = 'paciente';
  }

  if ($has_token_citas) {
    $cols[] = 'token';
    $types .= 's';
    $bind[] = $token;
  }

  $placeholders = implode(',', array_fill(0, count($cols), '?'));

  if ($has_creado_en_citas) {
    $sql = "INSERT INTO citas_medicas (" . implode(',', $cols) . ", creado_en)
            VALUES (" . $placeholders . ", NOW())";
  } else {
    $sql = "INSERT INTO citas_medicas (" . implode(',', $cols) . ")
            VALUES (" . $placeholders . ")";
  }

  $q = prepareOrFail($db, $sql);
  $q->bind_param($types, ...$bind);

  try {
    $q->execute();
  } catch (mysqli_sql_exception $ex) {
    throw new RuntimeException(
      'Error MySQL al guardar la cita: ' . $ex->getMessage() .
      ' | tipo original=' . $tipo_in .
      ' | tipo normalizado=' . $tipo .
      ' | fecha=' . $fecha_db .
      ' | hora=' . $hora_norm .
      ' | paciente_id=' . $paciente_id
    );
  }

  if ($q->affected_rows <= 0) {
    throw new RuntimeException('No se pudo crear la cita.');
  }

  $cita_id = $q->insert_id;
  $q->close();

  $db->commit();

  $_SESSION['ultima_cita'] = (int)$cita_id;
  $_SESSION['ultima_cita_nombre'] = $nombre;
  $_SESSION['form_success'] = 'Cita registrada correctamente para ' . $fecha_ui . ' a las ' . substr($hora_norm, 0, 5);

  if ($has_token_citas && $token) {
    header('Location: ./gracias.php?id=' . $cita_id . '&t=' . urlencode($token));
  } else {
    header('Location: ./gracias.php?id=' . $cita_id);
  }

  exit;

} catch (Throwable $e) {
  if (isset($db) && $db instanceof mysqli) {
    try {
      $db->rollback();
    } catch (Throwable $x) {
      // Ignorar error de rollback
    }
  }

  $_SESSION['form_error'] = '⚠️ ' . h($e->getMessage());
  $_SESSION['form_old']   = $_POST;

  header('Location: ./index.php');
  exit;
}