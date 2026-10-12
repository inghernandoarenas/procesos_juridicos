<?php
// backend/controllers/SincronizarEstadoController.php
//
// Endpoint de polling: el frontend lo consulta cada pocos segundos mientras
// hay una sincronización TYBA/SAMAI en curso, para saber si ya terminó de
// traer los anexos en background y avisarle al usuario con un toast.

require_once __DIR__ . '/../libs/SyncStatus.php';
require_once __DIR__ . '/../libs/auth.php';

header('Content-Type: application/json');
verificarToken();

$proceso_id = (int) ($_GET['proceso_id'] ?? 0);

if (!$proceso_id) {
    echo json_encode(['success' => false, 'error' => 'proceso_id requerido']);
    exit;
}

echo json_encode([
    'success' => true,
    'estado'  => SyncStatus::get($proceso_id),
]);
?>