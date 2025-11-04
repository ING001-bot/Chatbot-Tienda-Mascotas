<?php
session_start();
require_once __DIR__ . '/conexion.php';
if(empty($_SESSION['usuario_id'])){ header('Location: login.php'); exit; }
$uid = (int)$_SESSION['usuario_id'];
$rows = $pdo->prepare("SELECT id,total,estado,fecha FROM compras WHERE usuario_id=? ORDER BY id DESC");
$rows->execute([$uid]);
$list = $rows->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Historial de compras</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header><strong>Historial de compras</strong> — <a href="perfil.php" style="color:#fff">Mi cuenta</a></header>
<div class="container">
  <div class="card" style="padding:16px">
    <table style="width:100%;border-collapse:collapse">
      <tr><th>ID</th><th>Fecha</th><th>Estado</th><th>Total</th><th></th></tr>
      <?php foreach($list as $c): ?>
        <tr>
          <td><?= (int)$c['id'] ?></td>
          <td><?= htmlspecialchars($c['fecha']) ?></td>
          <td><?= htmlspecialchars($c['estado']) ?></td>
          <td>S/ <?= number_format((float)$c['total'],2) ?></td>
          <td><a href="compra.php?id=<?= (int)$c['id'] ?>">Ver detalle</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if(!$list): ?><tr><td colspan="5" style="padding:10px;color:#64748b">Aún no tienes compras.</td></tr><?php endif; ?>
    </table>
  </div>
</div>
<script src="assets/js/chatbot.js"></script>
</body>
</html>
