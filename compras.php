<?php
session_start();
require_once __DIR__ . '/conexion.php';
if(empty($_SESSION['usuario_id'])){ header('Location: login.php'); exit; }
$u = (int)$_SESSION['usuario_id'];
$compras = $pdo->prepare("SELECT * FROM compras WHERE usuario_id = ? ORDER BY fecha DESC");
$compras->execute([$u]);
$compras = $compras->fetchAll();
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mis compras</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header><a href="index.php" style="color:#fff;text-decoration:none">← Tienda</a></header>
<div class="container">
  <h2>Mis compras</h2>
  <?php if(isset($_GET['ok'])): ?><div style="background:#dcfce7;border:1px solid #86efac;padding:10px;border-radius:8px;margin-bottom:10px">Compra realizada correctamente.</div><?php endif; ?>
  <table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb"><tr><th>ID</th><th>Total</th><th>Estado</th><th>Fecha</th><th>Boleta</th></tr>
    <?php foreach($compras as $c): ?>
      <tr>
        <td>#<?= (int)$c['id'] ?></td>
        <td>S/ <?= number_format((float)$c['total'],2) ?></td>
        <td><?= htmlspecialchars($c['estado']) ?></td>
        <td><?= htmlspecialchars($c['fecha']) ?></td>
        <td>
          <a href="generar_boleta.php?compra_id=<?= (int)$c['id'] ?>" target="_blank">Generar/Ver PDF</a> |
          <a href="send_mail.php?compra_id=<?= (int)$c['id'] ?>">Enviar por correo</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<script src="assets/js/chatbot.js"></script>
</body>
</html>
