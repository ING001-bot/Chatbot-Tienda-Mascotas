<?php
session_start();
require_once __DIR__ . '/conexion.php';
if(($_SESSION['rol'] ?? '') !== 'admin'){ http_response_code(403); die('Acceso restringido'); }
$ok=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $id = (int)($_POST['id'] ?? 0);
  $rol = $_POST['rol'] ?? 'cliente';
  if(in_array($rol, ['cliente','admin'], true) && $id){
    $st = $pdo->prepare("UPDATE usuarios SET rol=? WHERE id=?");
    $st->execute([$rol, $id]);
    $ok = 'Rol actualizado';
  }
}
if(isset($_GET['del'])){
  $id = (int)$_GET['del'];
  $pdo->prepare("DELETE FROM usuarios WHERE id=?")->execute([$id]);
  $ok = 'Usuario eliminado';
}
$rows = $pdo->query("SELECT id,nombre,email,rol,created_at FROM usuarios ORDER BY id DESC")->fetchAll();
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin usuarios</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header><strong>Usuarios</strong> — <a href="admin.php" style="color:#fff">Panel</a></header>
<div class="container">
  <?php if($ok): ?><div style="background:#dcfce7;border:1px solid #86efac;padding:10px;border-radius:8px;margin:10px 0"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if($err): ?><div style="background:#fee2e2;border:1px solid #fecaca;padding:10px;border-radius:8px;margin:10px 0"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb">
    <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Creado</th><th></th></tr>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars($r['nombre']) ?></td>
        <td><?= htmlspecialchars($r['email']) ?></td>
        <td>
          <form method="post" style="display:flex;gap:6px;align-items:center">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <select name="rol">
              <option value="cliente" <?= $r['rol']==='cliente'?'selected':'' ?>>cliente</option>
              <option value="admin" <?= $r['rol']==='admin'?'selected':'' ?>>admin</option>
            </select>
            <button style="padding:6px 10px;border:0;border-radius:6px;background:#0ea5e9;color:#fff;cursor:pointer">Guardar</button>
          </form>
        </td>
        <td><?= htmlspecialchars($r['created_at']) ?></td>
        <td><a href="admin_usuarios.php?del=<?= (int)$r['id'] ?>" onclick="return confirm('¿Eliminar usuario?')">Eliminar</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
</body>
</html>
