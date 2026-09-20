<?php
$op = $_GET['op'] ?? null;

// Si no se indica una opción, redirigir por defecto a 'ver_gastos'
if ($op === null) {
    header("Location: ?op=ver_gastos");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Contabilidad</title>
    <link rel="stylesheet" href="contabilidad.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .botones-panel {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-top: 40px;
        }

        .boton-opcion {
            background-color: white;
            padding: 25px 30px;
            border-radius: 15px;
            text-align: center;
            width: 200px;
            box-shadow: 0px 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
        }

        .boton-opcion:hover {
            transform: scale(1.03);
            box-shadow: 0px 8px 20px rgba(0,0,0,0.15);
        }

        .boton-opcion i {
            color:rgb(0, 0, 0);
            margin-bottom: 8px;
        }

        .boton-opcion strong {
            color:rgb(47, 0, 28);
            font-size: 16px;
        }
    </style>
</head>
<body>
<a href="../Administrador/panelAdmin.php" class="btn-volver">← Volver al Panel Administrador</a>

<div class="layout-contabilidad">
    <!-- Menú lateral -->
    <div class="menu-lateral">
        <h2>Contabilidad</h2>
        <a href="?op=registrar_gasto" class="boton-opcion">
    <i class="fas fa-file-invoice-dollar fa-lg"></i> Registrar Gasto
</a>
<a href="?op=ver_gastos" class="boton-opcion">
    <i class="fas fa-list-ul fa-lg"></i> Ver Gastos
</a>
<a href="dashboard_financiero.php" class="boton-opcion dashboard-btn">
    <i class="fas fa-chart-pie fa-lg"></i>
    <strong>Dashboard<br>Financiero</strong>
</a>



</a>
<a href="?op=Ver_compras" class="boton-opcion">
    <i class="fas fa-clipboard-list fa-lg"></i> Ver Compras
</a>
<a href="?op=resumen_contable" class="boton-opcion resumen-btn">
    <i class="fas fa-calendar-alt fa-lg"></i>
    <strong>Resumen<br>Mensual</strong>
</a>



    </div>

    <!-- Contenido principal -->
    <div class="contenido-panel">
    <?php
    switch ($op) {
        case 'registrar_gasto':
            include 'registrar_gasto.php';
            break;
        case 'ver_gastos':
            include 'ver_gastos.php';
            break;
        case 'registrar_compra':
            include 'registrar_compra.php';
            break;
           case 'Ver_compras':
          include 'ver_compras.php';
           break;

        case 'resumen_contable':
            include 'resumen_contable.php';
            break;
        default:
            echo "<h2>Selecciona una opción del panel</h2>";
            echo "<p style='color:#666;'>Aquí se mostrarán las funciones contables al hacer clic.</p>";
            break;
    }
    ?>
</div>


</body>
</html>
