<?php
header('Content-Type: application/json');
$mensaje = $_GET['m'] ?? 'hola';
$payload = json_encode(['mensaje'=>$mensaje]);
$opts = ['http'=>['method'=>'POST','header'=>'Content-Type: application/json','content'=>$payload,'timeout'=>4]];
$ctx = stream_context_create($opts);
$resp = @file_get_contents('http://localhost/Chatbot/chatbot.php', false, $ctx);
echo $resp !== false ? $resp : json_encode(['error'=>'sin respuesta']);
