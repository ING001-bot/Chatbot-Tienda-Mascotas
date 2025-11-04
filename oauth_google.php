<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/config.php';
$input = json_decode(file_get_contents('php://input'), true);
$id_token = $input['id_token'] ?? '';
if(!$id_token){ echo json_encode(['status'=>'error','message'=>'id_token requerido']); exit; }
// Validación simple via tokeninfo (demo). Para producción usa la librería oficial Google PHP Client.
$verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($id_token);
$resp = @file_get_contents($verifyUrl);
$data = $resp? json_decode($resp,true) : null;
if(!$data || empty($data['aud']) || $data['aud'] !== $GOOGLE_CLIENT_ID){ echo json_encode(['status'=>'error','message'=>'Token inválido']); exit; }
$email = $data['email'] ?? '';
$name = $data['name'] ?? 'Usuario Google';
$sub = $data['sub'] ?? '';
if(!$email || !$sub){ echo json_encode(['status'=>'error','message'=>'Datos Google incompletos']); exit; }
// Crear o actualizar usuario
$st = $pdo->prepare("SELECT id,nombre,rol FROM usuarios WHERE email=? LIMIT 1");
$st->execute([$email]);
$u = $st->fetch();
if($u){
  $pdo->prepare("UPDATE usuarios SET google_id=? WHERE id=?")->execute([$sub, $u['id']]);
  $_SESSION['usuario_id'] = $u['id'];
  $_SESSION['nombre'] = $u['nombre'];
  $_SESSION['rol'] = $u['rol'];
} else {
  $pdo->prepare("INSERT INTO usuarios(nombre,email,google_id) VALUES(?,?,?)")->execute([$name,$email,$sub]);
  $_SESSION['usuario_id'] = $pdo->lastInsertId();
  $_SESSION['nombre'] = $name;
  $_SESSION['rol'] = 'cliente';
}
echo json_encode(['status'=>'ok']);
