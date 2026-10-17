<?php
// ═══════════════════════════════════════════════════════════════════════
// CRON DE SINCRONIZACIÓN AUTOMÁTICA
// Recorre TODOS los procesos activos y los sincroniza contra el portal que
// cada uno tiene asignado en "fuente_consulta" (rama / samai / tyba).
//
// ANTES: este script solo consultaba Rama Judicial para absolutamente todos
// los procesos, sin importar qué portal tuviera configurado cada uno — un
// proceso de SAMAI o TYBA nunca se sincronizaba por cron, solo a mano desde
// la UI. Ahora cada proceso se enruta al API correcto según su portal.
//
// Portales soportados por el cron: rama, samai, tyba.
// "penal" (SIUGJ) se omite — el portal aún no está integrado (ver plan de
// profesionalización, ítem 6). "ninguna"/vacío también se omite.
// ═══════════════════════════════════════════════════════════════════════

ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Los error_log() de las clases Api* (cURL, HTTP != 200, etc.) en CLI se
// pierden por stderr. Se redirigen a un archivo para poder diagnosticar.
ini_set('error_log', __DIR__ . '/../../logs/cron_php_errors.log');

define('SISTEMA_URL', 'http://localhost');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/ApiRamaJudicial.php';
require_once __DIR__ . '/../api/ApiSamai.php';
require_once __DIR__ . '/../api/Apityba.php';
require_once __DIR__ . '/../models/Proceso.php';
require_once __DIR__ . '/../models/Actuacion.php';
require_once __DIR__ . '/../models/Anexo.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/../services/NotificacionService.php';

// ── Log ────────────────────────────────────────────────────────────────
$logFile = __DIR__ . '/../../logs/sincronizacion.log';
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}

function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

/**
 * Guarda un lote de actuaciones nuevas y dispara sus notificaciones.
 * Centralizado aquí para no repetir el mismo bloque tres veces (rama/samai/tyba).
 */
function guardarActuacionesYNotificar(Actuacion $actuacionModel, NotificacionService $notificacionSvc, array $proceso, array $actuaciones, string $fuente): int {
    if (empty($actuaciones)) return 0;

    $insertadas = $actuacionModel->insertarLote($actuaciones, (int)$proceso['id'], $fuente);
    $nuevas     = count($insertadas);

    writeLog("  [$fuente] Actuaciones recibidas: " . count($actuaciones) . " — nuevas: $nuevas");

    foreach ($insertadas as $act) {
        try {
            $resultados = $notificacionSvc->notificarNuevaActuacion($proceso, $act);
            writeLog("    📧 Notificaciones enviadas (" . ($act['actuacion'] ?? '') . "): " . count($resultados));
        } catch (Throwable $e) {
            writeLog("    ⚠️ Error en notificación: " . $e->getMessage());
        }
    }

    return $nuevas;
}

// Captura errores fatales (los que NO son Exception, p.ej. TypeError) que
// antes mataban el script en silencio sin dejar rastro en el log.
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        writeLog("💥 ERROR FATAL: {$e['message']} en {$e['file']}:{$e['line']}");
    }
});

writeLog("=== INICIO SINCRO AUTOMÁTICA ===");
writeLog("Entorno: PHP " . PHP_VERSION . " (" . PHP_SAPI . "), php.ini=" . (php_ini_loaded_file() ?: 'ninguno')
    . ", allow_url_fopen=" . (ini_get('allow_url_fopen') ? 'on' : 'OFF')
    . ", openssl=" . (extension_loaded('openssl') ? 'on' : 'OFF')
    . ", curl=" . (extension_loaded('curl') ? 'on' : 'OFF'));

// ¿Está vivo el servicio Node (SAMAI/TYBA)?
$ch = curl_init('http://127.0.0.1:3001/health');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
$h = curl_exec($ch);
$hc = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$he = curl_error($ch);
curl_close($ch);
writeLog("samai-service /health: " . ($hc === 200 ? "OK" : "NO RESPONDE (HTTP $hc $he)"));

$procesoModel = new Proceso();
$procesos     = $procesoModel->getAll();

writeLog("Procesos a revisar: " . count($procesos));

// Agrupar por portal para que, si un portal falla (p.ej. el servicio Node de
// SAMAI/TYBA está caído), no se interrumpa la sincronización de los demás.
$porFuente = ['rama' => [], 'samai' => [], 'tyba' => []];
$omitidos  = 0;
foreach ($procesos as $proceso) {
    $fuente = $proceso['fuente_consulta'] ?? 'ninguna';
    if (empty($proceso['numero_radicado'])) {
        writeLog("  Proceso ID {$proceso['id']} sin radicado - omitido");
        $omitidos++;
        continue;
    }
    if (isset($porFuente[$fuente])) {
        $porFuente[$fuente][] = $proceso;
    } else {
        // 'ninguna', 'penal' (SIUGJ aún no integrado), o cualquier valor futuro no mapeado
        writeLog("  Proceso ID {$proceso['id']} con portal '$fuente' - sin sincronización automática disponible, omitido");
        $omitidos++;
    }
}

$actuacionModel  = new Actuacion();
$anexoModel      = new Anexo();
$notificacionSvc = new NotificacionService(); // una sola instancia para todo el cron
$totalNuevas     = 0;
$totalAnexos     = 0;
$errores         = 0;

