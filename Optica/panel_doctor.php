<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'doctor') {
    header('Location: login.php'); exit;
}

require_once 'conexion.php';
$conn = conectarDB();

/* ------------ Helpers ------------ */
function normalizeCedula(?string $c): string {
    $c = strtoupper(trim($c ?? ''));
    return str_replace(['-', ' '], '', $c);
}
function isCedulaFormat(string $c): bool {
    $c = normalizeCedula($c);
    return (bool)preg_match('/^\d{3}\d{6}\d{4}[A-Z]$/', $c);
}
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/* ------------ Estado ------------ */
$mensaje          = '';
$historial        = '';
$drawerHtml       = '';  // contenido del panel lateral
$openDrawer       = false;
$mostrarHistorial = false;
$nombrePaciente   = '';

/* Doctor */


$id_usuario   = $_SESSION['id_usuario'];
$stmtNombre   = $conn->prepare("SELECT nombre FROM usuarios WHERE id = ?");
$stmtNombre->bind_param("i", $id_usuario);
$stmtNombre->execute();
$resNombre    = $stmtNombre->get_result();
$nombreDoctor = $resNombre->fetch_assoc()['nombre'] ?? $_SESSION['usuario'];
$stmtNombre->close();

/* ------------ POST ------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $buscar = trim($_POST['buscar'] ?? '');
    $buscarNorm = normalizeCedula($buscar);

    // --- BUSCAR ---
    if ($accion === 'buscar') {
        if ($buscar === '') {
            $mensaje = 'Escribe un nombre o cédula para buscar.';
        } else {
            if (isCedulaFormat($buscarNorm)) {
                $stmt = $conn->prepare("
                    SELECT id, nombre, telefono, edad, cedula
                    FROM pacientes
                    WHERE REPLACE(UPPER(cedula), '-', '') = ?
                ");
                $stmt->bind_param("s", $buscarNorm);
            } else {
                $like = "%$buscar%";
                $stmt = $conn->prepare("
                    SELECT id, nombre, telefono, edad, cedula
                    FROM pacientes
                    WHERE nombre LIKE ?
                    ORDER BY nombre
                    LIMIT 50
                ");
                $stmt->bind_param("s", $like);
            }

            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows > 1 && !isCedulaFormat($buscarNorm)) {
                // Construimos el Drawer lateral con la lista
                $mensaje = "Se encontraron {$resultado->num_rows} coincidencias. Selecciona un paciente:";
                ob_start();
                ?>
                <div id="drawer" class="drawer">
                  <div class="drawer-header">
                    <strong>Resultados</strong>
                    <button type="button" class="btn-close" aria-label="Cerrar" onclick="closeDrawer()">×</button>
                  </div>
                  <div class="drawer-body">
                    <?php while ($row = $resultado->fetch_assoc()): ?>
                      <form method="POST" class="item-resultado" onsubmit="closeDrawer()">
                        <input type="hidden" name="accion" value="seleccionar">
                        <input type="hidden" name="id_paciente" value="<?php echo (int)$row['id']; ?>">
                        <button type="submit" title="Seleccionar">
                          <span class="nombre"><?php echo h($row['nombre']); ?></span>
                          <span class="meta">Edad: <?php echo (int)$row['edad']; ?> · Céd: <?php echo h($row['cedula'] ?? ''); ?></span>
                        </button>
                      </form>
                    <?php endwhile; ?>
                  </div>
                </div>
                <div id="backdrop" class="backdrop" onclick="closeDrawer()"></div>
                <?php
                $drawerHtml = ob_get_clean();
                $openDrawer = true;
            } elseif ($resultado->num_rows === 1) {
                // Único match: cargar historial
                $paciente       = $resultado->fetch_assoc();
                $id_paciente    = (int)$paciente['id'];
                $nombrePaciente = $paciente['nombre'];

                $_SESSION['paciente_id']     = $id_paciente;
                $_SESSION['paciente_nombre'] = $nombrePaciente;

                $mensaje = "Paciente: ".h($nombrePaciente)." (ID: {$id_paciente})";

                $stmtH = $conn->prepare("
                    SELECT * FROM historial_clinico
                    WHERE paciente_id = ?
                    ORDER BY fecha DESC
                ");
                $stmtH->bind_param("i", $id_paciente);
                $stmtH->execute();
                $resH = $stmtH->get_result();

                $mostrarHistorial = true;
                if ($resH->num_rows > 0) {
                    while ($row = $resH->fetch_assoc()) {
                        $fecha = date('d/m/Y H:i', strtotime($row['fecha']));
                        $historial .= "<div class='historial-card'>
                            <h4>📅 {$fecha}</h4>
                            <p><strong>Tipo de Lente:</strong> ".h($row['tipo_lente'])."</p>
                            <p><strong>Diagnóstico:</strong> ".h($row['diagnostico'])."</p>
                            <p><strong>Observaciones:</strong> ".h($row['observaciones'])."</p>
                            <p><strong>Medidas anteriores:</strong> ".h($row['medidas_anteriores'])."</p>
                            <h5>Ojo Derecho:</h5>
                            <p>Esfera: ".h($row['esfera_od']).", Cilindro: ".h($row['cilindro_od']).", Eje: ".h($row['eje_od']).", Adición: ".h($row['adicion_od'])."</p>
                            <p>AVS: ".h($row['avs_lentes_od']).", AVC: ".h($row['avc_lentes_od']).", DIP: ".h($row['dip_od']).", Altura: ".h($row['altura_od'])."</p>
                            <h5>Ojo Izquierdo:</h5>
                            <p>Esfera: ".h($row['esfera_oi']).", Cilindro: ".h($row['cilindro_oi']).", Eje: ".h($row['eje_oi']).", Adición: ".h($row['adicion_oi'])."</p>
                            <p>AVS: ".h($row['avs_lentes_oi']).", AVC: ".h($row['avc_lentes_oi']).", DIP: ".h($row['dip_oi']).", Altura: ".h($row['altura_oi'])."</p>
                        </div>";
                    }
                } else {
                    $historial = "<p>Este paciente no tiene historial clínico registrado.</p>";
                }
                $stmtH->close();
            } else {
                $mensaje = "Paciente no encontrado.";
            }
            $stmt->close();
        }
    }

    // --- SELECCIONAR (desde el drawer) ---
    elseif ($accion === 'seleccionar' && isset($_POST['id_paciente'])) {
        $id_paciente = (int)$_POST['id_paciente'];
        $stmt = $conn->prepare("SELECT id, nombre FROM pacientes WHERE id = ?");
        $stmt->bind_param("i", $id_paciente);
        $stmt->execute();
        $pac = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($pac) {
            $_SESSION['paciente_id']     = $id_paciente;
            $_SESSION['paciente_nombre'] = $pac['nombre'];
            $nombrePaciente              = $pac['nombre'];
            $mensaje = "Paciente: ".h($nombrePaciente)." (ID: {$id_paciente})";

            $stmtH = $conn->prepare("
                SELECT *
                FROM historial_clinico
                WHERE paciente_id = ?
                ORDER BY fecha DESC
            ");
            $stmtH->bind_param("i", $id_paciente);
            $stmtH->execute();
            $resH = $stmtH->get_result();

            $mostrarHistorial = true;
            if ($resH->num_rows > 0) {
                while ($row = $resH->fetch_assoc()) {
                    $fecha = date('d/m/Y H:i', strtotime($row['fecha']));
                    $historial .= "<div class='historial-card'>
                        <h4>📅 {$fecha}</h4>
                        <p><strong>Tipo de Lente:</strong> ".h($row['tipo_lente'])."</p>
                        <p><strong>Diagnóstico:</strong> ".h($row['diagnostico'])."</p>
                        <p><strong>Observaciones:</strong> ".h($row['observaciones'])."</p>
                        <p><strong>Medidas anteriores:</strong> ".h($row['medidas_anteriores'])."</p>
                        <h5>Ojo Derecho:</h5>
                        <p>Esfera: ".h($row['esfera_od']).", Cilindro: ".h($row['cilindro_od']).", Eje: ".h($row['eje_od']).", Adición: ".h($row['adicion_od'])."</p>
                        <p>AVS: ".h($row['avs_lentes_od']).", AVC: ".h($row['avc_lentes_od']).", DIP: ".h($row['dip_od']).", Altura: ".h($row['altura_od'])."</p>
                        <h5>Ojo Izquierdo:</h5>
                        <p>Esfera: ".h($row['esfera_oi']).", Cilindro: ".h($row['cilindro_oi']).", Eje: ".h($row['eje_oi']).", Adición: ".h($row['adicion_oi'])."</p>
                        <p>AVS: ".h($row['avs_lentes_oi']).", AVC: ".h($row['avc_lentes_oi']).", DIP: ".h($row['dip_oi']).", Altura: ".h($row['altura_oi'])."</p>
                    </div>";
                }
            } else {
                $historial = "<p>Este paciente no tiene historial clínico registrado.</p>";
            }
            $stmtH->close();
        } else {
            $mensaje = "Paciente no encontrado.";
            $mostrarHistorial = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel del Doctor</title>
  <link rel="stylesheet" href="dashboard_doctor.css">
  <style>
    /* Drawer lateral */
    .backdrop{
      position:fixed; inset:0; background:rgba(0,0,0,.35);
      opacity:0; pointer-events:none; transition:opacity .2s ease;
      z-index: 49;
    }
    .backdrop.show{ opacity:1; pointer-events:auto; }
    .drawer{
      position:fixed; top:0; right:0; height:100vh; width:420px;
      max-width:95vw; background:#fff; box-shadow:-8px 0 24px rgba(0,0,0,.15);
      transform:translateX(100%); transition:transform .25s ease;
      z-index: 50; display:flex; flex-direction:column;
      border-left:1px solid #083da7ff; border-top-left-radius:12px; border-bottom-left-radius:12px;
    }
    .drawer.open{ transform:translateX(0); }
    .drawer-header{ display:flex; align-items:center; justify-content:space-between; padding:14px 16px; border-bottom:1px solid #eef2f7; }
    .drawer-body{ padding:12px; overflow:auto; flex:1; }
    .btn-close{ border:none; background:#f3f4f6; width:34px; height:34px; border-radius:10px; font-size:20px; cursor:pointer }
    .btn-close:hover{ background:#e5e7eb; }

    /* Resultados del drawer */
    .item-resultado{ margin-bottom:8px }
    .item-resultado button{
      width:100%; text-align:left; padding:12px 14px; border:1px solid #e5e7eb;
      border-radius:12px; background:#ffffff; cursor:pointer;
      display:flex; flex-direction:column; gap:4px;
    }
    .item-resultado button:hover{ background:#f8fafc; }
    .item-resultado .nombre{ font-weight:600; color:#111; }
    .item-resultado .meta{ font-size:.9rem; color:#111; }

    .mensaje{margin-top:10px;color:#111;background:#fff8e1;border:1px solid #facc15;padding:10px;border-radius:8px}
    .historial-card{border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin:10px 0;background:#fff}

    .formulario label{display:block;margin-top:8px}
    .formulario input{width:100%;padding:10px;border:1px solid #227affff;border-radius:10px}
    .form-buttons{display:flex;gap:10px;margin-top:12px}
    .dashboard-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:10px}
    .dashboard-card{display:block;padding:12px;text-align:center;border:1px solid #1a5ce0ff;border-radius:12px;background:#fff}
  </style>
</head>

<?php if (!empty($_SESSION['postlogin_notice'])): ?>
  <div style="margin:10px auto;max-width:900px;color:#065f46;background:#d1fae5;border:1px solid #34d399;padding:12px;border-radius:10px">
    <?php echo h($_SESSION['postlogin_notice']); ?>
    <div style="margin-top:8px">
      <a href="/cambiar_password.php" style="margin-right:10px;">Cambiar contraseña</a>
      <a href="#" onclick="this.parentElement.parentElement.remove();return false;">Continuar</a>
    </div>
  </div>
  <?php unset($_SESSION['postlogin_notice']); ?>
<?php endif; ?>

<body>
<a href="logout.php" class="logout-btn">Cerrar sesión</a>

<div class="container">
  <div class="sidebar">
    <h2><?php echo h($nombreDoctor); ?></h2>

    <form method="POST" class="formulario" autocomplete="off">
      <label>Nombre o Cédula (para buscar):</label>
      <input type="text" name="buscar" placeholder="Ej: Ana López o 0010505051016N / 001-050505-1016N">

      <div class="form-buttons">
        <button type="submit" name="accion" value="buscar">Buscar</button>
      </div>
    </form>

    <?php if ($mensaje): ?>
      <p class="mensaje"><?php echo $mensaje; ?></p>
    <?php endif; ?>
  </div>

  <div class="main-panel">
    <div class="dashboard-grid">
      <a href="ver_citas.php" class="dashboard-card">Ver Citas</a>
      <a href="ver_facturas.php" class="dashboard-card">Ver Facturas</a>
      <a href="insertar_historial.php" class="dashboard-card">Nuevo Diagnóstico</a>
      <a href="ver_historial.php" class="dashboard-card">Ver Historiales</a>
    </div>

    <?php if ($mostrarHistorial): ?>
      <div class="historial-container">
        <h3>🩺 Historial clínico de: <u><?php echo h($nombrePaciente); ?></u></h3>
        <?php echo $historial; ?>
        <div class="historial-buttons" style="display:flex;gap:10px;margin-top:10px">
          <form action="imprimir_historial.php" method="post" target="_blank">
            <button type="submit">Imprimir</button>
          </form>
          <form action="insertar_historial.php" method="get">
            <button type="submit">Añadir nuevo diagnóstico</button>
          </form>
          <form action="panel_doctor.php" method="get">
            <button type="submit">Buscar otro paciente</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <!-- Drawer & Backdrop -->
    <?php echo $drawerHtml; ?>
  </div>
</div>

<script>
  // Abre el drawer si PHP lo indicó
  (function(){
    var shouldOpen = <?php echo $openDrawer ? 'true' : 'false'; ?>;
    if(shouldOpen){
      openDrawer();
    }
  })();

  function openDrawer(){
    var d = document.getElementById('drawer');
    var b = document.getElementById('backdrop');
    if(d){ d.classList.add('open'); }
    if(b){ b.classList.add('show'); }
  }
  function closeDrawer(){
    var d = document.getElementById('drawer');
    var b = document.getElementById('backdrop');
    if(d){ d.classList.remove('open'); }
    if(b){ b.classList.remove('show'); }
  }
</script>
</body>
</html>
