<?php
// Genera hash para Admin123! y actualiza el admin por defecto
require_once __DIR__ . '/conexion.php';
$hash = password_hash('Admin123!', PASSWORD_DEFAULT);
$st = $pdo->prepare("UPDATE usuarios SET password=? WHERE email='admin@local.test' LIMIT 1");
$st->execute([$hash]);
echo "Admin actualizado con hash: ".$hash;
