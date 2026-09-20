<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$conn = conectarDB();
$conn->set_charset('utf8mb4');

$csrf = make_csrf();
$old = $_SESSION['form_old'] ?? [];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Agendar cita | Portal de clientes</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./styless.css?v=3">
</head>
<body>
<div class="container">
  <div class="header">
    <h1>Agenda tu cita</h1>
    <button type="button" id="themeToggle" class="btn secondary">Cambiar tema</button>
  </div>

  <div class="card">
    <h3>1) Elige el servicio</h3>
    <div class="row">
      <div>
        <label for="servicio_id">Servicio</label>
        <select id="servicio_id" aria-label="Servicio">
          <option value="">— Selecciona —</option>
          <option value="primera" data-duracion="30">Consulta general (30 min)</option>
          <option value="control" data-duracion="40">Examen de la vista (40 min)</option>
          <option value="entrega" data-duracion="15">Retiro/entrega de lentes (15 min)</option>
          <option value="otro" data-duracion="30">Otro (30 min)</option>
        </select>
        <p class="help">Selecciona el tipo de cita que necesitas.</p>
      </div>
      <div>
        <span class="badge">Duraciones estimadas incluidas</span>
      </div>
    </div>
  </div>

  <div class="card">
    <h3>2) Fecha y hora</h3>
    <div class="row">
      <div>
        <label for="fecha">Fecha</label>
        <input type="date" id="fecha" min="<?= date('Y-m-d') ?>">
      </div>
      <div>
        <label for="hora">Horas disponibles</label>
        <select id="hora" disabled>
          <option value="">Selecciona servicio y fecha</option>
        </select>
      </div>
    </div>
    <p class="small">Las horas se cargan al elegir servicio y fecha.</p>
  </div>

  <?php if (!empty($_SESSION['form_error'])): ?>
    <div class="alert alert-danger" role="alert" style="margin-bottom:15px;">
      <?= h($_SESSION['form_error']); ?>
    </div>
  <?php endif; ?>

  <form class="card" method="post" action="./crear_cita.php" autocomplete="on" id="form-cita" novalidate>
    <h3>3) Tus datos</h3>

    <div class="row three">
      <div>
        <label for="nombre">Nombre completo</label>
        <input type="text" name="nombre" id="nombre" placeholder="Ej: Juan Pérez"
               value="<?= h($old['nombre'] ?? '') ?>">
      </div>
      <div>
        <label for="telefono">Teléfono</label>
        <input type="text" name="telefono" id="telefono" placeholder="Ej: 8888-8888"
               value="<?= h($old['telefono'] ?? '') ?>">
      </div>
      <div>
        <label for="correo">Correo (opcional)</label>
        <input type="email" name="correo" id="correo" placeholder="Ej: juan@mail.com"
               value="<?= h($old['correo'] ?? '') ?>">
      </div>
    </div>

    <div class="row three">
      <div>
        <label for="edad">Edad</label>
        <input type="number" name="edad" id="edad" min="0" max="120" step="1" inputmode="numeric"
               placeholder="Ej: 25" value="<?= h($old['edad'] ?? '') ?>">
        <p class="help">Si tienes 16 años o más, la cédula será obligatoria.</p>
      </div>
      <div>
        <label for="cedula">Cédula</label>
        <input name="cedula" id="cedula" placeholder="Ej: 001-050505-1016N"
               value="<?= h($old['cedula'] ?? '') ?>">
        <p class="help">Puedes escribirla con o sin guiones; la normalizamos al enviar.</p>
      </div>
      <div>
        <label for="motivo">Motivo de la cita</label>
        <input name="motivo" id="motivo" placeholder="Ej: examen de la vista, control..."
               value="<?= h($old['motivo'] ?? '') ?>">
      </div>
    </div>

    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <input type="hidden" name="tipo" id="f_tipo" value="<?= h($old['tipo'] ?? '') ?>">
    <input type="hidden" name="duracion_min" id="f_duracion" value="<?= h($old['duracion_min'] ?? '') ?>">
    <input type="hidden" name="fecha" id="f_fecha" value="<?= h($old['fecha'] ?? '') ?>">
    <input type="hidden" name="hora" id="f_hora" value="<?= h($old['hora'] ?? '') ?>">

    <hr class="sep">

    <div class="footer-actions">
      <button id="btn-agendar" disabled>Agendar</button>
      <a class="btn secondary" href="quienes_somos.php">Cancelar</a>
    </div>
  </form>
</div>

