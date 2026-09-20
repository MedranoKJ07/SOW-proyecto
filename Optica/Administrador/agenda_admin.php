<?php
// Administrador/agenda_admin.php
session_start();
require_once __DIR__ . '/../conexion.php';
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../login.php'); exit;
}
$conn = conectarDB();
if (!$conn) { die('Error de conexión'); }
$conn->set_charset('utf8mb4');

// Cargar doctores desde 'usuarios' (rol='doctor')
$doctores = [];
$q = "SELECT id, nombre FROM usuarios WHERE rol='doctor' ORDER BY nombre";
if ($r = $conn->query($q)) { while($x=$r->fetch_assoc()) $doctores[] = $x; }
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Agenda | Supervisión</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="dashboard_Admin.css?v=7">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- FullCalendar CDN -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<style>
  :root{
    --primary:#1f55ff;        /* azul principal */
    --primary-600:#2a5fff;
    --ink:#0b1020;            /* negro azulado */
    --ink-2:#111827;          /* negro/gris muy oscuro */
    --line:#e9edf3;           /* bordes suaves */
    --bg:#f5f8ff;             /* fondo claro azulado */
    --chip:#eef2ff;           /* chips */
  }

  body{font-family:Inter,system-ui,Segoe UI,Arial;background:linear-gradient(135deg,var(--bg),#ffffff);margin:0}
  .topbar{
    display:flex;align-items:center;justify-content:space-between;
    padding:16px 22px;background:#fff;border-bottom:1px solid var(--line);
    position:sticky;top:0;z-index:10;
  }
  .kpis{display:flex;gap:14px;flex-wrap:wrap}
  .kpi{
    background:#fff;border:1px solid var(--line);border-radius:14px;
    padding:12px 14px;box-shadow:0 5px 14px rgba(0,0,0,.04);min-width:180px
  }
  .kpi .n{font-size:22px;font-weight:800;color:var(--ink)}
  .filters{display:flex;gap:10px;flex-wrap:wrap;align-items:center;padding:12px 22px}
  .filters select,.filters input{
    padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--ink)
  }
  .btn{
    background:var(--primary);color:#fff;border:none;border-radius:10px;
    padding:10px 14px;cursor:pointer;text-decoration:none;display:inline-flex;
    align-items:center;gap:8px;box-shadow:0 6px 14px rgba(31,85,255,.25)
  }
  .btn:hover{background:var(--primary-600)}
  .btn-outline{
    background:#fff;color:var(--primary);border:1px solid var(--primary);
    box-shadow:none
  }
  #calendar{
    max-width:1200px;margin:0 auto 28px;background:#fff;border:1px solid var(--line);
    border-radius:16px;padding:8px 10px;box-shadow:0 10px 24px rgba(0,0,0,.06)
  }

  /* ===== FullCalendar theme ===== */
  .fc .fc-toolbar{gap:10px}
  .fc .fc-toolbar-title{font-weight:800;color:var(--ink)}
  .fc .fc-button{
    background:var(--ink);border:1px solid var(--ink);color:#fff;border-radius:10px;padding:7px 12px
  }
  .fc .fc-button:disabled{opacity:.5}
  .fc .fc-button-primary:not(:disabled).fc-button-active,
  .fc .fc-button-primary:not(:disabled):active{background:var(--primary);border-color:var(--primary)}
  .fc-theme-standard .fc-scrollgrid,
  .fc-theme-standard td, .fc-theme-standard th{border-color:var(--line)}
  .fc-day-today{background:#f6f9ff}
  .fc .fc-col-header-cell-cushion, .fc-daygrid-day-number{color:var(--ink);font-weight:600}

  /* Eventos: pastillas azul/negro/blanco */
  .fc-event{
    border-radius:10px;padding:2px 6px;
    font-weight:700;color:#fff; /* texto blanco por contraste */
  }
  .fc-event:hover{filter:brightness(1.05)}
  .fc .fc-daygrid-event-dot{display:none} /* sin puntito, solo pill */

  /* Leyenda */
  .legend{display:flex;gap:10px;flex-wrap:wrap;padding:6px 22px 16px}
  .chip{
    display:inline-flex;align-items:center;gap:8px;background:var(--chip);
    border:1px solid var(--line);border-radius:999px;padding:6px 10px;font-size:13px;color:var(--ink)
  }
  .b{width:14px;height:14px;border-radius:4px}
</style>

</head>
<body>

<?php
$hoy = date('Y-m-d'); $mesIni = date('Y-m-01'); $mesFin = date('Y-m-t');
$getN = fn($sql)=> (int)($conn->query($sql)->fetch_assoc()['n'] ?? 0);
$kpi_hoy  = $getN("SELECT COUNT(*) n FROM citas_medicas WHERE fecha='{$hoy}'");
$kpi_aus  = $getN("SELECT COUNT(*) n FROM citas_medicas WHERE estado='ausente' AND fecha BETWEEN '{$mesIni}' AND '{$mesFin}'");
$kpi_prox = $getN("SELECT COUNT(*) n FROM citas_medicas WHERE fecha BETWEEN '{$hoy}' AND DATE_ADD('{$hoy}', INTERVAL 14 DAY)");
?>

<div class="topbar">
  <div class="kpis">
    <div class="kpi"><div>Hoy</div><div class="n"><?= $kpi_hoy ?></div></div>
    <div class="kpi"><div>Ausencias (mes)</div><div class="n"><?= $kpi_aus ?></div></div>
    <div class="kpi"><div>Próx. 14 días</div><div class="n"><?= $kpi_prox ?></div></div>
  </div>
  <div>
    <a class="btn btn-outline" href="panelAdmin.php"><i class="fa-solid fa-arrow-left"></i> Volver</a>
  </div>
</div>

<!-- Filtros -->
<div class="filters">
  <select id="f-estado">
    <option value="">Estado: Todos</option>
    <option value="pendiente">Pendiente</option>
    <option value="en_atencion">En atención</option>
    <option value="atendida">Atendida</option>
    <option value="ausente">Ausente</option>
    <option value="cancelada">Cancelada</option>
  </select>

  <select id="f-doctor">
    <option value="">Doctor: Todos</option>
    <?php foreach($doctores as $d): ?>
      <option value="<?= (int)$d['id'] ?>"><?= h($d['nombre']) ?></option>
    <?php endforeach; ?>
  </select>

  <input type="date" id="f-desde" value="<?= h($mesIni) ?>">
  <input type="date" id="f-hasta" value="<?= h($mesFin) ?>">
  <button class="btn" id="btn-aplicar"><i class="fa-solid fa-filter"></i> Aplicar</button>
  <button class="btn btn-outline" id="btn-limpiar">Limpiar</button>
</div>

<!-- Leyenda -->
<div class="legend">
  <div class="chip"><span class="b" style="background:#f9e505ff;"></span> Pendiente</div>
  <div class="chip"><span class="b" style="background:#18a3a8ff;"></span> En atención</div>
  <div class="chip"><span class="b" style="background:#08d720ff;"></span> Atendida</div>
  <div class="chip"><span class="b" style="background:#0966e8ff;"></span> Ausente</div>
  <div class="chip"><span class="b" style="background:#ad1c1cff;"></span> Cancelada</div>
</div>


<div id="calendar"></div>

<script>
  // Map de colores por estado
  // Colores por estado (azul/negro/blanco)
const stateColors = {
  pendiente:   '#f9e505ff', // 
  en_atencion: '#18a3a8ff', // 
  atendida:    '#08d720ff', // 
  ausente:     '#0966e8ff', // 
  cancelada:   '#ad1c1cff'  // 
};

  const calendarEl = document.getElementById('calendar');
  const calendar = new FullCalendar.Calendar(calendarEl, {
  initialView: 'dayGridMonth',
  height: 'auto',
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
  },
  locale: 'es',
  firstDay: 1,
  slotMinTime: '07:00:00',
  slotMaxTime: '19:00:00',

  // 👇 Nuevo bloque
  eventDidMount(info){
  const t = info.event;
  info.el.title = `${t.title}\n${t.extendedProps.estado}`;

  // texto  siempre (por tu paleta oscura)
  info.el.style.color = '#0e0e0eff';

  if (t.extendedProps.estado === 'ausente' || t.extendedProps.estado === 'cancelada') {
    info.el.style.border = '2px dashed #e53935';
  }
},


 eventClick(info){
  const id = info.event.id;
  if (!id) return;
  // antes: ../Secretaria/citas_ver.php?id=...
  window.location.href = 'ver_cita.php?id=' + id;   // 👈 nuevo
},

  events: fetchEvents
});


  calendar.render();

  function fetchEvents(fetchInfo, successCallback, failureCallback){
    const estado = document.getElementById('f-estado').value;
    const doctor = document.getElementById('f-doctor').value;
    const desde  = document.getElementById('f-desde').value || fetchInfo.startStr;
    const hasta  = document.getElementById('f-hasta').value || fetchInfo.endStr;

    const url = new URL(window.location.origin + '<?= dirname($_SERVER['PHP_SELF']) ?>/api_citas_admin.php');
    url.searchParams.set('desde', desde);
    url.searchParams.set('hasta', hasta);
    if (estado) url.searchParams.set('estado', estado);
    if (doctor) url.searchParams.set('doctor', doctor);

    fetch(url.toString(), {credentials: 'same-origin'})
      .then(r => r.json())
      .then(events => {
        // aplicar colores
        events.forEach(ev => {
          ev.backgroundColor = stateColors[ev.extendedProps.estado] || '#607d8b';
          ev.borderColor = ev.backgroundColor;
        });
        successCallback(events);
      })
      .catch(err => failureCallback(err));
  }

  document.getElementById('btn-aplicar').addEventListener('click', e=>{
    e.preventDefault();
    calendar.refetchEvents();
  });
  document.getElementById('btn-limpiar').addEventListener('click', e=>{
    e.preventDefault();
    document.getElementById('f-estado').value='';
    document.getElementById('f-doctor').value='';
    // mes actual
    const today = new Date();
    const ym = today.toISOString().slice(0,7);
    document.getElementById('f-desde').value = ym+'-01';
    document.getElementById('f-hasta').value = new Date(today.getFullYear(), today.getMonth()+1, 0).toISOString().slice(0,10);
    calendar.refetchEvents();
  });
</script>
</body>
</html>
