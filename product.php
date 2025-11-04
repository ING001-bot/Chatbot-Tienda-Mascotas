<?php
session_start();
require_once __DIR__ . '/conexion.php';
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT p.*, c.nombre AS categoria FROM productos p LEFT JOIN categorias c ON c.id=p.categoria_id WHERE p.id=? LIMIT 1");
$st->execute([$id]);
$p = $st->fetch();
if(!$p){ die('Producto no encontrado'); }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($p['nombre']) ?> - Tienda</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>.prod{display:grid;grid-template-columns: 1fr 1fr;gap:24px}.prod img{width:100%;height:360px;object-fit:cover;background:#f1f5f9;border-radius:10px}.card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px}.price{font-weight:700;font-size:20px;margin-top:8px} a.btn{display:inline-block;margin-top:12px;padding:10px 12px;border-radius:8px;background:#0ea5e9;color:#fff;text-decoration:none}</style>
</head>
<body>
  <header><a href="index.php" style="color:#fff;text-decoration:none">← Volver</a></header>
  <div class="container">
    <div class="prod">
      <div><img src="<?= htmlspecialchars($p['imagen'] ?: 'https://placehold.co/800x600?text=Mascotas') ?>" alt=""></div>
      <div class="card">
        <h2><?= htmlspecialchars($p['nombre']) ?></h2>
        <div><?= htmlspecialchars($p['categoria'] ?: 'Sin categoría') ?></div>
        <div class="price">S/ <?= number_format((float)$p['precio'],2) ?></div>
        <p style="margin-top:10px;color:#334155;white-space:pre-wrap"><?= htmlspecialchars($p['descripcion']) ?></p>
        <a class="btn" href="cart.php?action=add&id=<?= (int)$p['id'] ?>">Agregar al carrito</a>
      </div>
    </div>
  </div>
  <script src="assets/js/chatbot.js"></script>
</body>
</html>
