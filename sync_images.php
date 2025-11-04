<?php
// Sincroniza rutas de imágenes locales en /img con los productos en BD
// Coincide por slug del nombre: "Collar de cuero" -> "collar-de-cuero.{jpg|jpeg}"
// Uso: abrir en navegador: http://localhost/Chatbot/sync_images.php

session_start();
require_once __DIR__ . '/conexion.php';
header('Content-Type: text/plain; charset=utf-8');

$baseDir = __DIR__ . '/img';
if(!is_dir($baseDir)){
  http_response_code(500);
  echo "Carpeta img/ no encontrada: $baseDir\n";
  exit;
}

function slug($s){
  // Transliterate Spanish accents reliably (avoid stray apostrophes)
  $map = [
    'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
    'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n'
  ];
  $s = strtr($s, $map);
  $s = strtolower($s);
  $s = preg_replace('/[^a-z0-9]+/','-', $s);
  $s = preg_replace('/-+/', '-', $s); // collapse multiple hyphens
  $s = trim($s,'-');
  return $s;
}

$exts = ['jpg','jpeg'];
$select = $pdo->query("SELECT id,nombre FROM productos");
$rows = $select->fetchAll();
$upd = $pdo->prepare("UPDATE productos SET imagen=? WHERE id=?");
$updated = 0; $missing = [];
foreach($rows as $r){
  $slug = slug($r['nombre']);
  $path = null;
  foreach($exts as $e){
    $candidate = $baseDir . '/' . $slug . '.' . $e;
    if(file_exists($candidate)){
      $path = 'img/' . $slug . '.' . $e;
      break;
    }
  }
  if($path){
    $upd->execute([$path, $r['id']]);
    $updated++;
    echo "OK: {$r['nombre']} -> $path\n";
  } else {
    $missing[] = $r['nombre'];
    echo "FALTA: {$r['nombre']} (sube: {$slug}.[jpg|jpeg])\n";
  }
}

echo "\nActualizados: $updated\n";
if($missing){
  echo "Pendientes (no se encontró archivo):\n- ".implode("\n- ", $missing)."\n";
}
