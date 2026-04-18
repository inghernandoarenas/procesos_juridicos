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
    echo json_encode(['success' => false, 'message' => 'No se pudo conectar con SAMAI — verifica que el servicio Node esté corriendo']);
    exit;
}

$actuacionModel = new Actuacion();
$insertadas     = $actuacionModel->insertarLote($actuaciones, $proceso_id, 'samai');
$contador       = count($insertadas);
$total          = count($actuaciones);

// ── Respuesta inmediata al frontend ──────────────────────────
echo json_encode([
    'success' => true,
    'message' => $contador > 0
        ? "SAMAI: {$contador} actuaciones nuevas de {$total} encontradas"
        : "SAMAI: todo al día — {$total} actuaciones ya registradas"
]);

// ── Notificaciones en background (no bloquea) ─────────────────
if ($contador > 0) {
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        ob_end_flush();
        flush();
    }
    require_once __DIR__ . '/../services/NotificacionService.php';
    $svc = new NotificacionService();
    foreach ($insertadas as $act) {
        try { $svc->notificarNuevaActuacion($proceso, $act); }
        catch (Exception $e) { /* no interrumpir */ }
    }
}
?>