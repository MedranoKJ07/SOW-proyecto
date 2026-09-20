<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

echo "BASE_URL: " . BASE_URL . "<br>";

$db = conectarDB();
echo "✅ Conectó a DB<br>";

$r = $db->query("SHOW TABLES");
if (!$r) {
  die("❌ Error SHOW TABLES: " . $db->error);
}

echo "Tablas:<br>";
while ($row = $r->fetch_row()) {
  echo "- " . $row[0] . "<br>";
}
