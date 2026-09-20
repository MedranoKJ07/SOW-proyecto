<?php
// Administrador/compras_crear.php (con lista de productos + autocompletar costo y total)
session_start();
require_once __DIR__ . '/../conexion.php';
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header('Location: ../login.php'); exit;
}
$conn = conectarDB();
function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$id_proveedor = (int)($_GET['id_proveedor'] ?? 0);
if ($id_proveedor <= 0) {
  header('Location: ./proveedores_listar.php?err=Proveedor+inválido'); exit;
}

$stmt = $conn->prepare("SELECT id_proveedor, nombre, nombre_comercial FROM proveedores WHERE id_proveedor = ?");
$stmt->bind_param("i", $id_proveedor);
$stmt->execute();
$prov = $stmt->get_result()->fetch_assoc();
if (!$prov) { die('Proveedor no encontrado'); }

/* ===== Productos para el datalist =====
   Trae id, sku, nombre y el último costo visto en detalle_compra (por nombre).
*/
$sqlProd = "
  SELECT 
    p.id_producto,
    p.sku,
    p.nombre,
    p.precio_venta,
    p.stock_minimo,
    (
      SELECT d.costo_unitario
      FROM detalle_compra d
      WHERE LOWER(TRIM(d.producto)) = LOWER(TRIM(p.nombre))
      ORDER BY d.id_detalle_compra DESC
      LIMIT 1
    ) AS ultimo_costo
  FROM productos p
  ORDER BY p.nombre ASC
