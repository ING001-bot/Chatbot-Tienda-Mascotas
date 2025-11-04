<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/utils_csrf.php';
if(($_SESSION['rol'] ?? '') !== 'admin'){ http_response_code(403); die('Acceso restringido'); }
$err=''; $ok='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $id = (int)($_POST['id'] ?? 0);
  $nombre = trim($_POST['nombre'] ?? '');
  $descripcion = trim($_POST['descripcion'] ?? '');
  $token = $_POST['csrf'] ?? '';
  if(!csrf_validate($token)){
    $err = 'Token CSRF inválido';
  } elseif($nombre){
    if($id){
      $st = $pdo->prepare("UPDATE categorias SET nombre=?, descripcion=? WHERE id=?");
      $st->execute([$nombre,$descripcion,$id]);
      $ok='Categoría actualizada';
    } else {
      $st = $pdo->prepare("INSERT INTO categorias(nombre,descripcion) VALUES(?,?)");
      $st->execute([$nombre,$descripcion]);
      $ok='Categoría creada';
    }
  } else { $err='Nombre requerido'; }
}
if(isset($_GET['del'])){
  $id = (int)$_GET['del'];
  $pdo->prepare("DELETE FROM categorias WHERE id=?")->execute([$id]);
  $ok='Categoría eliminada';
}
$edit=null;
if(isset($_GET['edit'])){
  $id=(int)$_GET['edit'];
  $st=$pdo->prepare("SELECT * FROM categorias WHERE id=?");
  $st->execute([$id]);
  $edit=$st->fetch();
}
$rows = $pdo->query("SELECT * FROM categorias ORDER BY id DESC")->fetchAll();
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin categorías</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header><strong>Categorías</strong> — <a href="admin.php" style="color:#fff">Panel</a></header>
<div class="container">
  <?php if($ok): ?><div style="background:#dcfce7;border:1px solid #86efac;padding:10px;border-radius:8px;margin:10px 0"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if($err): ?><div style="background:#fee2e2;border:1px solid #fecaca;padding:10px;border-radius:8px;margin:10px 0"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin-top:16px">
    <h3><?= $edit? 'Editar categoría' : 'Nueva categoría' ?></h3>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
      <label>Nombre</label>
      <input name="nombre" value="<?= htmlspecialchars($edit['nombre'] ?? '') ?>" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px">
      <label>Descripción</label>
      <textarea name="descripcion" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px" rows="3"><?= htmlspecialchars($edit['descripcion'] ?? '') ?></textarea>
      <button style="margin-top:10px;padding:10px 12px;border:0;border-radius:8px;background:#0ea5e9;color:#fff;cursor:pointer">Guardar</button>
    </form>
  </div>
  <h3 style="margin-top:20px">Listado</h3>
  <table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb">
    <tr><th>ID</th><th>Nombre</th><th>Descripción</th><th></th></tr>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars($r['nombre']) ?></td>
        <td><?= htmlspecialchars($r['descripcion']) ?></td>
        <td>
          <a href="admin_categorias.php?edit=<?= (int)$r['id'] ?>">Editar</a> |
          <a href="admin_categorias.php?del=<?= (int)$r['id'] ?>" onclick="return confirm('¿Eliminar?')">Eliminar</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<script src="assets/js/chatbot.js"></script>
</body>
</html>
