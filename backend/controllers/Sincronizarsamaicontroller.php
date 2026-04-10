<?php
set_time_limit(120);

require_once __DIR__ . '/../api/ApiSamai.php';
require_once __DIR__ . '/../models/Actuacion.php';
require_once __DIR__ . '/../models/Proceso.php';
require_once __DIR__ . '/../libs/auth.php';

header('Content-Type: application/json');

$proceso_id   = (int)($_POST['proceso_id'] ?? 0);
$procesoModel = new Proceso();
$proceso      = $procesoModel->getById($proceso_id);

if (!$proceso) {
    echo json_encode(['success' => false, 'message' => 'Proceso no encontrado']);
    exit;
}
if (empty($proceso['numero_radicado'])) {
    echo json_encode(['success' => false, 'message' => 'El proceso no tiene número de radicado']);
    exit;
}

$api         = new ApiSamai();
$actuaciones = $api->consultarActuacionesPorRadicado($proceso['numero_radicado']);

if ($actuaciones === null) {
    echo json_encode(['success' => false, 'message' => 'No se pudo conectar con SAMAI — revisa logs/samai_sync.log']);
    exit;
}
if (empty($actuaciones)) {
    echo json_encode(['success' => false, 'message' => 'SAMAI no encontró actuaciones para este radicado']);
    exit;
}

$actuacionModel = new Actuacion();
$insertadas     = $actuacionModel->insertarLote($actuaciones, $proceso_id, 'samai');
$contador       = count($insertadas);

if ($contador > 0) {
    require_once __DIR__ . '/../services/NotificacionService.php';
    $svc = new NotificacionService();
    foreach ($insertadas as $act) {
        try { $svc->notificarNuevaActuacion($proceso, $act); }
        catch (Exception $e) {}
    }
}

$total = count($actuaciones);
if ($contador > 0) {
    echo json_encode(['success' => true,
        'message' => "SAMAI: {$contador} actuaciones nuevas de {$total} encontradas"]);
} else {
    echo json_encode(['success' => true,
        'message' => "SAMAI: todo al día — {$total} actuaciones ya registradas"]);
}
?>