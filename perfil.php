<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/utils_csrf.php';
if(empty($_SESSION['usuario_id'])){ header('Location: login.php'); exit; }
$uid = (int)$_SESSION['usuario_id'];
$ok=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $token = $_POST['csrf'] ?? '';
  $nombre = trim($_POST['nombre'] ?? '');
  $telefono = trim($_POST['telefono'] ?? '');
  $direccion = trim($_POST['direccion'] ?? '');
  if(!csrf_validate($token)){
    $err = 'Token CSRF inválido';
  } elseif(!$nombre){
    $err = 'El nombre es requerido';
  } else {
    $st = $pdo->prepare("UPDATE usuarios SET nombre=?, telefono=?, direccion=? WHERE id=?");
    $st->execute([$nombre, $telefono ?: null, $direccion ?: null, $uid]);
    $ok = 'Perfil actualizado correctamente';
  }
}
$st = $pdo->prepare("SELECT nombre,email,telefono,direccion,rol FROM usuarios WHERE id=? LIMIT 1");
$st->execute([$uid]);
$u = $st->fetch();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mi cuenta</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header><strong>Mi cuenta</strong> — <a href="index.php" style="color:#fff">Inicio</a></header>
<div class="container">
  <?php if($ok): ?><div style="background:#dcfce7;border:1px solid #86efac;padding:10px;border-radius:8px;margin:10px 0"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if($err): ?><div style="background:#fee2e2;border:1px solid #fecaca;padding:10px;border-radius:8px;margin:10px 0"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <div class="card" style="padding:16px">
    <h3>Datos de perfil</h3>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <label>Nombre</label>
      <input name="nombre" value="<?= htmlspecialchars($u['nombre'] ?? '') ?>" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px">
      <label style="margin-top:10px">Email</label>
      <input value="<?= htmlspecialchars($u['email'] ?? '') ?>" disabled style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;background:#f8fafc;color:#64748b">
      <label style="margin-top:10px">Teléfono</label>
      <input name="telefono" value="<?= htmlspecialchars($u['telefono'] ?? '') ?>" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px">
      <label style="margin-top:10px">Dirección</label>
      <textarea name="direccion" rows="3" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px"><?= htmlspecialchars($u['direccion'] ?? '') ?></textarea>
      <button class="btn" style="margin-top:12px">Guardar</button>
    </form>
  </div>
  <div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap">
    <a class="btn" href="historial.php">Historial de compras</a>
    <?php if(($u['rol'] ?? '')==='admin'): ?><a class="btn" href="admin.php">Panel admin</a><?php endif; ?>
  </div>
</div>
<script src="assets/js/chatbot.js"></script>
</body>
</html>
