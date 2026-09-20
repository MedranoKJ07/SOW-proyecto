<?php
session_start(); // si el check-in lo hace secretaria, valida rol aquí si quieres
require_once __DIR__.'/conexion.php';
$conn = conectarDB();

$id = filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
if(!$id){ header('Location: ver_citas.php?msg=Falta+ID'); exit; }

$sql = "UPDATE citas_medicas
        SET checkin_en = NOW(),
            estado = 'pendiente',
            actualizado_en = NOW()
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$id);
$stmt->execute();

$ok = $stmt->affected_rows > 0;
$msg = $ok ? "Check-in+hecho+para+la+cita+$id" : "No+se+actualizo+nada+(verifica+ID/estado)";
header("Location: ver_citas.php?msg=$msg");
