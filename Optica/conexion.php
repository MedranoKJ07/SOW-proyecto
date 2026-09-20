<?php
/**
 * Punto único de conexión. En XAMPP usa los valores locales por defecto;
 * en otro entorno se pueden proporcionar DB_* sin editar el código.
 */
function conectarDB(): mysqli
{
    $host = trim((string)(getenv('DB_HOST') ?: '127.0.0.1'));
    $user = (string)(getenv('DB_USER') ?: 'root');
    $pass = (string)(getenv('DB_PASS') ?: '');
    $name = trim((string)(getenv('DB_NAME') ?: 'optica'));
    $port = (int)(getenv('DB_PORT') ?: 3306);

    if ($host === '' || $user === '' || $name === '' || $port < 1 || $port > 65535) {
        throw new RuntimeException('Configuración de base de datos inválida.');
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = new mysqli($host, $user, $pass, $name, $port);

    if ($conn->connect_errno) {
        error_log('Conexión MySQL fallida: código ' . $conn->connect_errno);
        throw new RuntimeException('No fue posible conectar con la base de datos local.');
    }

    if (!$conn->set_charset('utf8mb4')) {
        $conn->close();
        throw new RuntimeException('No fue posible establecer utf8mb4.');
    }

    return $conn;
}
