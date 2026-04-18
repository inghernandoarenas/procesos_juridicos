<?php
set_time_limit(120);

require_once __DIR__ . '/../api/ApiRamaJudicial.php';
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

// ── 1. Consultar API ──────────────────────────────────────────
$api         = new ApiRamaJudicial();
$actuaciones = $api->consultarActuacionesPorRadicado($proceso['numero_radicado']);

if ($actuaciones === null) {
    echo json_encode(['success' => false, 'message' => 'No se pudo conectar con la Rama Judicial']);
    exit;
}
if (empty($actuaciones)) {
    echo json_encode(['success' => true, 'message' => 'El proceso no tiene actuaciones en Rama Judicial']);
    exit;
}

// ── 2. Insertar en lote ───────────────────────────────────────
$actuacionModel = new Actuacion();
$insertadas     = $actuacionModel->insertarLote($actuaciones, $proceso_id);
$contador       = count($insertadas);
$c_api          = count($actuaciones); // ← definir ANTES del log

// Log
$logFile = __DIR__ . '/../../logs/rama_sync.log';
$ts      = date('H:i:s');
file_put_contents($logFile,
    "[$ts] SYNC FIN proceso_id=$proceso_id — API:{$c_api} insertadas:{$contador} ya_existian:" . ($c_api - $contador) . "\n",
    FILE_APPEND
);

// ── 3. Respuesta inmediata al frontend ────────────────────────
if ($contador > 0) {
    echo json_encode(['success' => true,
        'message' => "Rama: {$contador} actuaciones nuevas de {$c_api} encontradas"]);
} else {
    echo json_encode(['success' => true,
        'message' => "Rama: todo al día — {$c_api} actuaciones ya registradas"]);
}

// ── 4. Notificaciones en background (no bloquea) ─────────────
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