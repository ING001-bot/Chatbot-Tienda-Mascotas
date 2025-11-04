<?php
// Genera hash para Admin123! y asegura el usuario admin por defecto
require_once __DIR__ . '/conexion.php';
header('Content-Type: text/plain; charset=utf-8');
try{
  $email = 'admin@local.test';
  $nombre = 'Administrador';
  $hash = password_hash('123456', PASSWORD_DEFAULT);
  // si no existe, insert; si existe, update
  $pdo->beginTransaction();
  $sel = $pdo->prepare("SELECT id FROM usuarios WHERE email=? LIMIT 1");
  $sel->execute([$email]);
  $row = $sel->fetch();
  if($row){
    $upd = $pdo->prepare("UPDATE usuarios SET password=?, rol='admin' WHERE id=?");
    $upd->execute([$hash, $row['id']]);
    $pdo->commit();
    echo "OK: Admin existente actualizado. ID=".$row['id']."\n";
  } else {
    $ins = $pdo->prepare("INSERT INTO usuarios(nombre,email,password,rol) VALUES(?,?,?, 'admin')");
    $ins->execute([$nombre, $email, $hash]);
    $id = $pdo->lastInsertId();
    $pdo->commit();
    echo "OK: Admin creado. ID=".$id."\n";
  }
  echo "Hash (bcrypt): ".$hash."\n";
  echo "Credenciales -> Usuario: ADMIN | Password: 123456 (alias de ".$email.")\n";
} catch(Exception $e){
  if($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo "ERROR: ".$e->getMessage();
}