<script>
const OLD = <?= json_encode([
  'tipo'        => $old['tipo'] ?? '',
  'duracion'    => $old['duracion_min'] ?? '',
  'fecha'       => $old['fecha'] ?? '',
  'hora'        => $old['hora'] ?? '',
  'edad'        => $old['edad'] ?? '',
  'cedula'      => $old['cedula'] ?? '',
  'motivo'      => $old['motivo'] ?? '',
  'nombre'      => $old['nombre'] ?? '',
  'telefono'    => $old['telefono'] ?? '',
  'correo'      => $old['correo'] ?? ''
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const servicioSel = document.getElementById('servicio_id');
  const fechaInp    = document.getElementById('fecha');
  const horaSel     = document.getElementById('hora');
  const btnAgendar  = document.getElementById('btn-agendar');

  const hServicio   = document.getElementById('f_tipo');
  const hDur        = document.getElementById('f_duracion');
  const hFecha      = document.getElementById('f_fecha');
  const hHora       = document.getElementById('f_hora');

  const edadInp     = document.getElementById('edad');
  const cedulaInp   = document.getElementById('cedula');
  const form        = document.getElementById('form-cita');

  function updateCedulaRequirement() {
    const edad = parseInt(edadInp?.value || '0', 10);
    const must = !Number.isNaN(edad) && edad >= 16;

    if (cedulaInp) {
      if (must) cedulaInp.setAttribute('required', 'required');
      else cedulaInp.removeAttribute('required');
    }
  }

  function updateHidden() {
    const opt = servicioSel.options[servicioSel.selectedIndex];
    const tipo = servicioSel.value || '';
    const dur = opt ? (opt.dataset.duracion || '') : '';

    if (hServicio) hServicio.value = tipo;
    if (hDur) hDur.value = dur;
    if (hFecha) hFecha.value = fechaInp.value || '';
    if (hHora) hHora.value = horaSel.value || '';

    const readyCore = (tipo && fechaInp.value && horaSel.value);
    btnAgendar.disabled = !readyCore;
  }

  function setHoras(list) {
    horaSel.innerHTML = '';

    if (!Array.isArray(list) || list.length === 0) {
      const o = document.createElement('option');
      o.value = '';
      o.textContent = 'No hay horarios';
      horaSel.appendChild(o);
      horaSel.disabled = true;
      updateHidden();
      return;
    }

    list.forEach(h => {
      const o = document.createElement('option');
      o.value = h;
      o.textContent = h.slice(0, 5);
      horaSel.appendChild(o);
    });

    horaSel.disabled = false;

    if (OLD.hora) {
      const idx = Array.from(horaSel.options).findIndex(o => o.value === OLD.hora);
      horaSel.selectedIndex = idx >= 0 ? idx : 0;
    } else {
      horaSel.selectedIndex = 0;
    }

    updateHidden();
  }

  async function cargarHoras() {
    updateHidden();

    if (!servicioSel.value || !fechaInp.value) {
      setHoras([]);
      return;
    }

    try {
      const url = './disponibilidad.php?tipo=' + encodeURIComponent(servicioSel.value) +
                  '&fecha=' + encodeURIComponent(fechaInp.value);

      const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!r.ok) throw new Error('HTTP ' + r.status);

      const data = await r.json();
      setHoras(data.horas || []);
    } catch (err) {
      console.error('Error cargando horas:', err);
      setHoras([]);
    }
  }

  function normalizeCedula(v) {
    return (v || '').toUpperCase().replace(/[\s-]+/g, '');
  }

  servicioSel.addEventListener('change', cargarHoras);
  fechaInp.addEventListener('change', cargarHoras);
  horaSel.addEventListener('change', updateHidden);

  if (edadInp) {
    edadInp.addEventListener('input', updateCedulaRequirement);
  }

  form.addEventListener('submit', function (e) {
    updateCedulaRequirement();

    if (cedulaInp && cedulaInp.required && !cedulaInp.value.trim()) {
      e.preventDefault();
      cedulaInp.focus();
      alert('Para 16 años o más, la cédula es obligatoria.');
      return;
    }

    if (cedulaInp) {
      cedulaInp.value = normalizeCedula(cedulaInp.value);
    }
  });

  if (OLD.tipo) {
    const idx = Array.from(servicioSel.options).findIndex(o => o.value === OLD.tipo);
    if (idx >= 0) servicioSel.selectedIndex = idx;
  }

  if (OLD.fecha) fechaInp.value = OLD.fecha;

  if (OLD.tipo && OLD.fecha) {
    cargarHoras();
  } else {
    updateHidden();
  }

  updateCedulaRequirement();
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const body = document.body;
  const btnTheme = document.getElementById('themeToggle');

  const saved = localStorage.getItem('theme');

  if (saved === 'dark') {
    body.classList.add('dark');
    body.classList.remove('light');
  } else if (saved === 'light') {
    body.classList.add('light');
    body.classList.remove('dark');
  }

  function updateThemeLabel() {
    if (!btnTheme) return;
    btnTheme.textContent = body.classList.contains('dark') ? '☀️ Claro' : '🌙 Oscuro';
  }

  function toggleTheme() {
    if (body.classList.contains('dark')) {
      body.classList.remove('dark');
      body.classList.add('light');
      localStorage.setItem('theme', 'light');
    } else {
      body.classList.remove('light');
      body.classList.add('dark');
      localStorage.setItem('theme', 'dark');
    }
    updateThemeLabel();
  }

  updateThemeLabel();

  if (btnTheme) {
    btnTheme.addEventListener('click', toggleTheme);
  }
});
</script>

<?php
unset($_SESSION['form_error'], $_SESSION['form_old']);
?>
</body>
</html>