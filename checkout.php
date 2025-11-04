<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/utils_csrf.php';
if(empty($_SESSION['usuario_id'])){ header('Location: login.php'); exit; }
if(empty($_SESSION['cart'])){ header('Location: cart.php'); exit; }

$ids = array_keys($_SESSION['cart']);
$in = implode(',', array_fill(0,count($ids),'?'));
$st = $pdo->prepare("SELECT id,precio FROM productos WHERE id IN ($in)");
$st->execute($ids); $rows = $st->fetchAll();
$precios = []; foreach($rows as $r){ $precios[$r['id']] = (float)$r['precio']; }
$total = 0; foreach($_SESSION['cart'] as $id=>$q){ $total += $q * ($precios[$id] ?? 0); }

if($_SERVER['REQUEST_METHOD']==='POST' && csrf_validate($_POST['csrf'] ?? '')){
  $pdo->beginTransaction();
  try{
    $st = $pdo->prepare("INSERT INTO compras(usuario_id,total,estado) VALUES(?,?, 'pagado')");
    $st->execute([$_SESSION['usuario_id'], $total]);
    $compra_id = $pdo->lastInsertId();
    $ins = $pdo->prepare("INSERT INTO detalles_compra(compra_id,producto_id,cantidad,precio_unitario) VALUES(?,?,?,?)");
    foreach($_SESSION['cart'] as $id=>$q){ $ins->execute([$compra_id,$id,$q,$precios[$id] ?? 0]); }
    $pdo->commit();
    $_SESSION['cart'] = [];
    header('Location: compras.php?ok=1&id='.(int)$compra_id);
    exit;
  }catch(Exception $e){ $pdo->rollBack(); die('Error en compra'); }
}
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Checkout</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header><a href="cart.php" style="color:#fff;text-decoration:none">← Volver</a></header>
<div class="container">
  <h2>Confirmación de compra</h2>
  <p>Total a pagar: <strong>S/ <?= number_format((float)$total,2) ?></strong></p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <button style="padding:10px 12px;border:0;border-radius:8px;background:#0ea5e9;color:#fff;cursor:pointer">Confirmar</button>
  </form>
</div>
<script src="assets/js/chatbot.js"></script>
</body>
</html>