// ── 1. RAMA JUDICIAL ─────────────────────────────────────────────────────
writeLog("--- Portal: RAMA JUDICIAL (" . count($porFuente['rama']) . " procesos) ---");
$apiRama = new ApiRamaJudicial();
foreach ($porFuente['rama'] as $proceso) {
    writeLog("Revisando proceso [rama]: {$proceso['numero_radicado']}");
    try {
        $actuaciones = $apiRama->consultarActuacionesPorRadicado($proceso['numero_radicado']);

        if ($actuaciones === null) {
            writeLog("  ERROR: API no respondió para radicado {$proceso['numero_radicado']}");
            $errores++;
            continue;
        }
        if (!is_array($actuaciones)) {
            writeLog("  ERROR: Respuesta inválida de API para {$proceso['numero_radicado']}");
            $errores++;
            continue;
        }

        $totalNuevas += guardarActuacionesYNotificar($actuacionModel, $notificacionSvc, $proceso, $actuaciones, 'rama');
    } catch (Throwable $e) {
        writeLog("  ❌ EXCEPCIÓN [rama]: " . $e->getMessage());
        $errores++;
    }
}

// ── 2. SAMAI (Consejo de Estado) ─────────────────────────────────────────
writeLog("--- Portal: SAMAI (" . count($porFuente['samai']) . " procesos) ---");
$apiSamai = new ApiSamai();
foreach ($porFuente['samai'] as $proceso) {
    writeLog("Revisando proceso [samai]: {$proceso['numero_radicado']}");
    try {
        $actuaciones = $apiSamai->consultarActuacionesPorRadicado($proceso['numero_radicado']);
        if ($actuaciones === null) {
            writeLog("  ERROR: SAMAI no respondió (¿el servicio Node está corriendo?) para {$proceso['numero_radicado']}");
            $errores++;
            continue;
        }

        $totalNuevas += guardarActuacionesYNotificar($actuacionModel, $notificacionSvc, $proceso, $actuaciones, 'samai');

        // Expediente general (único listado de documentos que expone SAMAI)
        $anexos = $apiSamai->consultarAnexosPorRadicado($proceso['numero_radicado'], $proceso['id']);
        if ($anexos === null) {
            writeLog("  [samai] ⚠️ Documentos del expediente: el servicio devolvió error/timeout (ver logs/cron_php_errors.log)");
        } elseif (!empty($anexos)) {
            $anexoModel->insertarLoteSamai($anexos, (int)$proceso['id'], null);
            $totalAnexos += count($anexos);
            writeLog("  [samai] Documentos del expediente: " . count($anexos));
        } else {
            writeLog("  [samai] Documentos del expediente: 0");
        }
    } catch (Throwable $e) {
        writeLog("  ❌ EXCEPCIÓN [samai]: " . $e->getMessage());
        $errores++;
    }
}

// ── 3. TYBA (Justicia XXI Web) ───────────────────────────────────────────
writeLog("--- Portal: TYBA (" . count($porFuente['tyba']) . " procesos) ---");
$apiTyba = new ApiTyba();
foreach ($porFuente['tyba'] as $proceso) {
    writeLog("Revisando proceso [tyba]: {$proceso['numero_radicado']}");
    try {
        $actuaciones = $apiTyba->consultarActuacionesPorRadicado($proceso['numero_radicado']);
        if ($actuaciones === null) {
            writeLog("  ERROR: TYBA no respondió (¿el servicio Node está corriendo?) para {$proceso['numero_radicado']}");
            $errores++;
            continue;
        }

        $totalNuevas += guardarActuacionesYNotificar($actuacionModel, $notificacionSvc, $proceso, $actuaciones, 'tyba');

        // 3a. Anexos generales del proceso (pestaña "Archivos")
        $anexosGenerales = $apiTyba->consultarAnexosPorRadicado($proceso['numero_radicado'], $proceso['id']);
        if ($anexosGenerales === null) {
            writeLog("  [tyba] ⚠️ Anexos generales: error/timeout (ver logs/cron_php_errors.log)");
        } elseif (!empty($anexosGenerales)) {
            $anexoModel->insertarLoteTyba($anexosGenerales, (int)$proceso['id'], null);
            $totalAnexos += count($anexosGenerales);
            writeLog("  [tyba] Anexos generales: " . count($anexosGenerales));
        } else {
            writeLog("  [tyba] Anexos generales: 0");
        }

        // 3b. Anexos propios de cada actuación (entra al detalle de cada una,
        //     puede tardar varios minutos en procesos con muchas actuaciones)
        $anexosPorActuacion = $apiTyba->consultarAnexosPorActuacion($proceso['numero_radicado'], $proceso['id']);
        if ($anexosPorActuacion === null) {
            writeLog("  [tyba] ⚠️ Anexos por actuación: error/timeout (ver logs/cron_php_errors.log)");
        } elseif (!empty($anexosPorActuacion)) {
            $res = $anexoModel->insertarLoteTybaPorActuacion($anexosPorActuacion, (int)$proceso['id'], null);
            $totalAnexos += count($anexosPorActuacion);
            writeLog("  [tyba] Anexos por actuación: " . count($anexosPorActuacion) . " actuaciones con archivos — resultado: " . json_encode($res));
        } else {
            writeLog("  [tyba] Anexos por actuación: 0");
        }
    } catch (Throwable $e) {
        writeLog("  ❌ EXCEPCIÓN [tyba]: " . $e->getMessage());
        $errores++;
    }
}

writeLog("=== FIN SINCRO — Nuevas actuaciones: $totalNuevas, Anexos nuevos: $totalAnexos, Omitidos: $omitidos, Errores: $errores ===\n");