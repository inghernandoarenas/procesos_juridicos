<?php
set_time_limit(120);

require_once __DIR__ . '/../api/ApiTyba.php';
require_once __DIR__ . '/../models/Actuacion.php';
require_once __DIR__ . '/../models/Anexo.php'; // <-- AGREGAR ESTA LÍNEA
require_once __DIR__ . '/../libs/auth.php';

header('Content-Type: application/json');
verificarToken();

$proceso_id = (int)($_POST['proceso_id'] ?? 0);
$usuario_id = $_SESSION['usuario_id'] ?? null; // Asumiendo que guardas el ID en sesión

$procesoModel = new Proceso();
$proceso = $procesoModel->getById($proceso_id);

if (!$proceso) {
    echo json_encode(['success' => false, 'message' => 'Proceso no encontrado']);
    exit;
}

if (empty($proceso['numero_radicado'])) {
    echo json_encode(['success' => false, 'message' => 'El proceso no tiene número de radicado']);
    exit;
}

$api = new ApiTyba();

// 1. Traer y guardar actuaciones
$actuaciones = $api->consultarActuacionesPorRadicado($proceso['numero_radicado']);
if ($actuaciones === null) {
    echo json_encode(['success' => false, 'message' => 'No se pudo conectar con TYBA — verifica que el servicio Node esté corriendo']);
    exit;
}

$actuacionModel = new Actuacion();
$insertadasAct = $actuacionModel->insertarLote($actuaciones, $proceso_id, 'tyba');
$contadorAct = count($insertadasAct);
$totalAct = count($actuaciones);

// 2. Traer y guardar anexos (NUEVO)
$anexosTyba = $api->consultarAnexosPorRadicado($proceso['numero_radicado']); // <-- Ver nota abajo*
$contadorAnexos = 0;
if ($anexosTyba !== null && is_array($anexosTyba)) {
    $anexoModel = new Anexo();
    $resultadoAnexos = $anexoModel->insertarLoteTyba($anexosTyba, $proceso_id, $usuario_id);
    $contadorAnexos = $resultadoAnexos['insertados'];
}

// Respuesta inmediata
$mensaje = [];
if ($contadorAct > 0) $mensaje[] = "{$contadorAct} actuaciones nuevas";
if ($contadorAnexos > 0) $mensaje[] = "{$contadorAnexos} anexos nuevos";

echo json_encode([
    'success' => true,
    'message' => count($mensaje) > 0 ? "TYBA: " . implode(", ", $mensaje) : "TYBA: todo al día",
]);

// Notificaciones en background (solo por actuaciones, como ya lo tenías)
if ($contadorAct > 0) {
    if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    else { while (ob_get_level() > 0) ob_end_flush(); flush(); }
    
    require_once __DIR__ . '/../services/NotificacionService.php';
    $svc = new NotificacionService();
    foreach ($insertadasAct as $act) {
        try { $svc->notificarNuevaActuacion($proceso, $act); }
        catch (Exception $e) {}
    }
}
?>