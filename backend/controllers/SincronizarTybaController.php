<?php
// Antes 120, luego 180. Ahora 900 (15 min): el nuevo paso de anexos por
// actuación entra al detalle de CADA actuación una por una, y con procesos
// de muchas actuaciones eso puede tardar varios minutos. Como todo esto
// corre en background (después de responder al usuario), este límite alto
// no afecta la experiencia — solo evita que PHP mate el proceso a medias.
set_time_limit(900);

// Nunca imprimir warnings/notices/deprecated en la respuesta: contaminan el JSON
// y el frontend lo muestra como "Error de conexión al sincronizar".
// Se registran en el log de errores de PHP en su lugar.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../api/ApiTyba.php';
require_once __DIR__ . '/../models/Actuacion.php';
require_once __DIR__ . '/../models/Proceso.php'; // FIX: se había perdido al agregar Anexo.php -> "Class Proceso not found"
require_once __DIR__ . '/../models/Anexo.php';
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

// 1. Traer y guardar actuaciones — esto es lo único que el usuario espera ver rápido.
//    Antes, el request seguía bloqueado consultando anexos (otro scrape completo de
//    TYBA) antes de responder, lo que sumaba ~20-60s extra y a menudo superaba el
//    timeout de Apache (por defecto 60s) o el set_time_limit del script, cortando
//    la conexión a medio camino. El frontend interpretaba eso como "Error de
//    conexión al sincronizar" aunque no era un problema real de red.
$actuaciones = $api->consultarActuacionesPorRadicado($proceso['numero_radicado']);
if ($actuaciones === null) {
    echo json_encode(['success' => false, 'message' => 'No se pudo consultar TYBA en este momento. Intenta de nuevo; si persiste, revisa logs/tyba_sync.log y que el servicio Node esté corriendo']);
    exit;
}

$actuacionModel = new Actuacion();
$insertadasAct = $actuacionModel->insertarLote($actuaciones, $proceso_id, 'tyba');
$contadorAct = count($insertadasAct);
$totalAct = count($actuaciones);

// ── Responder YA al frontend con el resultado de actuaciones ──────────────
$respuesta = json_encode([
    'success' => true,
    'message' => $contadorAct > 0
        ? "TYBA: {$contadorAct} actuaciones nuevas"
        : "TYBA: todo al día (actuaciones)",
]);

// Seguir corriendo aunque el navegador ya tenga su respuesta
ignore_user_abort(true);
// Liberar el lock de sesión: si no, cualquier otra petición del mismo usuario
// (abrir el modal, otra sincronización) queda esperando hasta que termine el background.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

if (function_exists('fastcgi_finish_request')) {
    echo $respuesta;
    fastcgi_finish_request();
} else {
    // XAMPP / mod_php: flush() solo NO cierra la conexión. Hay que decirle al
    // navegador exactamente cuántos bytes esperar y que la conexión termina ahí.
    header('Content-Length: ' . strlen($respuesta));
    header('Connection: close');
    echo $respuesta;
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    flush();
}

// ═══════════════════════════════════════════════════════════════════════
// A PARTIR DE AQUÍ: el usuario ya recibió su respuesta y el botón se
// reactivó en el frontend. Todo lo siguiente corre en background y ya
// no puede producir el mensaje "Error de conexión al sincronizar".
// ═══════════════════════════════════════════════════════════════════════

// 2a. Anexos generales del proceso — pestaña "Archivos" (ej: 01DEMANDA.pdf)
$anexosTyba = $api->consultarAnexosPorRadicado($proceso['numero_radicado']);
if ($anexosTyba !== null && is_array($anexosTyba)) {
    $anexoModel = new Anexo();
    $anexoModel->insertarLoteTyba($anexosTyba, $proceso_id, $usuario_id);
}

// 2b. Anexos propios de cada actuación (ej: el auto que se dictó en esa
//     actuación puntual) — entra al detalle de cada una, puede tardar varios
//     minutos en procesos con muchas actuaciones. Corre después de que las
//     actuaciones ya quedaron guardadas en el paso 1, porque necesita su
//     id_api para asociar correctamente cada anexo.
$anexosPorActuacion = $api->consultarAnexosPorActuacion($proceso['numero_radicado']);
if ($anexosPorActuacion !== null && is_array($anexosPorActuacion)) {
    $anexoModel = $anexoModel ?? new Anexo();
    $anexoModel->insertarLoteTybaPorActuacion($anexosPorActuacion, $proceso_id, $usuario_id);
}

// 3. Notificaciones por actuaciones nuevas
if ($contadorAct > 0) {
    require_once __DIR__ . '/../services/NotificacionService.php';
    $svc = new NotificacionService();
    foreach ($insertadasAct as $act) {
        try {
            $svc->notificarNuevaActuacion($proceso, $act);
        } catch (Exception $e) {
            /* no interrumpir */
        }
    }
}
?>