<?php
session_start();
require_once __DIR__ . '/conexion.php';
if(empty($_SESSION['usuario_id'])){ header('Location: login.php'); exit; }
$uid = (int)$_SESSION['usuario_id'];
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM compras WHERE id=? AND (usuario_id=? OR ? IN (SELECT rol FROM usuarios WHERE id=? AND rol='admin')) LIMIT 1");
$st->execute([$id, $uid, 'admin', $uid]);
$c = $st->fetch();
if(!$c){ die('Compra no encontrada'); }
$it = $pdo->prepare("SELECT d.cantidad,d.precio,p.nombre FROM detalles_compra d JOIN productos p ON p.id=d.producto_id WHERE d.compra_id=?");
$it->execute([$id]);
$items = $it->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Compra #<?= (int)$c['id'] ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header><strong>Compra #<?= (int)$c['id'] ?></strong> — <a href="historial.php" style="color:#fff">Historial</a></header>
<div class="container">
  <div class="card" style="padding:16px">
    <div><strong>Fecha:</strong> <?= htmlspecialchars($c['fecha']) ?> — <strong>Estado:</strong> <?= htmlspecialchars($c['estado']) ?></div>
    <table style="width:100%;border-collapse:collapse;margin-top:10px">
      <tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr>
      <?php $sum=0; foreach($items as $it): $sub=$it['cantidad']*$it['precio']; $sum+=$sub; ?>
        <tr>
          <td><?= htmlspecialchars($it['nombre']) ?></td>
          <td><?= (int)$it['cantidad'] ?></td>
          <td>S/ <?= number_format((float)$it['precio'],2) ?></td>
          <td>S/ <?= number_format((float)$sub,2) ?></td>
        </tr>
      <?php endforeach; ?>
      <tr><td colspan="3" style="text-align:right"><strong>Total</strong></td><td><strong>S/ <?= number_format((float)$sum,2) ?></strong></td></tr>
    </table>
  </div>
</div>
<script src="assets/js/chatbot.js"></script>
</body>
</html>
