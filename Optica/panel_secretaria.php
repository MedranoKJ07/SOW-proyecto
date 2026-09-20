<?php 
session_start();
date_default_timezone_set('America/Managua'); // o tu zona


if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'secretaria') {
    header('Location: login.php');
    exit;
}

include_once 'conexion.php';
$conn = conectarDB();

$hoy = date('Y-m-d');
//echo "<p style='color:red; font-weight:bold;'>Fecha PHP detectada: $hoy</p>";

$stmt = $conn->prepare("SELECT c.*, p.nombre FROM citas_medicas c LEFT JOIN pacientes p ON c.paciente_id = p.id WHERE c.fecha = ? AND c.estado = 'pendiente' ORDER BY c.hora ASC");

$stmt->bind_param("s", $hoy);
$stmt->execute();
$result = $stmt->get_result();
//echo "<p style='color:blue; font-weight:bold;'>Número de citas encontradas: " . $result->num_rows . "</p>";

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Secretaría</title>
    <link rel="stylesheet" href="style.css">
    <?php if (!empty($_SESSION['postlogin_notice'])): ?>
  <div style="margin:10px auto;max-width:900px;color:#065f46;background:#d1fae5;border:1px solid #34d399;padding:12px;border-radius:10px">
    <?php echo htmlspecialchars($_SESSION['postlogin_notice'],ENT_QUOTES,'UTF-8'); ?>
    <div style="margin-top:8px">
      <a href="/cambiar_password.php" style="margin-right:10px;">Cambiar contraseña</a>
      <a href="#" onclick="this.parentElement.parentElement.remove();return false;">Continuar</a>
    </div>
  </div>
  <?php unset($_SESSION['postlogin_notice']); ?>
