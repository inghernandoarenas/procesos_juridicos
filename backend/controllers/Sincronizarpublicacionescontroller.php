<?php
set_time_limit(300);

require_once __DIR__ . '/../api/ApiPublicaciones.php';
require_once __DIR__ . '/../models/Actuacion.php';
require_once __DIR__ . '/../models/Proceso.php';
require_once __DIR__ . '/../libs/auth.php';

header('Content-Type: application/json');

// Verificar token
verificarToken();

$procesoModel   = new Proceso();
$actuacionModel = new Actuacion();
$api            = new ApiPublicaciones();

$logFile = __DIR__ . '/../../logs/publicaciones_sync.log';
$ts      = date('Y-m-d H:i:s');
file_put_contents($logFile, "\n[$ts] === INICIO SYNC PUBLICACIONES ===\n", FILE_APPEND);

// ── Rango: últimos 7 días ─────────────────────────────────────
$fechaFin    = date('Y-m-d');
$fechaInicio = date('Y-m-d', strtotime('-7 days'));

// ── Procesos activos con despacho oficial ─────────────────────
$procesos = $procesoModel->getActivosConDespacho();

if (empty($procesos)) {
    echo json_encode([
        'success' => true,
        'message' => 'No hay procesos con despacho oficial asignado. Asigna un despacho a tus procesos para consultar publicaciones.',
        'insertadas' => 0,
    ]);
    exit;
}

file_put_contents($logFile, "Procesos: " . count($procesos) . " | Rango: $fechaInicio → $fechaFin\n", FILE_APPEND);

// Agrupar por codigo_oficial para no repetir consultas
$despachos = [];
foreach ($procesos as $p) {
    $cod = $p['codigo_oficial'];
    if (!isset($despachos[$cod])) {
        $despachos[$cod] = ['codigo' => $cod, 'nombre' => $p['despacho_nombre'], 'procesos' => []];
    }
    $despachos[$cod]['procesos'][] = $p;
}

$totalDespachos = count($despachos);
file_put_contents($logFile, "Despachos únicos: $totalDespachos\n", FILE_APPEND);

// ── Respuesta inmediata ───────────────────────────────────────
echo json_encode([
    'success' => true,
    'message' => "Consultando publicaciones de $totalDespachos despacho(s) — últimos 7 días. Revisa el timeline en ~30 segundos.",
    'despachos' => $totalDespachos,
]);

// Flush sin romper si no hay buffer
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    while (ob_get_level() > 0) { ob_end_flush(); }
    flush();
}

// ── Consultar y procesar en background ───────────────────────
$totalInsertadas = 0;
$errores = 0;

foreach ($despachos as $cod => $info) {
    $publicaciones = $api->consultarPorDespacho($cod, $fechaInicio, $fechaFin);

    if ($publicaciones === null) {
        $errores++;
        file_put_contents($logFile, "  ERROR despacho $cod\n", FILE_APPEND);
        continue;
    }

    if (empty($publicaciones)) {
        file_put_contents($logFile, "  Sin publicaciones: $cod\n", FILE_APPEND);
        continue;
    }

    file_put_contents($logFile, "  Despacho $cod — " . count($publicaciones) . " publicaciones\n", FILE_APPEND);

    foreach ($info['procesos'] as $proceso) {
        $lote = [];
        foreach ($publicaciones as $pub) {
            $idApi = 'pub_' . substr(md5($proceso['id'] . $pub['fecha'] . $pub['titulo']), 0, 16);
            $obs   = implode(' | ', array_filter([
                !empty($pub['tipo'])         ? 'Tipo: '         . $pub['tipo']         : '',
                !empty($pub['especialidad']) ? 'Especialidad: ' . $pub['especialidad'] : '',
                !empty($pub['municipio'])    ? 'Municipio: '    . $pub['municipio']    : '',
            ]));
            $lote[] = [
                'id_api'        => $idApi,
                'despacho'      => $pub['despacho'] ?: $info['nombre'],
                'fecha'         => $pub['fecha'],
                'actuacion'     => $pub['titulo'],
                'observaciones' => $obs ?: null,
            ];
        }

        $insertadas = $actuacionModel->insertarLote($lote, (int)$proceso['id'], 'publicaciones');
        $totalInsertadas += count($insertadas);

        if (count($insertadas) > 0) {
            file_put_contents($logFile,
                "    Proceso {$proceso['numero_radicado']} — " . count($insertadas) . " nuevas\n",
                FILE_APPEND);
        }
    }
}

file_put_contents($logFile,
    "[" . date('H:i:s') . "] FIN — insertadas: $totalInsertadas | errores: $errores\n",
    FILE_APPEND);
?>