<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/utils_csrf.php';
$err = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $nombre = trim($_POST['nombre'] ?? '');
  $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
  $pass = $_POST['password'] ?? '';
  $token = $_POST['csrf'] ?? '';
  if(!$email || !$pass || !$nombre || !csrf_validate($token)){
    $err = 'Datos inválidos o token CSRF inválido';
  } else {
    try{
      // Validaciones de unicidad
      $ch = $pdo->prepare("SELECT COUNT(*) c FROM usuarios WHERE email=? OR nombre=?");
      $ch->execute([$email, $nombre]);
      $exists = (int)$ch->fetch()['c'] > 0;
      if($exists){
        $err = 'El nombre de usuario o email ya está en uso';
      } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $st = $pdo->prepare("INSERT INTO usuarios(nombre,email,password) VALUES(?,?,?)");
        $st->execute([$nombre,$email,$hash]);
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $pdo->lastInsertId();
        $_SESSION['nombre'] = $nombre;
        $_SESSION['rol'] = 'cliente';
        $_SESSION['flash_success'] = '¡Cuenta creada con éxito! Bienvenido/a, ya puedes comprar.';
        header('Location: index.php');
        exit;
      }
    }catch(PDOException $e){ $err = 'No se pudo registrar'; }
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Registro</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>.box{max-width:420px;margin:60px auto;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:20px} label{display:block;margin:10px 0 6px} input{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px} button{margin-top:14px;padding:10px 12px;border:0;border-radius:8px;background:#0ea5e9;color:#fff;cursor:pointer;width:100%} .err{color:#b91c1c;margin:8px 0}</style>
</head>
<body>
  <div class="box">
    <h2>Crear cuenta</h2>
    <?php if($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <label>Nombre (será tu usuario)</label>
      <input type="text" name="nombre" required>
      <label>Email</label>
      <input type="email" name="email" required>
      <label>Contraseña</label>
      <input type="password" name="password" required>
      <button>Registrarse</button>
    </form>
    <p style="margin-top:12px;font-size:14px">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
  </div>
</body>
</html>
