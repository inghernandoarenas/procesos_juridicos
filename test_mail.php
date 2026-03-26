<?php
require_once 'backend/services/EmailService.php';

$email = new EmailService();
$resultado = $email->enviar('ing.hernando.arenas@gmail.com', 'Prueba', 'Mensaje de prueba');

echo $resultado ? '✅ Enviado' : '❌ Error';