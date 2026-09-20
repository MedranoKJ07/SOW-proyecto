<?php
session_start(); // doctor
require_once __DIR__.'/conexion.php';
$conn = conectarDB();

$id = filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
if(!$id){ header('Location: ver_citas.php?msg=Falta+ID'); exit; }

$sql = "UPDATE citas_medicas
        SET inicio_atencion = NOW(),
            estado = 'en_atencion',
            actualizado_en = NOW()
        WHERE id = ? AND checkin_en IS NOT NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$id);
$stmt->execute();

$ok = $stmt->affected_rows > 0;
$msg = $ok ? "Atencion+iniciada+en+cita+$id" : "No+se+inicia:+falta+check-in+o+ID+no+valido";
header("Location: ver_citas.php?msg=$msg");
