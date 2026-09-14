<?php
set_time_limit(120);

require_once __DIR__ . '/../api/ApiTyba.php';
require_once __DIR__ . '/../models/Actuacion.php';
require_once __DIR__ . '/../models/Proceso.php';
require_once __DIR__ . '/../libs/auth.php';

header('Content-Type: application/json');
verificarToken();

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

$api         = new ApiTyba();
$actuaciones = $api->consultarActuacionesPorRadicado($proceso['numero_radicado']);

if ($actuaciones === null) {
    echo json_encode(['success' => false, 'message' => 'No se pudo conectar con TYBA — verifica que el servicio Node esté corriendo']);
    exit;
}

$actuacionModel = new Actuacion();
$insertadas     = $actuacionModel->insertarLote($actuaciones, $proceso_id, 'tyba');
$contador       = count($insertadas);
$total          = count($actuaciones);

// Respuesta inmediata
echo json_encode([
    'success' => true,
    'message' => $contador > 0
        ? "TYBA: {$contador} actuaciones nuevas de {$total} encontradas"
        : "TYBA: todo al día — {$total} actuaciones ya registradas",
]);

// Notificaciones en background
if ($contador > 0) {
    if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    else { while (ob_get_level() > 0) ob_end_flush(); flush(); }

    require_once __DIR__ . '/../services/NotificacionService.php';
    $svc = new NotificacionService();
    foreach ($insertadas as $act) {
        try { $svc->notificarNuevaActuacion($proceso, $act); }
        catch (Exception $e) {}
    }
}
?>