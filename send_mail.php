<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/config.php';
$compra_id = (int)($_GET['compra_id'] ?? 0);
if(!$compra_id){ die('Compra no válida'); }
$st = $pdo->prepare("SELECT c.*, u.nombre, u.email FROM compras c LEFT JOIN usuarios u ON u.id=c.usuario_id WHERE c.id=?");
$st->execute([$compra_id]);
$c = $st->fetch();
if(!$c){ die('Compra no encontrada'); }
$boleta = __DIR__ . '/boletas/boleta_'.$compra_id.'.pdf';
if(!file_exists($boleta)){
  header('Location: generar_boleta.php?compra_id='.(int)$compra_id);
  exit;
}
if(file_exists(__DIR__.'/vendor/autoload.php')){
  require __DIR__.'/vendor/autoload.php';
  $mail = new PHPMailer\PHPMailer\PHPMailer(true);
  try{
    $mail->isSMTP();
    $mail->Host = $SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = $SMTP_USER;
    $mail->Password = $SMTP_PASS;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $SMTP_PORT;
    $mail->setFrom($SMTP_USER, 'Tienda Mascotas');
    $mail->addAddress($c['email'], $c['nombre']);
    $mail->Subject = 'Tu boleta #' . $compra_id;
    $mail->Body = 'Adjuntamos tu boleta de compra. Gracias por tu preferencia.';
    $mail->addAttachment($boleta);
    $mail->send();
    // marcar enviado
    $pdo->prepare("UPDATE boletas SET enviado_mail=1 WHERE compra_id=?")->execute([$compra_id]);
    echo 'Correo enviado a ' . htmlspecialchars($c['email']);
  }catch(Exception $e){ echo 'Error al enviar'; }
} else {
  echo 'PHPMailer no instalado. Ejecuta: composer install';
}
