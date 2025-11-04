<?php
session_start();
require_once __DIR__ . '/conexion.php';
if(($_SESSION['rol'] ?? '') !== 'admin'){ http_response_code(403); die('Acceso restringido'); }
$rows = $pdo->query("SELECT c.id,u.nombre as cliente,c.total,c.estado,c.fecha FROM compras c LEFT JOIN usuarios u ON u.id=c.usuario_id ORDER BY c.id DESC")->fetchAll();
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin compras</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header><strong>Compras</strong> — <a href="admin.php" style="color:#fff">Panel</a></header>
<div class="container">
  <table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb">
    <tr><th>ID</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars($r['cliente'] ?: '—') ?></td>
        <td>S/ <?= number_format((float)$r['total'],2) ?></td>
        <td><?= htmlspecialchars($r['estado']) ?></td>
        <td><?= htmlspecialchars($r['fecha']) ?></td>
        <td>
          <a href="generar_boleta.php?compra_id=<?= (int)$r['id'] ?>" target="_blank">PDF</a> |
          <a href="send_mail.php?compra_id=<?= (int)$r['id'] ?>">Enviar</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
</body>
</html>
