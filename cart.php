<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/utils_csrf.php';
if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
$action = $_GET['action'] ?? '';
if($action==='add'){
  $id = (int)($_GET['id'] ?? 0);
  if($id){ $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1; }
  header('Location: cart.php'); exit;
}
if($action==='remove'){
  $id = (int)($_GET['id'] ?? 0);
  unset($_SESSION['cart'][$id]);
  header('Location: cart.php'); exit;
}
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update']) && csrf_validate($_POST['csrf'] ?? '')){
  foreach(($_POST['qty'] ?? []) as $id=>$q){
    $id = (int)$id; $q = max(0, min(999, (int)$q));
    if($q===0) unset($_SESSION['cart'][$id]); else $_SESSION['cart'][$id] = $q;
  }
}
$ids = array_keys($_SESSION['cart']);
$items = [];$total=0;
if($ids){
  $in = implode(',', array_fill(0,count($ids),'?'));
  $st = $pdo->prepare("SELECT id,nombre,precio,imagen FROM productos WHERE id IN ($in)");
  $st->execute($ids);
  $rows = $st->fetchAll();
  foreach($rows as $r){
    $qty = $_SESSION['cart'][$r['id']];
    $r['qty'] = $qty;
    $r['sub'] = $qty * (float)$r['precio'];
    $items[] = $r; $total += $r['sub'];
  }
}
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Carrito</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb} th,td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left} .actions a{color:#0ea5e9;text-decoration:none}</style>
</head>
<body>
<header><a href="index.php" style="color:#fff;text-decoration:none">← Tienda</a></header>
<div class="container">
  <h2>Carrito</h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <table>
      <tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th><th></th></tr>
      <?php foreach($items as $it): ?>
        <tr>
          <td><?= htmlspecialchars($it['nombre']) ?></td>
          <td>S/ <?= number_format((float)$it['precio'],2) ?></td>
          <td><input name="qty[<?= (int)$it['id'] ?>]" value="<?= (int)$it['qty'] ?>" type="number" min="0" max="999" style="width:70px"></td>
          <td>S/ <?= number_format((float)$it['sub'],2) ?></td>
          <td class="actions"><a href="cart.php?action=remove&id=<?= (int)$it['id'] ?>">Quitar</a></td>
        </tr>
      <?php endforeach; ?>
      <tr><td colspan="3" style="text-align:right"><strong>Total</strong></td><td colspan="2"><strong>S/ <?= number_format((float)$total,2) ?></strong></td></tr>
    </table>
    <div style="margin-top:12px">
      <button name="update" value="1" style="padding:8px 10px;border:0;border-radius:8px;background:#0ea5e9;color:#fff;cursor:pointer">Actualizar cantidades</button>
      <?php if($total>0): ?>
        <a href="checkout.php" style="margin-left:8px">Ir a pagar</a>
      <?php endif; ?>
    </div>
  </form>
  <?php if(empty($_SESSION['usuario_id'])): ?>
  <div style="margin-top:16px;background:#fff3cd;border:1px solid #ffeeba;padding:10px;border-radius:8px">Para comprar debes iniciar sesión. Puedes registrarte o usar el chatbot para guiarte.</div>
  <?php endif; ?>
</div>
<script src="assets/js/chatbot.js"></script>
</body>
</html>
