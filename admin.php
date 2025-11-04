<?php
session_start();
require_once __DIR__ . '/conexion.php';
if(($_SESSION['rol'] ?? '') !== 'admin'){ http_response_code(403); die('Acceso restringido'); }
$users = (int)$pdo->query("SELECT COUNT(*) c FROM usuarios")->fetch()['c'];
$prods = (int)$pdo->query("SELECT COUNT(*) c FROM productos")->fetch()['c'];
$ventas = (float)$pdo->query("SELECT COALESCE(SUM(total),0) t FROM compras WHERE estado='pagado'")->fetch()['t'];
$pend = (int)$pdo->query("SELECT COUNT(*) c FROM compras WHERE estado='pendiente'")->fetch()['c'];
$top = $pdo->query("SELECT p.nombre, SUM(d.cantidad) s FROM detalles_compra d JOIN productos p ON p.id=d.producto_id GROUP BY d.producto_id ORDER BY s DESC LIMIT 5")->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - Tienda Mascotas</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;padding:16px} .k{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px}</style>
</head>
<body>
  <header><strong>Panel de administración</strong> — <a href="index.php" style="color:#fff">Tienda</a></header>
  <div class="kpis">
    <div class="k"><div>Total usuarios</div><h2><?= $users ?></h2></div>
    <div class="k"><div>Total productos</div><h2><?= $prods ?></h2></div>
    <div class="k"><div>Ventas totales</div><h2>S/ <?= number_format($ventas,2) ?></h2></div>
    <div class="k"><div>Órdenes pendientes</div><h2><?= $pend ?></h2></div>
  </div>
  <div class="kpis">
    <div class="k"><a href="admin_usuarios.php">Gestionar usuarios</a></div>
    <div class="k"><a href="admin_categorias.php">Gestionar categorías</a></div>
    <div class="k"><a href="admin_productos.php">Gestionar productos</a></div>
    <div class="k"><a href="admin_compras.php">Ver compras</a></div>
    <div class="k"><a href="admin_reportes.php">Reportes</a></div>
  </div>
  <div class="container">
    <h3>Top productos</h3>
    <ul>
      <?php foreach($top as $t): ?>
        <li><?= htmlspecialchars($t['nombre']) ?> — <?= (int)$t['s'] ?> und.</li>
      <?php endforeach; ?>
    </ul>
  </div>
</body>
</html>
