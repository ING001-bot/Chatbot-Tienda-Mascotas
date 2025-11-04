<?php
session_start();
if(empty($_SESSION['usuario_id'])){ header('Location: login.php'); exit; }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Soporte</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header><strong>Soporte</strong> — <a href="perfil.php" style="color:#fff">Mi cuenta</a></header>
<div class="container">
  <div class="card" style="padding:16px">
    <p>¿Necesitas ayuda? Escríbenos a <a href="mailto:soporte@tienda-mascotas.local">soporte@tienda-mascotas.local</a> o cuéntale al chatbot tu problema, te guiamos paso a paso.</p>
  </div>
</div>
<script src="assets/js/chatbot.js"></script>
</body>
</html>