";
$rProds = $conn->query($sqlProd);
$productos = [];
if ($rProds && $rProds->num_rows) {
  while($row = $rProds->fetch_assoc()){
    $row['ultimo_costo'] = is_null($row['ultimo_costo']) ? null : (float)$row['ultimo_costo'];
    $productos[] = $row;
  }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Nueva compra | Óptica West</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../css/bootstrap.min.css">
<style>
:root{
  --bg:#0b1220; --bg-soft:#0f1730; --surface:#121b36; --surface-2:#0e162b;
  --primary:#2f6df6; --accent:#0eeaff; --text:#e7ecff; --muted:#9fb0d9;
  --success:#11d18b; --danger:#ff5a79; --shadow:0 12px 32px rgba(0,0,0,.35);
}
body{
  margin:0;background:
    radial-gradient(1200px 600px at -5% -10%, #1b2b57 0%, transparent 60%),
    radial-gradient(900px 500px at 110% 10%, #1a274a 0%, transparent 60%),
    linear-gradient(180deg, var(--bg) 0%, #0a0f1c 60%);
  color:var(--text); font-family:"Inter",system-ui,Segoe UI,Roboto,Arial,sans-serif;
}
.wrap{max-width:1000px;margin:28px auto;padding:0 16px}
.header{background:linear-gradient(135deg,#13234a,#0f1d3d);border:1px solid rgba(255,255,255,.06);
  border-radius:18px;box-shadow:var(--shadow);padding:18px 20px;display:flex;align-items:center;justify-content:space-between}
.btn-ghost{color:var(--text);border:1px solid rgba(255,255,255,.16);padding:.6rem 1rem;border-radius:12px;text-decoration:none}
.btn-ghost:hover{background:rgba(255,255,255,.06)}
.panel{background:linear-gradient(180deg,var(--surface) 0%,var(--surface-2) 100%);
  border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:16px;box-shadow:var(--shadow);}
.form-control,.form-select{background:#0b1327!important;color:var(--text)!important;
  border:1px solid rgba(255,255,255,.12)!important;}
label{color:var(--muted);}
.btn-primary{background:var(--primary);border:none;border-radius:12px;}
.btn-danger{border:none;border-radius:12px;}
.table{width:100%;border-collapse:separate;border-spacing:0 8px;}
.table thead th{color:var(--muted);font-weight:600;border-bottom:1px solid rgba(255,255,255,.08)}
.table tbody tr{background:linear-gradient(180deg,#0f1833,#0b1327);border:1px solid rgba(255,255,255,.07)}
.table tbody td{padding:10px 12px;}
.small{color:var(--muted);font-size:.9rem}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>🧾 Nueva compra — <?= h($prov['nombre']) ?></h1>
    <div style="display:flex;gap:8px">
      <a class="btn-ghost" href="./proveedores_ver.php?id=<?= (int)$id_proveedor ?>">⬅ Volver</a>
      <a class="btn-ghost" href="./proveedores_listar.php">Módulo</a>
    </div>
  </div>

  <div class="panel" style="margin-top:18px">
    <form method="post" action="./compras_guardar.php" id="formCompra">
      <input type="hidden" name="id_proveedor" value="<?= (int)$id_proveedor ?>">

      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label">Fecha de compra</label>
          <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Número de documento (factura)</label>
          <input type="text" name="num_documento" class="form-control" placeholder="FAC-001 o s/n">
        </div>
        <div class="col-md-4">
          <label class="form-label">Total (C$)</label>
          <input type="number" step="0.01" min="0.01" name="total" class="form-control" id="inputTotal" readonly>
          <div class="small">Se calcula automáticamente por la suma de los subtotales.</div>
        </div>
      </div>

      <h5 class="mt-3">📦 Detalle de productos</h5>

      <!-- Lista de productos -->
      <datalist id="listaProductos">
        <?php foreach($productos as $p): ?>
          <option value="<?= h($p['nombre']) ?>" label="<?= h($p['sku'] ? $p['sku'].' · '.$p['nombre'] : $p['nombre']) ?>"></option>
        <?php endforeach; ?>
      </datalist>

      <div class="table-responsive">
        <table class="table align-middle" id="tablaProductos">
          <thead>
            <tr><th style="min-width:320px">Producto</th><th>Cantidad</th><th>Costo unitario (C$)</th><th>Subtotal</th><th></th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <button type="button" class="btn btn-primary mt-2" id="btnAgregar">Agregar producto</button>

      <div class="text-end mt-3">
        <button type="submit" class="btn btn-primary">Guardar compra</button>
        <a href="./proveedores_ver.php?id=<?= (int)$id_proveedor ?>" class="btn btn-danger">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<script>
// Productos desde PHP (para autocompletar costo y guardar id)
const CATALOGO = <?php echo json_encode($productos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

const tabla = document.querySelector("#tablaProductos tbody");
const btnAgregar = document.querySelector("#btnAgregar");
const inputTotal = document.querySelector("#inputTotal");

// Utilidad: buscar producto por nombre case-insensitive
function findProdByName(name){
  const key = (name||'').trim().toLowerCase();
  if(!key) return null;
  return CATALOGO.find(p => (p.nombre||'').trim().toLowerCase() === key) || null;
}

btnAgregar.addEventListener("click", addRow);
tabla.addEventListener("input", recalc);
tabla.addEventListener("click",(e)=>{
  if(e.target.closest(".btn-del")) { e.target.closest("tr").remove(); recalc(); }
});

addRow(); // agrega una fila inicial

function addRow(){
  const tr=document.createElement("tr");
  tr.innerHTML=`
    <td>
      <input list="listaProductos" name="producto[]" class="form-control input-prod" placeholder="Escribe o selecciona…" required>
      <input type="hidden" name="id_producto[]" class="input-id-prod">
      <div class="small hint-cost d-none"></div>
    </td>
    <td style="max-width:120px">
      <input type="number" name="cantidad[]" class="form-control input-cant" min="1" value="1" required>
    </td>
    <td style="max-width:180px">
      <input type="number" name="costo_unitario[]" class="form-control input-costo" step="0.01" min="0.01" value="0.00" required>
    </td>
    <td class="subtotal text-end">C$ 0.00</td>
    <td><button type="button" class="btn btn-danger btn-sm btn-del">🗑</button></td>
  `;
  tabla.appendChild(tr);

  // cuando selecciona un producto del datalist, autocompleta id y costo
  const inProd  = tr.querySelector(".input-prod");
  const inId    = tr.querySelector(".input-id-prod");
  const inCosto = tr.querySelector(".input-costo");
  const hint    = tr.querySelector(".hint-cost");

  inProd.addEventListener("change", ()=>{
    const p = findProdByName(inProd.value);
    if(p){
      inId.value = p.id_producto;
      if(p.ultimo_costo !== null){
        inCosto.value = Number(p.ultimo_costo).toFixed(2);
        hint.classList.remove("d-none");
        hint.textContent = "Último costo registrado: C$ " + Number(p.ultimo_costo).toFixed(2);
      } else {
        hint.classList.add("d-none");
        hint.textContent = "";
      }
    } else {
      // producto libre (no existe en catálogo)
      inId.value = "";
      hint.classList.add("d-none");
      hint.textContent = "";
    }
    recalc();
  });

  recalc();
}

function recalc(){
  let total = 0;
  tabla.querySelectorAll("tr").forEach(tr=>{
    const c = tr.querySelector('.input-cant')?.valueAsNumber || 0;
    const u = tr.querySelector('.input-costo')?.valueAsNumber || 0;
    const sub = c*u;
    tr.querySelector('.subtotal').textContent = "C$ " + sub.toFixed(2);
    total += sub;
  });
  inputTotal.value = total.toFixed(2);
}

// Validación simple antes de enviar: debe haber al menos 1 ítem
document.querySelector("#formCompra").addEventListener("submit", (e)=>{
  if(tabla.querySelectorAll("tr").length === 0){
    e.preventDefault();
    alert("Agrega al menos un producto a la compra.");
  }
});
</script>
</body>
</html>
