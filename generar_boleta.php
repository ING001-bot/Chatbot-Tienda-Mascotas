<?php
require_once __DIR__ . '/conexion.php';
$compra_id = (int)($_GET['compra_id'] ?? 0);
if(!$compra_id){ die('Compra no válida'); }
$st = $pdo->prepare("SELECT c.*, u.nombre, u.email FROM compras c LEFT JOIN usuarios u ON u.id=c.usuario_id WHERE c.id=?");
$st->execute([$compra_id]);
$c = $st->fetch();
if(!$c){ die('Compra no encontrada'); }
$det = $pdo->prepare("SELECT d.*, p.nombre FROM detalles_compra d LEFT JOIN productos p ON p.id=d.producto_id WHERE d.compra_id=?");
$det->execute([$compra_id]);
$detalles = $det->fetchAll();
$html = '<style>body{font-family:DejaVu Sans, sans-serif;}</style><h1>Boleta #'.(int)$compra_id.'</h1>';
$html .= '<p>Cliente: '.htmlspecialchars($c['nombre']).' ('.htmlspecialchars($c['email']).')</p>';
$html .= '<p>Fecha: '.htmlspecialchars($c['fecha']).'</p>';
$html .= '<table border="1" cellspacing="0" cellpadding="6"><tr><th>Producto</th><th>Cant</th><th>P. Unit</th><th>Sub</th></tr>';
$total = 0; foreach($detalles as $d){ $sub = $d['cantidad'] * $d['precio_unitario']; $total += $sub; $html.='<tr><td>'.htmlspecialchars($d['nombre']).'</td><td>'.$d['cantidad'].'</td><td>S/ '.number_format((float)$d['precio_unitario'],2).'</td><td>S/ '.number_format((float)$sub,2).'</td></tr>'; }
$html .= '<tr><td colspan="3" align="right"><strong>Total</strong></td><td><strong>S/ '.number_format((float)$total,2).'</strong></td></tr></table>';
$dest = __DIR__ . '/boletas'; if(!is_dir($dest)) mkdir($dest, 0777, true);
$file = $dest . '/boleta_'.$compra_id.'.pdf';
$numero = 'BOL-'.str_pad((string)$compra_id, 6, '0', STR_PAD_LEFT);
if(file_exists(__DIR__.'/vendor/autoload.php')){
  require __DIR__.'/vendor/autoload.php';
  $dompdf = new Dompdf\Dompdf();
  $dompdf->loadHtml($html);
  $dompdf->setPaper('A4', 'portrait');
  $dompdf->render();
  file_put_contents($file, $dompdf->output());
  // registrar en tabla boletas si no existe
  $pdo->prepare("INSERT IGNORE INTO boletas(compra_id, numero_boleta, archivo_pdf) VALUES(?,?,?)")->execute([$compra_id,$numero, 'boletas/boleta_'.$compra_id.'.pdf']);
  header('Content-Type: application/pdf');
  readfile($file);
} else {
  header('Content-Type: text/html; charset=utf-8');
  echo '<p><strong>DomPDF no instalado.</strong> Instala con Composer: <code>composer install</code></p>';
  echo $html;
}
