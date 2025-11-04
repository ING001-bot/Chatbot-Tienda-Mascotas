<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/utils_csrf.php';
$err = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
  $pass = $_POST['password'] ?? '';
  $token = $_POST['csrf'] ?? '';
  if(!$email || !$pass || !csrf_validate($token)){
    $err = 'Datos inválidos o token CSRF inválido';
  } else {
    $st = $pdo->prepare("SELECT id,nombre,email,password,rol FROM usuarios WHERE email = ? LIMIT 1");
    $st->execute([$email]);
    $u = $st->fetch();
    if($u && !empty($u['password']) && password_verify($pass, $u['password'])){
      $_SESSION['usuario_id'] = $u['id'];
      $_SESSION['nombre'] = $u['nombre'];
      $_SESSION['rol'] = $u['rol'];
      header('Location: index.php');
      exit;
    } else {
      $err = 'Credenciales inválidas';
    }
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ingresar</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>.box{max-width:380px;margin:60px auto;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:20px} label{display:block;margin:10px 0 6px} input{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px} button{margin-top:14px;padding:10px 12px;border:0;border-radius:8px;background:#0ea5e9;color:#fff;cursor:pointer;width:100%} .err{color:#b91c1c;margin:8px 0}</style>
</head>
<body>
  <div class="box">
    <h2>Iniciar sesión</h2>
    <?php if($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <label>Email</label>
      <input type="email" name="email" required>
      <label>Contraseña</label>
      <input type="password" name="password" required>
      <button>Ingresar</button>
    </form>
    <p style="margin-top:12px;font-size:14px">¿No tienes cuenta? <a href="register.php">Regístrate</a></p>
    <hr>
    <div>
      <p>Google Sign-In</p>
      <button id="google-login" style="width:100%">Ingresar con Google</button>
    </div>
  </div>
  <script>
  document.getElementById('google-login').onclick = async function(){
    const id_token = prompt('Pega aquí el id_token de Google (demo)');
    if(!id_token) return;
    const res = await fetch('oauth_google.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id_token})});
    const data = await res.json();
    if(data.status==='ok'){ window.location='index.php'; } else { alert(data.message||'Error OAuth'); }
  };
  </script>
</body>
</html>
