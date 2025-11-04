<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils_csrf.php';
$hasGoogle = !empty($GOOGLE_CLIENT_ID) && (strpos($GOOGLE_CLIENT_ID, 'apps.googleusercontent.com') !== false);
$err = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $identifierRaw = trim($_POST['email'] ?? '');
  $email = filter_var($identifierRaw, FILTER_VALIDATE_EMAIL);
  $pass = $_POST['password'] ?? '';
  $token = $_POST['csrf'] ?? '';
  // Permitir alias ADMIN para el usuario administrador
  $isAdminAlias = (strcasecmp($identifierRaw, 'ADMIN') === 0);
  if($isAdminAlias){ $email = 'admin@local.test'; }
  if((!$email && !$isAdminAlias && $identifierRaw==='') || !$pass || !csrf_validate($token)){
    $err = 'Datos inválidos o token CSRF inválido';
  } else {
    if($email || $isAdminAlias){
      $st = $pdo->prepare("SELECT id,nombre,email,password,rol FROM usuarios WHERE email = ? LIMIT 1");
      $st->execute([$email]);
    } else {
      // Login por nombre de usuario (username)
      $st = $pdo->prepare("SELECT id,nombre,email,password,rol FROM usuarios WHERE nombre = ? LIMIT 1");
      $st->execute([$identifierRaw]);
    }
    $u = $st->fetch();
    if($u && !empty($u['password']) && password_verify($pass, $u['password'])){
      session_regenerate_id(true);
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
  <?php if($hasGoogle): ?><script src="https://accounts.google.com/gsi/client" async defer></script><?php endif; ?>
</head>
<body>
  <div class="box">
    <h2>Iniciar sesión</h2>
    <?php if($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <label>Email o Usuario</label>
      <input type="text" name="email" placeholder="ej. usuario@correo.com o ADMIN" required>
      <label>Contraseña</label>
      <input type="password" name="password" required>
      <button>Ingresar</button>
    </form>
    <p style="margin-top:12px;font-size:14px">¿No tienes cuenta? <a href="register.php">Regístrate</a></p>
    <hr>
    <div>
      <p>Google Sign-In</p>
      <?php if($hasGoogle): ?>
        <div id="g_id_onload"
             data-client_id="<?= htmlspecialchars($GOOGLE_CLIENT_ID) ?>"
             data-callback="handleGoogleCredential"
             data-auto_prompt="false">
        </div>
        <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline" data-text="sign_in_with" data-shape="rectangular" data-logo_alignment="left"></div>
      <?php else: ?>
        <div style="background:#fff3cd;border:1px solid #ffeeba;padding:10px;border-radius:8px">Configura tu Google Client ID en <code>config.php</code> para habilitar el inicio con Google.</div>
      <?php endif; ?>
    </div>
  </div>
  <?php if($hasGoogle): ?>
  <script>
  async function handleGoogleCredential(response){
    try{
      const id_token = response.credential;
      const res = await fetch('oauth_google.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id_token }) });
      const data = await res.json();
      if(data.status==='ok'){ window.location = 'index.php'; }
      else { alert(data.message || 'Error OAuth'); }
    }catch(e){ alert('Error de red en OAuth'); }
  }
  </script>
  <?php endif; ?>
</body>
</html>
