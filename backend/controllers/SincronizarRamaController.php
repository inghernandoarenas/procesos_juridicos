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

// ── 1. Consultar API ──────────────────────────────────────────────
$api         = new ApiRamaJudicial();
$actuaciones = $api->consultarActuacionesPorRadicado($proceso['numero_radicado']);

if ($actuaciones === null) {
    echo json_encode(['success' => false, 'message' => 'No se pudo conectar con la Rama Judicial']);
    exit;
}
if (empty($actuaciones)) {
    echo json_encode(['success' => false, 'message' => 'La consulta no devolvió actuaciones para este radicado']);
    exit;
}

// ── 2. Insertar en lote (1 SELECT + 1 INSERT vs 52 SELECT + 52 INSERT) ────
$actuacionModel = new Actuacion();
$insertadas     = $actuacionModel->insertarLote($actuaciones, $proceso_id);
$contador       = count($insertadas);

// Log
$logFile = __DIR__ . '/../../logs/rama_sync.log';
$ts      = date('H:i:s');
file_put_contents($logFile,
    "[$ts] SYNC FIN proceso_id=$proceso_id — API:{$c_api} insertadas:{$contador} ya_existian:" . (count($actuaciones) - $contador) . "\n",
    FILE_APPEND
);

// ── 3. Notificaciones solo de las realmente nuevas ────────────────
if ($contador > 0) {
    require_once __DIR__ . '/../services/NotificacionService.php';
    $notificacionService = new NotificacionService();
    foreach ($insertadas as $act) {
        try {
            $notificacionService->notificarNuevaActuacion($proceso, $act);
        } catch (Exception $e) { /* no interrumpir */ }
    }
}

// ── 4. Respuesta ──────────────────────────────────────────────────
$c_api = count($actuaciones);
if ($contador > 0) {
    echo json_encode(['success' => true,
        'message' => "Se importaron {$contador} actuaciones nuevas de {$c_api} encontradas"]);
} else {
    echo json_encode(['success' => true,
        'message' => "Todo actualizado — {$c_api} actuaciones ya estaban registradas"]);
}
?>