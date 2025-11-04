<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/utils_csrf.php';

$q = trim($_GET['q'] ?? '');
$cat = (int)($_GET['cat'] ?? 0);
$params = [];
$sql = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.imagen, c.nombre AS categoria FROM productos p LEFT JOIN categorias c ON c.id=p.categoria_id";
$w = [];
if($q !== ''){ $w[] = '(p.nombre LIKE ? OR p.descripcion LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if($cat){ $w[] = 'p.categoria_id = ?'; $params[] = $cat; }
if($w){ $sql .= ' WHERE ' . implode(' AND ', $w); }
$sql .= ' ORDER BY p.id DESC LIMIT 24';
$st = $pdo->prepare($sql); $st->execute($params); $productos = $st->fetchAll();
$cats = $pdo->query("SELECT id,nombre FROM categorias ORDER BY nombre")->fetchAll();
$cartCount = 0; if(!empty($_SESSION['cart'])){ foreach($_SESSION['cart'] as $k=>$v){ $cartCount += (int)$v; } }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tienda Mascotas</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header>
    <div><strong>Tienda de Accesorios para Mascotas</strong></div>
    <nav>
      <a href="cart.php" style="color:#fff;margin-right:14px">Carrito (<?= (int)$cartCount ?>)</a>
      <?php if(!empty($_SESSION['usuario_id'])): ?>
        <a href="admin.php" style="color:#fff;margin-right:10px">Admin</a>
        <a href="logout.php" style="color:#fff">Salir</a>
      <?php else: ?>
        <a href="login.php" style="color:#fff;margin-right:10px">Ingresar</a>
        <a href="register.php" style="color:#fff">Registrarse</a>
      <?php endif; ?>
    </nav>
  </header>

  <div class="container">
    <form method="get" style="display:flex;gap:8px;align-items:center;margin-bottom:12px">
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar productos..." style="flex:1;padding:10px;border:1px solid #cbd5e1;border-radius:8px">
      <select name="cat" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px">
        <option value="0">Todas las categorías</option>
        <?php foreach($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $cat===(int)$c['id']? 'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
      <button style="padding:10px 12px;border:0;border-radius:8px;background:#0ea5e9;color:#fff;cursor:pointer">Buscar</button>
    </form>
    <h2>Nuevos productos</h2>
    <div class="grid">
      <?php foreach($productos as $p): ?>
        <div class="card">
          <img src="<?= htmlspecialchars($p['imagen'] ?: 'https://placehold.co/400x300?text=Mascotas') ?>" alt="">
          <div class="b">
            <h3><a href="product.php?id=<?= (int)$p['id'] ?>" style="text-decoration:none;color:inherit"><?= htmlspecialchars($p['nombre']) ?></a></h3>
            <p><?= htmlspecialchars($p['categoria'] ?: 'Sin categoría') ?></p>
            <div class="price">S/ <?= number_format((float)$p['precio'],2) ?></div>
            <div style="margin-top:8px;display:flex;gap:8px">
              <a href="product.php?id=<?= (int)$p['id'] ?>" style="color:#0ea5e9;text-decoration:none">Ver</a>
              <a href="cart.php?action=add&id=<?= (int)$p['id'] ?>" style="color:#0ea5e9;text-decoration:none">Agregar al carrito</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <script src="assets/js/main.js"></script>
  <script src="assets/js/chatbot.js"></script>
</body>
</html>
