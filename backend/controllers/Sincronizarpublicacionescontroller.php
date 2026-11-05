<?php
set_time_limit(600); // hasta 10 min para N despachos + PDFs

require_once __DIR__ . '/../api/ApiPublicaciones.php';
require_once __DIR__ . '/../models/Actuacion.php';
require_once __DIR__ . '/../models/Proceso.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/auth.php';

header('Content-Type: application/json');
verificarToken();

$procesoModel   = new Proceso();
$actuacionModel = new Actuacion();
$api            = new ApiPublicaciones();
$db             = (new Database())->getConnection();

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
        'message' => 'No hay procesos con despacho oficial asignado.',
        'insertadas' => 0,
    ]);
    exit;
}

// Agrupar por codigo_oficial
$despachos = [];
foreach ($procesos as $p) {
    $cod = $p['codigo_oficial'];
    if (!isset($despachos[$cod])) {
        $despachos[$cod] = ['codigo' => $cod, 'nombre' => $p['despacho_nombre'], 'procesos' => []];
    }
    $despachos[$cod]['procesos'][] = $p;
}

$totalDespachos = count($despachos);
file_put_contents($logFile, "Procesos: " . count($procesos) . " | Despachos: $totalDespachos | Rango: $fechaInicio → $fechaFin\n", FILE_APPEND);

// ── Respuesta inmediata ───────────────────────────────────────
echo json_encode([
    'success'   => true,
    'message'   => "Sincronizando $totalDespachos despacho(s) — últimos 7 días. Los documentos se descargarán en segundo plano.",
    'despachos' => $totalDespachos,
]);

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    while (ob_get_level() > 0) ob_end_flush();
    flush();
}

// ── Directorio de uploads para PDFs de publicaciones ─────────
$uploadsDir = __DIR__ . '/../../uploads/publicaciones/';
if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

// ── Procesar cada despacho ────────────────────────────────────
$totalInsertadas = 0;
$totalPdfs       = 0;
$errores         = 0;

foreach ($despachos as $cod => $info) {
    $publicaciones = $api->consultarPorDespacho($cod, $fechaInicio, $fechaFin);

    if ($publicaciones === null) {
        $errores++;
        file_put_contents($logFile, "  ERROR conectando despacho $cod\n", FILE_APPEND);
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
                '_articleId'    => $pub['articleId'] ?? null,
            ];
        }

        // Limpiar campos internos antes de insertar
        $loteDB = array_map(fn($p) => array_diff_key($p, ['_articleId' => '']), $lote);
        $insertadas = $actuacionModel->insertarLote($loteDB, (int)$proceso['id'], 'publicaciones');
        $totalInsertadas += count($insertadas);

        if (empty($insertadas)) continue;

        file_put_contents($logFile,
            "    Proceso {$proceso['numero_radicado']} — " . count($insertadas) . " nuevas\n",
            FILE_APPEND);

        // ── Descargar PDFs de cada publicación nueva ──────────
        // Construir set de id_api insertadas para lookup rápido
        $idsInsertados = array_flip(array_column($insertadas, 'id_api'));

        foreach ($lote as $pub) {
            if (empty($pub['_articleId'])) continue;

            // Solo descargar si la actuación fue insertada (nueva)
            if (!isset($idsInsertados[$pub['id_api']])) continue;

            // Obtener links de PDFs via Node.js
            $pdfData = obtenerPdfs($pub['_articleId']);
            if (empty($pdfData['pdfs'])) continue;

            // Obtener el actuacion_id recién insertada
            $stmtAct = $db->prepare("SELECT id FROM actuaciones WHERE id_api = :id_api AND proceso_id = :pid LIMIT 1");
            $stmtAct->execute([':id_api' => $pub['id_api'], ':pid' => $proceso['id']]);
            $actRow = $stmtAct->fetch(PDO::FETCH_ASSOC);
            if (!$actRow) continue;

            foreach ($pdfData['pdfs'] as $pdf) {
                $nombreArchivo = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $pdf['nombre']);
                if (!str_ends_with(strtolower($nombreArchivo), '.pdf')) $nombreArchivo .= '.pdf';
                $rutaLocal = $uploadsDir . $nombreArchivo;

                // Descargar el PDF
                $contenido = descargarArchivo($pdf['url']);
                if (!$contenido) {
                    file_put_contents($logFile, "      PDF no descargado: {$pdf['nombre']}\n", FILE_APPEND);
                    continue;
                }

                file_put_contents($rutaLocal, $contenido);
                $totalPdfs++;

                // Registrar en tabla anexos
                $rutaRelativa = 'uploads/publicaciones/' . $nombreArchivo;
                $stmtAnexo = $db->prepare("
                    INSERT IGNORE INTO anexos
                        (proceso_id, nombre_archivo, ruta_archivo, tipo_archivo, fecha_subida, descripcion)
                    VALUES
                        (:proceso_id, :nombre, :ruta, 'application/pdf', NOW(), :desc)
                ");
                $stmtAnexo->execute([
                    ':proceso_id' => $proceso['id'],
                    ':nombre'     => $pdf['nombre'],
                    ':ruta'       => $rutaRelativa,
                    ':desc'       => 'Publicación: ' . $pub['actuacion'],
                ]);

                file_put_contents($logFile, "      PDF guardado: $nombreArchivo\n", FILE_APPEND);
            }
        }
    }
}

file_put_contents($logFile,
    "[" . date('H:i:s') . "] FIN — actuaciones: $totalInsertadas | PDFs: $totalPdfs | errores: $errores\n",
    FILE_APPEND);

// ── Helpers ───────────────────────────────────────────────────
function obtenerPdfs(string $articleId): array {
    $ctx = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'timeout'       => 30,
            'ignore_errors' => true,
            'header'        => "Content-Type: application/json\r\n",
            'content'       => json_encode(['article_id' => $articleId]),
        ],
    ]);
    $raw = @file_get_contents('http://127.0.0.1:3001/publicaciones/detalle', false, $ctx);
    if (!$raw) return ['pdfs' => []];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : ['pdfs' => []];
}

function descargarArchivo(string $url): ?string {
    $ctx = stream_context_create([
        'http' => [
            'method'        => 'GET',
            'timeout'       => 30,
            'ignore_errors' => true,
            'header'        => "User-Agent: Mozilla/5.0\r\n",
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $data = @file_get_contents($url, false, $ctx);
    return ($data && strlen($data) > 100) ? $data : null;
}
?>