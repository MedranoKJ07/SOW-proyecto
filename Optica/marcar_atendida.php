<?php
session_start(); // doctor
require_once __DIR__.'/conexion.php';
$conn = conectarDB();

$id = filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
if(!$id){ header('Location: ver_citas.php?msg=Falta+ID'); exit; }

/* Asegura orden temporal para no violar el CHECK:
   - Si no hay inicio_atencion, lo fija a NOW()
   - fin_atencion >= inicio_atencion + 1 s
*/
$sql = "UPDATE citas_medicas
        SET inicio_atencion = COALESCE(inicio_atencion, NOW()),
            fin_atencion    = GREATEST(NOW(), DATE_ADD(COALESCE(inicio_atencion, NOW()), INTERVAL 1 SECOND)),
            estado          = 'atendida',
            actualizado_en  = NOW()
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$id);
$stmt->execute();

$ok = $stmt->affected_rows > 0;
$msg = $ok ? "Cita+$id+marcada+como+atendida" : "No+se+actualizo:+ID+invalido+o+ya+atendida";
header("Location: ver_citas.php?msg=$msg");
