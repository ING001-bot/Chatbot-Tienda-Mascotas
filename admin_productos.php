<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/utils_csrf.php';
if(($_SESSION['rol'] ?? '') !== 'admin'){ http_response_code(403); die('Acceso restringido'); }
$err=''; $ok='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $id = (int)($_POST['id'] ?? 0);
  $nombre = trim($_POST['nombre'] ?? '');
  $categoria_id = (int)($_POST['categoria_id'] ?? 0);
  $precio = (float)($_POST['precio'] ?? 0);
  $stock = (int)($_POST['stock'] ?? 0);
  $descripcion = trim($_POST['descripcion'] ?? '');
  $token = $_POST['csrf'] ?? '';
  if(!csrf_validate($token)){
    $err = 'Token CSRF inválido';
  }
  $imagen = null;
  if(!empty($_FILES['imagen']['name'])){
    $dest = __DIR__ . '/uploads'; if(!is_dir($dest)) mkdir($dest, 0777, true);
    $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
    if(!in_array($ext, ['jpg','jpeg','png'])){ $err='Tipo de imagen no permitido (solo jpg, jpeg, png)'; }
    elseif($_FILES['imagen']['size'] > 2*1024*1024){ $err='Imagen supera 2MB'; }
    else {
      $fn = 'p_'.time().'_'.mt_rand(1000,9999).'.'.$ext; $target = $dest.'/'.$fn;
      if(move_uploaded_file($_FILES['imagen']['tmp_name'], $target)) $imagen = 'uploads/'.$fn;
    }
  }
  if(!$err && $nombre && $precio>=0){
    if($id){
      $st0 = $pdo->prepare("SELECT imagen FROM productos WHERE id=?"); $st0->execute([$id]); $prev=$st0->fetch(); if(!$imagen){ $imagen=$prev['imagen']??null; }
      $st = $pdo->prepare("UPDATE productos SET categoria_id=?, nombre=?, descripcion=?, precio=?, stock=?, imagen=? WHERE id=?");
      $st->execute([$categoria_id ?: null, $nombre, $descripcion, $precio, $stock, $imagen, $id]);
      $ok = 'Producto actualizado';
    } else {
      $st = $pdo->prepare("INSERT INTO productos(categoria_id,nombre,descripcion,precio,stock,imagen) VALUES(?,?,?,?,?,?)");
      $st->execute([$categoria_id ?: null, $nombre, $descripcion, $precio, $stock, $imagen]);
      $ok = 'Producto creado';
    }
  } elseif(!$err) { $err='Nombre y precio son requeridos'; }
}
if(isset($_GET['del'])){ $id=(int)$_GET['del']; $pdo->prepare("DELETE FROM productos WHERE id=?")->execute([$id]); $ok='Producto eliminado'; }
$edit=null; if(isset($_GET['edit'])){ $id=(int)$_GET['edit']; $st=$pdo->prepare("SELECT * FROM productos WHERE id=?"); $st->execute([$id]); $edit=$st->fetch(); }
$cats = $pdo->query("SELECT id,nombre FROM categorias ORDER BY nombre")->fetchAll();
$rows = $pdo->query("SELECT p.id,p.nombre,p.precio,p.stock,c.nombre AS categoria FROM productos p LEFT JOIN categorias c ON c.id=p.categoria_id ORDER BY p.id DESC")->fetchAll();
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin productos</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header><strong>Productos</strong> — <a href="admin.php" style="color:#fff">Panel</a></header>
<div class="container">
  <?php if($ok): ?><div style="background:#dcfce7;border:1px solid #86efac;padding:10px;border-radius:8px;margin:10px 0"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if($err): ?><div style="background:#fee2e2;border:1px solid #fecaca;padding:10px;border-radius:8px;margin:10px 0"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin-top:16px">
    <h3><?= $edit? 'Editar producto' : 'Nuevo producto' ?></h3>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
      <label>Nombre</label>
      <input name="nombre" value="<?= htmlspecialchars($edit['nombre'] ?? '') ?>" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px">
      <label>Categoría</label>
      <select name="categoria_id" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px">
        <option value="">Sin categoría</option>
        <?php foreach($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= !empty($edit) && (int)$edit['categoria_id']===(int)$c['id']? 'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
      <label>Precio</label>
      <input type="number" step="0.01" name="precio" value="<?= htmlspecialchars((string)($edit['precio'] ?? '')) ?>" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px">
      <label>Stock</label>
      <input type="number" name="stock" value="<?= htmlspecialchars((string)($edit['stock'] ?? '0')) ?>" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px">
      <label>Descripción</label>
      <textarea name="descripcion" rows="4" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px"><?= htmlspecialchars($edit['descripcion'] ?? '') ?></textarea>
      <label>Imagen</label>
      <input type="file" name="imagen" accept="image/*">
      <?php if(!empty($edit['imagen'])): ?><div><img src="<?= htmlspecialchars($edit['imagen']) ?>" alt="" style="max-width:200px;margin-top:8px"></div><?php endif; ?>
      <button style="margin-top:10px;padding:10px 12px;border:0;border-radius:8px;background:#0ea5e9;color:#fff;cursor:pointer">Guardar</button>
    </form>
  </div>
  <h3 style="margin-top:20px">Listado</h3>
  <table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb">
    <tr><th>ID</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Stock</th><th></th></tr>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars($r['nombre']) ?></td>
        <td><?= htmlspecialchars($r['categoria'] ?: '—') ?></td>
        <td>S/ <?= number_format((float)$r['precio'],2) ?></td>
        <td><?= (int)$r['stock'] ?></td>
        <td>
          <a href="admin_productos.php?edit=<?= (int)$r['id'] ?>">Editar</a> |
          <a href="admin_productos.php?del=<?= (int)$r['id'] ?>" onclick="return confirm('¿Eliminar producto?')">Eliminar</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
</body>
</html>
