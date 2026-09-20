<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /login");
    exit();
}

// Validar rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== $rolPermitido) {
    header("Location: /no-autorizado");
    exit();
}
