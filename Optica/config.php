<?php
// URL pública del proyecto. BASE_URL puede sobrescribirse por entorno.
$configuredBaseUrl = trim((string)(getenv('BASE_URL') ?: ''));
if ($configuredBaseUrl === '') {
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  $scheme = $https ? 'https' : 'http';
  $configuredBaseUrl = $scheme . '://' . $host . '/sow/Optica/';
}

define('BASE_URL', rtrim($configuredBaseUrl, '/') . '/');

define('RESET_EXPIRA', 1800);

function base_url(): string {
  return rtrim(BASE_URL, '/') . '/';
}
