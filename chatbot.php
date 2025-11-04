<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/config.php';
$usuarioId = $_SESSION['usuario_id'] ?? null;
$session = session_id();
$usuarioNombre = $_SESSION['nombre'] ?? 'Invitado';
$inputRaw = file_get_contents('php://input');
$input = json_decode($inputRaw, true);
$text = $input['mensaje'] ?? ($_GET['mensaje'] ?? '');
$text = trim((string)$text);
if($text !== ''){ $text = mb_substr($text, 0, 500); }
$usuario = $input['usuario'] ?? ($_GET['usuario'] ?? $usuarioNombre);

function php_fallback($text, $usuarioId, $pdo, $usuarioNombre){
  $t = mb_strtolower($text, 'UTF-8');
  $greetName = $usuarioId ? $usuarioNombre : 'amigo';
  // Saludos
  if($t === '' || str_contains($t,'hola') || str_contains($t,'buenas') || str_contains($t,'ayuda')){
    return [
      'respuesta' => "Hola, $greetName 👋. Puedo ayudarte con productos, tu última compra, estado de pedido o soporte.",
      'sentimiento' => 'positivo',
      'usuario_detectado' => $usuarioId? $usuarioNombre : null,
      'action_sugerida' => null
    ];
  }
  // Última compra / estado
  if($usuarioId && (str_contains($t,'ultima compra') || str_contains($t,'última compra') || (str_contains($t,'estado') && str_contains($t,'pedido')) || str_contains($t,'historial'))){
    try{
      $st = $pdo->prepare("SELECT id,total,estado,fecha FROM compras WHERE usuario_id=? ORDER BY id DESC LIMIT 1");
      $st->execute([$usuarioId]);
      $c = $st->fetch();
      if($c){
        $det = $pdo->prepare("SELECT p.nombre,d.cantidad FROM detalles_compra d JOIN productos p ON p.id=d.producto_id WHERE d.compra_id=?");
        $det->execute([$c['id']]);
        $items = array_map(function($r){ return $r['cantidad'].'x '.$r['nombre']; }, $det->fetchAll());
        $itemsStr = $items? (': '.implode(', ',$items)) : '';
        return [
          'respuesta' => "Tu última compra (#{$c['id']}) está en estado '{$c['estado']}', total S/ ".number_format((float)$c['total'],2)."$itemsStr.",
          'sentimiento' => 'neutral',
          'usuario_detectado' => $usuarioNombre,
          'action_sugerida' => null
        ];
      }
      return [ 'respuesta' => 'Aún no tienes compras registradas.', 'sentimiento'=>'neutral', 'usuario_detectado'=>$usuarioNombre, 'action_sugerida'=>null ];
    }catch(Exception $e){ /* ignore */ }
  }
  // Productos destacados
  if(str_contains($t,'producto') || str_contains($t,'collar') || str_contains($t,'juguete') || str_contains($t,'recom') ){
    try{
      $rows = $pdo->query("SELECT nombre,precio FROM productos ORDER BY id DESC LIMIT 3")->fetchAll();
      if($rows){
        $parts = array_map(function($r){ return $r['nombre'].' (S/ '.number_format((float)$r['precio'],2).')'; }, $rows);
        return [ 'respuesta' => 'Algunas opciones: '.implode('; ', $parts).'. ¿Quieres ver más detalles?', 'sentimiento'=>'positivo', 'usuario_detectado'=>$usuarioId? $usuarioNombre:null, 'action_sugerida'=>null ];
      }
    }catch(Exception $e){ /* ignore */ }
  }
  // Respuesta genérica
  return [ 'respuesta' => 'Puedo ayudarte con productos, tu última compra, estado de pedidos o soporte. ¿Qué necesitas?', 'sentimiento'=>'neutral', 'usuario_detectado'=>$usuarioId? $usuarioNombre:null, 'action_sugerida'=>null ];
}

$base = $CHATBOT_API_URL ?: 'http://127.0.0.1:5000/chatbot';
$query = http_build_query([
  'mensaje' => $text,
  'usuario' => $usuario,
  'session_id' => $session,
  'user_id' => (string)$usuarioId,
]);
$apiUrl = $base . '?' . $query;
$headers = [];
if(!empty($CHATBOT_API_KEY)) $headers[] = 'X-API-Key: '.$CHATBOT_API_KEY;
$ctx = stream_context_create(['http' => [
  'timeout' => 6,
  'header' => implode("\r\n", $headers)
]]);
$response = false; $attempts = 0; $max = 2;
while($attempts <= $max){
  $attempts++;
  $response = @file_get_contents($apiUrl, false, $ctx);
  if($response !== false){ break; }
  usleep(300000); // 300ms backoff
}
if ($response === false) {
  $fallback = php_fallback($text, $usuarioId, $pdo, $usuarioNombre);
  echo json_encode($fallback);
  try{ $st = $pdo->prepare("INSERT INTO chatbot_logs(usuario_id, session_id, entrada, respuesta, sentimiento, origen) VALUES(?,?,?,?,?,?)"); $st->execute([$usuarioId, $session, $text, $fallback['respuesta'], $fallback['sentimiento'], 'texto']); }catch(Exception $e){}
  exit;
}
$data = json_decode($response, true);
if(!$data){ $data = php_fallback($text, $usuarioId, $pdo, $usuarioNombre); }
echo json_encode($data);
try{ $st = $pdo->prepare("INSERT INTO chatbot_logs(usuario_id, session_id, entrada, respuesta, sentimiento, origen) VALUES(?,?,?,?,?,?)"); $st->execute([$usuarioId, $session, $text, $data['respuesta'] ?? '', $data['sentimiento'] ?? 'neutral', 'texto']); }catch(Exception $e){}
