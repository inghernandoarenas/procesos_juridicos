<?php
require_once __DIR__ . '/../models/Notificacion.php';
require_once __DIR__ . '/../libs/auth.php';

header('Content-Type: application/json');
verificarToken();

$action = $_GET['action'] ?? '';
$notif  = new Notificacion();

if ($action === 'list') {
    $limite = (int)($_GET['limite'] ?? 100);
    echo json_encode($notif->getLogs($limite));
    exit;
}
?>