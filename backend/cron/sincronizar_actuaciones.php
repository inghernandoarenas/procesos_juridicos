<?php
// Configurar para ejecución por cron
ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// URL base del sistema — ajusta esto a tu dominio real
define('SISTEMA_URL', 'http://localhost');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/ApiRamaJudicial.php';
require_once __DIR__ . '/../models/Proceso.php';
require_once __DIR__ . '/../models/Actuacion.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/../services/NotificacionService.php';

// Log
$logFile = __DIR__ . '/../../logs/sincronizacion.log';
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}

function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

writeLog("=== INICIO SINCRO AUTOMÁTICA ===");

// Obtener todos los procesos activos
$procesoModel = new Proceso();
$procesos = $procesoModel->getAll();

writeLog("Procesos a revisar: " . count($procesos));

$api             = new ApiRamaJudicial();
$actuacionModel  = new Actuacion();
$notificacionSvc = new NotificacionService(); // una sola instancia para todo el cron
$totalNuevas     = 0;
$errores         = 0;

foreach($procesos as $proceso) {
    // Verificar que tenga radicado
    if(empty($proceso['numero_radicado'])) {
        writeLog("  Proceso ID {$proceso['id']} sin radicado - omitido");
        continue;
    }
    
    writeLog("Revisando proceso: {$proceso['numero_radicado']}");
    
    try {
        // Consultar API con timeout
        $actuaciones = $api->consultarActuacionesPorRadicado($proceso['numero_radicado']);
        
        // Validar respuesta
        if($actuaciones === null) {
            writeLog("  ERROR: API no respondió para radicado {$proceso['numero_radicado']}");
            $errores++;
            continue;
        }
        
        if(!is_array($actuaciones)) {
            writeLog("  ERROR: Respuesta inválida de API para {$proceso['numero_radicado']}");
            $errores++;
            continue;
        }
        
        if(empty($actuaciones)) {
            writeLog("  No se encontraron actuaciones para este radicado");
            continue;
        }
        
        writeLog("  Actuaciones recibidas: " . count($actuaciones));
        
        $nuevas = 0;
        foreach($actuaciones as $act) {
            // Verificar si ya existe
            $existe = $actuacionModel->existeActuacion($act['id'], $proceso['id']);
            
            if(!$existe) {
                // Guardar actuación y obtener ID
                $data = [
                    ':proceso_id' => $proceso['id'],
                    ':id_api' => $act['id'],
                    ':fecha' => $act['fecha'],
                    ':actuacion' => $act['actuacion'],
                    ':observaciones' => $act['observaciones']
                ];
                
                $nuevoId = $actuacionModel->createAndGetId($data);
                
                if($nuevoId) {
                    $nuevas++;
                    writeLog("    ✅ Nueva: {$act['actuacion']} - {$act['fecha']}");
                    
                    // Agregar el ID de BD a la actuación
                    $act['id'] = $nuevoId;
                    
                    // Disparar notificación
                    try {
                        $resultados = $notificacionSvc->notificarNuevaActuacion($proceso, $act);
                        writeLog("    📧 Notificaciones enviadas: " . count($resultados));
                    } catch(Exception $e) {
                        writeLog("    ⚠️ Error en notificación: " . $e->getMessage());
                    }
                } else {
                    writeLog("    ❌ Error al guardar actuación: {$act['actuacion']}");
                }
            }
                    }
        
        if($nuevas > 0) {
            writeLog("  Total nuevas en este proceso: $nuevas");
            $totalNuevas += $nuevas;
        }
        
    } catch(Exception $e) {
        writeLog("  ❌ EXCEPCIÓN: " . $e->getMessage());
        $errores++;
        continue;
    }
}

writeLog("=== FIN SINCRO - Total nuevas: $totalNuevas, Errores: $errores ===\n");