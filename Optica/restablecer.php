<?php
// restablecer.php — Valida token, permite cambiar contraseña y muestra mensajes bonitos
require_once __DIR__ . '/conexion.php';
header('Content-Type: text/html; charset=utf-8');
$db = conectarDB();

/* plantilla mensaje */
function render_msg($type,$title,$bodyHtml='',$linkHref=null,$linkText=null){
  $cls=['success'=>'msg-success','error'=>'msg-error','info'=>'msg-info'][$type]??'msg-info'; ?>
  <!DOCTYPE html><html lang="es"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="restableces.css?v=1">
    <style>
      body{font-family:Segoe UI,Tahoma,Arial,sans-serif;margin:0;background:#0b1020}
      .wrap{min-height:100vh;display:grid;place-items:center;padding:32px}
      .msg{max-width:720px;background:#fff;border-radius:14px;padding:22px 22px;box-shadow:0 10px 26px rgba(0,0,0,.18);line-height:1.6}
      .msg p{margin:0 0 10px}
      .msg a{color:#2563eb;font-weight:700;text-decoration:none}
      .msg a:hover{text-decoration:underline}
      .msg-success{background:#e6f9f0;border-left:8px solid #10b981;color:#065f46}
      .msg-error{background:#fee2e2;border-left:8px solid #dc2626;color:#7f1d1d}
    </style>
  </head><body><div class="wrap"><div class="msg <?php echo $cls; ?>">
    <p><?php echo $title; ?></p>
    <?php if($bodyHtml) echo $bodyHtml; ?>
    <?php if ($linkHref && $linkText): ?><p><a href="<?php echo htmlspecialchars($linkHref); ?>"><?php echo htmlspecialchars($linkText); ?></a></p><?php endif; ?>
  </div></div></body></html><?php exit;
}

/* helper error */
function fail($msg="Enlace inválido o caducado."){ http_response_code(400); render_msg('error',"❌ $msg",'','recuperar.php','Volver a recuperar'); }

$tokenPlain = $_SERVER['REQUEST_METHOD']==='POST' ? ($_POST['token'] ?? '') : ($_GET['token'] ?? '');
if (!$tokenPlain || !preg_match('/^[a-f0-9]{64}$/i', $tokenPlain)) { fail(); }
$calcHash = hash('sha256', $tokenPlain);

/* GET: mostrar formulario si token vigente */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $stmt = $db->prepare("SELECT id FROM usuarios WHERE reset_expira > NOW() AND reset_token = ? LIMIT 1");
  $stmt->bind_param("s",$calcHash); $stmt->execute();
  if (!$stmt->get_result()->fetch_assoc()) { fail(); }
  ?>
  <!DOCTYPE html><html lang="es"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear nueva contraseña</title>
    <link rel="stylesheet" href="restableces.css?v=1">
    <style>
      .hero-bg{min-height:100vh;display:grid;place-items:center;padding:24px;background:#0b1020}
      .card{background:rgba(74, 72, 72, 0.9);backdrop-filter:blur(7px);border-radius:16px;padding:22px 20px;box-shadow:0 8px 24px rgba(0,0,0,.15);max-width:520px;width:100%}
      label{display:block;margin:12px 0 6px;font-weight:600}
      input[type="password"]{width:100%;padding:12px 8px;border:1px solid #dfe4ea;border-radius:12px;background:#fafbff;outline:none}
      .btn{display:inline-block;padding:12px 18px;border:none;border-radius:12px;font-weight:800;cursor:pointer;background:linear-gradient(135deg,#4e73df,#224abe);color:#fff}
      .actions{margin-top:16px;display:flex;gap:12px;align-items:center}
    </style>
  </head><body class="hero-bg">
    <form method="post" autocomplete="off" class="card">
      <h2>Crear nueva contraseña</h2>
      <p class="sub">Mínimo 8 caracteres, con al menos 1 letra y 1 número.</p>
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($tokenPlain, ENT_QUOTES, 'UTF-8'); ?>">
      <label for="p1">Nueva contraseña</label>
      <input id="p1" type="password" name="pass1" required minlength="8"
             pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" title="Mínimo 8 caracteres, al menos 1 letra y 1 número">
      <label for="p2">Repite la contraseña</label>
      <input id="p2" type="password" name="pass2" required minlength="8">
      <div class="actions"><button type="submit" class="btn">Guardar</button></div>
    </form>
  </body></html>
  <?php exit;
}

/* POST: validar y actualizar */
$p1 = $_POST['pass1'] ?? ''; $p2 = $_POST['pass2'] ?? '';
if ($p1 !== $p2 || strlen($p1) < 8 || !preg_match('/(?=.*[A-Za-z])(?=.*\d)/',$p1)) {
  fail("Las contraseñas no coinciden o no cumplen la política.");
}
$stmt = $db->prepare("SELECT id FROM usuarios WHERE reset_expira > NOW() AND reset_token = ? LIMIT 1");
$stmt->bind_param("s",$calcHash); $stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
if (!$u) { fail(); }

$hash = password_hash($p1, PASSWORD_DEFAULT);
$upd  = $db->prepare("UPDATE usuarios SET `contraseña`=?, reset_token=NULL, reset_expira=NULL WHERE id=?");
$upd->bind_param("si",$hash,$u['id']); $upd->execute();

/* éxito */
render_msg('success','✅ ¡Contraseña actualizada con éxito!','', 'login.php','Inicia sesión aquí');
