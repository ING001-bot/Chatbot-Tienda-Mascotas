<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/conexion.php';
$usuarioId = $_SESSION['usuario_id'] ?? null;
$session = session_id();
$usuarioNombre = $_SESSION['nombre'] ?? 'Invitado';
$inputRaw = file_get_contents('php://input');
$input = json_decode($inputRaw, true);
$text = $input['mensaje'] ?? ($_GET['mensaje'] ?? '');
$usuario = $input['usuario'] ?? ($_GET['usuario'] ?? $usuarioNombre);
$apiUrl = 'http://127.0.0.1:5000/chatbot?mensaje=' . urlencode($text) . '&usuario=' . urlencode($usuario) . '&session_id=' . urlencode($session) . '&user_id=' . urlencode((string)$usuarioId);
$ctx = stream_context_create(['http' => ['timeout' => 4]]);
$response = @file_get_contents($apiUrl, false, $ctx);
if ($response === false) {
  $fallback = [ 'respuesta' => 'El servicio de IA no está disponible por el momento. Inténtalo más tarde.', 'sentimiento' => 'neutral', 'usuario_detectado' => null, 'action_sugerida'=>null ];
  echo json_encode($fallback);
  try{ $st = $pdo->prepare("INSERT INTO chatbot_logs(usuario_id, session_id, entrada, respuesta, sentimiento, origen) VALUES(?,?,?,?,?,?)"); $st->execute([$usuarioId, $session, $text, $fallback['respuesta'], $fallback['sentimiento'], 'texto']); }catch(Exception $e){}
  exit;
}
$data = json_decode($response, true);
if(!$data){ $data = ['respuesta'=>'No se pudo interpretar la respuesta.','sentimiento'=>'neutral','usuario_detectado'=>null,'action_sugerida'=>null]; }
echo json_encode($data);
try{ $st = $pdo->prepare("INSERT INTO chatbot_logs(usuario_id, session_id, entrada, respuesta, sentimiento, origen) VALUES(?,?,?,?,?,?)"); $st->execute([$usuarioId, $session, $text, $data['respuesta'] ?? '', $data['sentimiento'] ?? 'neutral', 'texto']); }catch(Exception $e){}
