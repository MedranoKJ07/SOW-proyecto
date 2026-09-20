<?php
session_start();
require_once __DIR__ . '/../conexion.php';
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../login.php'); exit;
}
$conn = conectarDB();
function h($v){return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Nuevo proveedor | Óptica West</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../css/bootstrap.min.css">
<style>
:root{--bg:#0b1220;--surface:#121b36;--surface2:#0e162b;--primary:#2f6df6;
--text:#e7ecff;--muted:#9fb0d9;--shadow:0 12px 32px rgba(0,0,0,.35);}
body{margin:0;background:linear-gradient(180deg,var(--bg),#0a0f1c 60%);
color:var(--text);font-family:"Inter",system-ui,Segoe UI,Roboto,Arial,sans-serif;}
.wrap{max-width:900px;margin:28px auto;padding:0 16px}
.header{background:linear-gradient(135deg,#13234a,#0f1d3d);border:1px solid rgba(255,255,255,.06);
border-radius:18px;box-shadow:var(--shadow);padding:18px 20px;display:flex;justify-content:space-between;align-items:center}
.btn-ghost{color:var(--text);border:1px solid rgba(255,255,255,.16);padding:.6rem 1rem;border-radius:12px;text-decoration:none}
.btn-ghost:hover{background:rgba(255,255,255,.06)}
.panel{background:linear-gradient(180deg,var(--surface),var(--surface2));border:1px solid rgba(255,255,255,.06);
border-radius:16px;padding:16px;box-shadow:var(--shadow);}
label{color:var(--muted)}
.form-control{background:#0b1327!important;color:var(--text)!important;border:1px solid rgba(255,255,255,.12)!important}
.btn-primary{background:var(--primary);border:none;border-radius:12px}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>➕ Nuevo proveedor</h1>
    <a class="btn-ghost" href="./proveedores_listar.php">⬅ Volver al listado</a>
  </div>

  <div class="panel" style="margin-top:18px">
    <form method="post" action="./proveedores_guardar.php">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre</label>
          <input type="text" name="nombre" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Nombre comercial</label>
          <input type="text" name="nombre_comercial" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Teléfono</label>
          <input type="text" name="telefono" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Correo</label>
          <input type="email" name="email" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Ciudad</label>
          <input type="text" name="ciudad" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Departamento</label>
          <input type="text" name="departamento" class="form-control">
        </div>
        <div class="col-12">
          <label class="form-label">Dirección</label>
          <textarea name="direccion" class="form-control" rows="2"></textarea>
        </div>
      </div>

      <div class="text-end mt-3">
        <button class="btn btn-primary" type="submit">Guardar proveedor</button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
