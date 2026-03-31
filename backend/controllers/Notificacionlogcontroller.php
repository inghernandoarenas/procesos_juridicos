<?php
require_once __DIR__ . '/../models/Notificacion.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$notif  = new Notificacion();

if ($action === 'list') {
    $limite = (int)($_GET['limite'] ?? 100);
    echo json_encode($notif->getLogs($limite));
    exit;
}
?>