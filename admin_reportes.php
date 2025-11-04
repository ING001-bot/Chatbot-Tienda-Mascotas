<?php
session_start();
require_once __DIR__ . '/conexion.php';
if(($_SESSION['rol'] ?? '') !== 'admin'){ http_response_code(403); die('Acceso restringido'); }
$type = $_GET['type'] ?? 'csv';
$ventas = $pdo->query("SELECT DATE(fecha) d, SUM(total) t FROM compras GROUP BY DATE(fecha) ORDER BY d DESC LIMIT 30")->fetchAll();
if($type==='pdf' && file_exists(__DIR__.'/vendor/autoload.php')){
  require __DIR__.'/vendor/autoload.php';
  $html = '<h1>Reporte de ventas</h1><table border="1" cellspacing="0" cellpadding="6"><tr><th>Fecha</th><th>Total</th></tr>';
  foreach($ventas as $v){ $html.='<tr><td>'.$v['d'].'</td><td>S/ '.number_format((float)$v['t'],2).'</td></tr>'; }
  $html.='</table>';
  $dompdf = new Dompdf\Dompdf();
  $dompdf->loadHtml($html); $dompdf->setPaper('A4','portrait'); $dompdf->render();
  header('Content-Type: application/pdf');
  echo $dompdf->output();
  exit;
}
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=reportes_ventas.csv');
echo "fecha,total\n";
foreach($ventas as $v){ echo $v['d'] . ',' . number_format((float)$v['t'],2,".","") . "\n"; }
