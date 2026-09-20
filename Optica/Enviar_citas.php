<?php
session_start();
date_default_timezone_set('America/Managua');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Carga Composer autoload
require __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . '/conexion.php';

$conn = conectarDB();

// Correo destino configurable; no se guarda una dirección personal en el código.
$correo_doctor = trim((string)(getenv('SMTP_DOCTOR_TO') ?: ''));
if ($correo_doctor === '') {
    header('Location: panel_secretaria.php?error=' . urlencode('Configura SMTP_DOCTOR_TO para enviar el resumen de citas.'));
    exit;
}

// Obtener citas del día
$fechaHoy = date('Y-m-d');
$stmt = $conn->prepare("SELECT c.hora, c.motivo, p.nombre 
                        FROM citas_medicas c 
                        JOIN pacientes p ON c.paciente_id = p.id 
                        WHERE c.fecha = ?");
$stmt->bind_param("s", $fechaHoy);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: panel_secretaria.php?error=' . urlencode('No hay citas para hoy.'));
    exit;
}

// Preparar cuerpo del correo con formato profesional
$fechaActual = date('d/m/Y');
$cuerpo = '
<div style="font-family: Arial, sans-serif; color: #333;">
    <h2 style="color: #2563EB;">📅 Citas pendientes para hoy (' . $fechaActual . ')</h2>
    <p>Estimado doctor, estas son las citas agendadas:</p>

    <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="border: 1px solid #ccc; padding: 8px;">Paciente</th>
                <th style="border: 1px solid #ccc; padding: 8px;">Hora</th>
                <th style="border: 1px solid #ccc; padding: 8px;">Motivo</th>
            </tr>
        </thead>
        <tbody>';

while ($fila = $result->fetch_assoc()) {
    $cuerpo .= '
        <tr>
            <td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($fila['nombre'], ENT_QUOTES, 'UTF-8') . '</td>
            <td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($fila['hora'], ENT_QUOTES, 'UTF-8') . '</td>
            <td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($fila['motivo'], ENT_QUOTES, 'UTF-8') . '</td>
        </tr>';
}

$cuerpo .= '
        </tbody>
    </table>
    <p style="margin-top: 20px;">Saludos cordiales,<br><strong>Óptica SOW</strong></p>
</div>';

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = getenv('SMTP_HOST') ?: 'localhost';
    $mail->SMTPAuth = (bool)(getenv('SMTP_USERNAME') && getenv('SMTP_PASSWORD'));
    $mail->Username = getenv('SMTP_USERNAME') ?: '';
    $mail->Password = getenv('SMTP_PASSWORD') ?: '';
    $mail->SMTPSecure = getenv('SMTP_ENCRYPTION') ?: '';
    $mail->Port = (int)(getenv('SMTP_PORT') ?: 25);
    $mail->CharSet = 'UTF-8';

    $mail->setFrom(getenv('SMTP_FROM') ?: 'no-reply@localhost', 'Optica SOW');
    $mail->addAddress($correo_doctor, 'Dr. William');

    $mail->isHTML(true);
    $mail->Subject = 'Citas medicas pendientes - ' . $fechaActual;
    $mail->Body = $cuerpo;
    $mail->AltBody = 'Citas médicas pendientes para hoy ' . $fechaActual;

    $mail->send();

    header('Location: panel_secretaria.php?enviado=1');
    exit;
} catch (Exception $e) {
    header('Location: panel_secretaria.php?error=' . urlencode('Error al enviar el correo: ' . $mail->ErrorInfo));
    exit;
}
