<?php
//Sincronizarsamaicontroller.php
// El expediente de SAMAI puede traer 20+ documentos y cada uno puede tardar
// hasta 45s en cargar en el visor (ver server.js) — con reintentos, el paso
// de anexos puede tomar varios minutos. Como corre en background después de
// responder al usuario (ignore_user_abort), un límite alto no afecta la
// experiencia, solo evita que PHP mate el proceso a medio camino.
set_time_limit(900);

// Nunca imprimir warnings/notices/deprecated en la respuesta: contaminan el JSON
// y el frontend lo muestra como "Error de conexión al sincronizar".
// Se registran en el log de errores de PHP en su lugar.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../api/ApiSamai.php';
require_once __DIR__ . '/../models/Actuacion.php';
require_once __DIR__ . '/../models/Proceso.php';
require_once __DIR__ . '/../models/Anexo.php';
require_once __DIR__ . '/../libs/auth.php';

header('Content-Type: application/json');

$proceso_id   = (int)($_POST['proceso_id'] ?? 0);
$usuario_id   = $_SESSION['usuario_id'] ?? null;
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

$api = new ApiSamai();

// 1. Traer y guardar actuaciones — esto es lo único que el usuario espera ver rápido.
$actuaciones = $api->consultarActuacionesPorRadicado($proceso['numero_radicado']);

if ($actuaciones === null) {
    echo json_encode(['success' => false, 'message' => 'No se pudo conectar con SAMAI — verifica que el servicio Node esté corriendo']);
    exit;
}

$actuacionModel = new Actuacion();
$insertadas     = $actuacionModel->insertarLote($actuaciones, $proceso_id, 'samai');
$contador       = count($insertadas);
$total          = count($actuaciones);

// ── Responder YA al frontend con el resultado de actuaciones ──────────────
$respuesta = json_encode([
    'success' => true,
    'message' => $contador > 0
        ? "SAMAI: {$contador} actuaciones nuevas de {$total} encontradas"
        : "SAMAI: todo al día — {$total} actuaciones ya registradas"
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
// reactivó en el frontend. Todo lo siguiente corre en background.
// ═══════════════════════════════════════════════════════════════════════

// 2. Documentos del expediente general (link "Visualizar expediente").
//    SAMAI no tiene anexos por actuación individual — solo este listado único.
$anexosSamai = $api->consultarAnexosPorRadicado($proceso['numero_radicado']);
if ($anexosSamai !== null && is_array($anexosSamai)) {
    $anexoModel = new Anexo();
    $anexoModel->insertarLoteSamai($anexosSamai, $proceso_id, $usuario_id);
}

// 3. Notificaciones por actuaciones nuevas
if ($contador > 0) {
    require_once __DIR__ . '/../services/NotificacionService.php';
    $svc = new NotificacionService();
    foreach ($insertadas as $act) {
        try { $svc->notificarNuevaActuacion($proceso, $act); }
        catch (Exception $e) { /* no interrumpir */ }
    }
}
?>