<?php endif; ?>
    <style>
        

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        header {
            background: linear-gradient(to right, #cbe9f5, #a2d4ec);
            padding: 25px;
            text-align: center;
            border-radius: 0 0 20px 20px;
        }

        header h2 {
            margin: 0;
            font-size: 26px;
            color: #2c3e50;
        }

        nav {
            background: white;
            padding: 15px 30px;
            box-shadow: 0px 2px 6px rgba(0,0,0,0.1);
            display: flex;
            gap: 20px;
            align-items: center;
        }

        nav a {
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            font-size: 15px;
            transition: color 0.2s ease;
        }

        nav a:hover {
            color: #1e88e5;
        }

        .contenedor {
            padding: 30px;
            max-width: 1000px;
            margin: auto;
        }

        .tarjeta {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        .tarjeta h3 {
            margin-top: 0;
            color: #1a237e;
        }

        .calendario {
            background: #f9f9f9;
            padding: 12px 20px;
            border-radius: 8px;
            display: inline-block;
            font-weight: 500;
            color: #1e88e5;
        }

        .boton-crear-factura {
            display: flex;
            justify-content: center;
        }

        .container {
            background-color: #ffffff;
            display: flex;
            width: 460px;
            height: 120px;
            position: relative;
            border-radius: 6px;
            transition: 0.3s ease-in-out;
            cursor: pointer;
        }

        .container:hover {
            transform: scale(1.03);
            width: 220px;
        }

        .left-side {
            background-color: #5de2a3;
            width: 130px;
            height: 120px;
            border-radius: 4px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-shrink: 0;
            overflow: hidden;
        }

        .right-side {
            width: calc(100% - 130px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            white-space: nowrap;
        }

        .arrow {
            width: 20px;
            height: 20px;
            margin-right: 20px;
        }

        .new {
            font-size: 23px;
            font-family: "Lexend Deca", sans-serif;
            margin-left: 20px;
        }

        .card {
            width: 70px;
            height: 46px;
            background-color: #c7ffbc;
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: absolute;
            top: 10px;
        }

        .card-line {
            width: 65px;
            height: 13px;
            background-color: #80ea69;
            border-radius: 2px;
            margin-top: 7px;
        }

        .buttons {
            width: 8px;
            height: 8px;
            background-color: #379e1f;
            box-shadow: 0 -10px 0 0 #26850e, 0 10px 0 0 #56be3e;
            border-radius: 50%;
            margin-top: 5px;
            transform: rotate(90deg);
            margin: 10px 0 0 -30px;
        }

        .post {
            width: 63px;
            height: 75px;
            background-color: #dddde0;
            position: absolute;
            bottom: 10px;
            top: 120px;
            border-radius: 6px;
        }

        .post-line {
            width: 47px;
            height: 9px;
            background-color: #545354;
            border-radius: 0px 0px 3px 3px;
            margin: 8px auto 0;
        }

        .screen {
            width: 47px;
            height: 23px;
            background-color: #ffffff;
            margin: 22px auto 0;
            border-radius: 3px;
        }

        .dollar {
            text-align: center;
            color: #4b953b;
            font-size: 16px;
        }

        .cita-radio {
            margin: 10px 0;
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            cursor: pointer;
            background-color: #fff;
        }

        .crud-btns {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .crud-btns button {
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        #editBtn { background: #8a2be2; }
        #deleteBtn { background: #e53935; }
        #readBtn { background: #9c27b0; }

        #editBtn:hover { background: #6a1bb1; }
        #deleteBtn:hover { background: #c62828; }
        #readBtn:hover { background: #7b1fa2; }

        footer {
            text-align: center;
            padding: 15px;
            background: #e8eaf6;
            margin-top: 40px;
        }

        /* Scroll en panel de citas */
        #citas-scroll {
    max-height: 250px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 10px;
    background-color: #fff;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.cita-radio {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    border: 1px solid #ccc;
    border-radius: 8px;
    background-color: #fdfdfd;
    font-size: 16px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: background-color 0.2s ease;
}

.cita-radio:hover {
    background-color: #f0f0f0;
}

.cita-radio input[type="radio"] {
    margin-right: 10px;
}
.noti {
    background-color: #e3f2fd;
    border-left: 5px solid #42a5f5;
    color: #1565c0;
    font-weight: 500;
    padding: 12px 20px;
    margin: 20px auto;
    max-width: 900px;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    font-size: 16px;
}

    </style>
</head>
<body>
<?php if (isset($_GET['enviado'])): ?>
  <div style="background:#d4edda;color:#155724;padding:10px;border-radius:5px;margin-bottom:10px;">
     Citas enviadas al correo del doctor.
  </div>
<?php endif; ?>

<header>
    <h2>Bienvenida Secretaria <?= htmlspecialchars($_SESSION['usuario']) ?></h2>
</header>

<nav>
    <a href="agendar_cita.php?tipo=existente">Agendar Cita - Paciente Existente</a>
    <a href="agendar_cita_nuevo.php">Agendar Cita - Nuevo Paciente</a>
     <a href="pacientes_listar.php"> Pacientes</a> <!-- Nuevo botón -->
    <a href="logout.php">Cerrar Sesión</a>
</nav>
<?php if ($result->num_rows > 0): ?>
    <div class="noti">
        ✅ Hoy, <?= $hoy ?> hay <strong><?= $result->num_rows ?></strong> citas registradas.
    </div>
<?php endif; ?>

<div class="contenedor">

    <div class="tarjeta">
        <h3>Citas del día</h3>

        <div class="crud-btns">
            <button id="editBtn">Editar</button>
            <button id="deleteBtn">Borrar</button>
            <button id="readBtn">Leer Más</button>
        </div>
     <form action="Enviar_citas.php" method="post" onsubmit="return confirm('¿Deseas enviar las citas pendientes al doctor por correo?');">
    <button type="submit" style="
        background-color: #2563eb;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        margin-bottom: 15px;
    ">
        📧 Enviar citas al doctor
    </button>
</form>


        <form id="form-citas">
            <div id="citas-scroll">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($cita = $result->fetch_assoc()): ?>
                        <label class="cita-radio">
                            <input type="radio" name="cita_id" value="<?= $cita['id'] ?>">
                            <strong><?= $cita['hora'] ?></strong> - <?= htmlspecialchars($cita['nombre'] ?? 'Sin nombre') ?> (<?= htmlspecialchars($cita['motivo']) ?>)
                        </label>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No hay citas para hoy.</p>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <!-- Historial de todas las citas -->
<?php
$stmtHistorial = $conn->prepare("SELECT c.*, p.nombre FROM citas_medicas c LEFT JOIN pacientes p ON c.paciente_id = p.id ORDER BY c.fecha DESC, c.hora DESC");
$stmtHistorial->execute();
$historial = $stmtHistorial->get_result();
?>

<div class="tarjeta">
    <h3>Historial de Citas</h3>
    <?php if ($historial->num_rows > 0): ?>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse: collapse;">
                <thead style="background-color: #e3f2fd;">
                    <tr>
                        <th style="padding: 10px; text-align: left;">Paciente</th>
                        <th style="padding: 10px; text-align: left;">Fecha</th>
                        <th style="padding: 10px; text-align: left;">Hora</th>
                        <th style="padding: 10px; text-align: left;">Motivo</th>
                        <th style="padding: 10px; text-align: left;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($h = $historial->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 10px;"><?= htmlspecialchars($h['nombre']) ?></td>
                            <td style="padding: 10px;"><?= htmlspecialchars($h['fecha']) ?></td>
                            <td style="padding: 10px;"><?= htmlspecialchars($h['hora']) ?></td>
                            <td style="padding: 10px;"><?= htmlspecialchars($h['motivo']) ?></td>
                            <td style="padding: 10px; font-weight: bold; color:
                                <?= $h['estado'] === 'pendiente' ? '#f57c00' : ($h['estado'] === 'realizada' ? '#388e3c' : '#d32f2f') ?>">
                                <?= htmlspecialchars($h['estado']) ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No hay citas registradas aún.</p>
    <?php endif; ?>
</div>


    <div class="tarjeta">
        <h3>Calendario</h3>
        <div class="calendario">
            <?php
            $fechaActual = new DateTime();
            $diasSemana = [
                
                    'Monday' => 'Lunes',
                    'Tuesday' => 'Martes',
                    'Wednesday' => 'Miércoles',
                    'Thursday' => 'Jueves',
                    'Friday' => 'Viernes',
                    'Saturday' => 'Sábado',
                    'Sunday' => 'Domingo'
                
                
               
                
            ];
            $dia = $diasSemana[$fechaActual->format('l')];
            $fecha = $fechaActual->format('d \d\e F \d\e Y');
            echo "$dia, $fecha";
            ?>
        </div>
    </div>

    <div class="tarjeta boton-crear-factura" onclick="window.location.href='crear_factura.php'">
        <div class="container">
            <div class="left-side">
                <div class="card">
                    <div class="card-line"></div>
                    <div class="buttons"></div>
                </div>
                <div class="post">
                    <div class="post-line"></div>
                    <div class="screen">
                        <div class="dollar">$</div>
                    </div>
                </div>
            </div>
            <div class="right-side">
                <div class="new">Nueva Factura</div>
                <svg class="arrow" viewBox="0 0 451.846 451.847" xmlns="http://www.w3.org/2000/svg"><path fill="#cfcfcf" d="M345.441 248.292L151.154 442.573c-12.359 12.365-32.397 12.365-44.75 0-12.354-12.354-12.354-32.391 0-44.744L278.318 225.92 106.409 54.017c-12.354-12.359-12.354-32.394 0-44.748 12.354-12.359 32.391-12.359 44.75 0l194.287 194.284c6.177 6.18 9.262 14.271 9.262 22.366 0 8.099-3.091 16.196-9.267 22.373z"/></svg>
            </div>
        </div>
    </div>

</div>

<footer>
    <small>Óptica Digital - Panel Secretaría © 2025</small>
</footer>

<script>
document.querySelectorAll('#editBtn, #deleteBtn, #readBtn').forEach(btn => {
    btn.addEventListener('click', function () {
        const selected = document.querySelector('input[name="cita_id"]:checked');
        if (!selected) {
            alert("Selecciona una cita para continuar.");
            return;
        }
        const id = selected.value;
        if (this.id === 'editBtn') {
            window.location.href = `editar_cita.php?id=${id}`;
        } else if (this.id === 'deleteBtn') {
            if (confirm("¿Seguro que quieres eliminar esta cita?")) {
                window.location.href = `eliminar_cita.php?id=${id}`;
            }
        } else if (this.id === 'readBtn') {
            window.location.href = `ver_cita.php?id=${id}`;
        }
    });
});
</script>

</body>
</html>
