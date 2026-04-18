<?php
/**
 * Prueba la API REST de SAMAI en /backend/api/v1/
 * URL: /procesos_juridicos/backend/diagnostico_samai_api.php
 * BORRAR después de usar.
 */
$radicado = $_GET['radicado'] ?? '08001333300320230008000';
$baseUrl  = 'https://samai.consejodeestado.gov.co';

function hit($url, $method = 'GET', $body = null, $headers = []) {
    $defaultHeaders = [
        'Accept: application/json',
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120',
    ];
    $ctx = stream_context_create([
        'http' => [
            'method'        => $method,
            'timeout'       => 10,
            'ignore_errors' => true,
            'header'        => implode("\r\n", array_merge($defaultHeaders, $headers)),
            'content'       => $body,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $t0  = microtime(true);
    $res = @file_get_contents($url, false, $ctx);
    $ms  = round((microtime(true) - $t0) * 1000);
    $code = '';
    foreach ($http_response_header ?? [] as $h) {
        if (str_starts_with($h, 'HTTP/')) $code = $h;
    }
    return ['body' => $res ?: '', 'ms' => $ms, 'code' => $code, 'len' => strlen($res ?: '')];
}

echo "<pre style='font-size:12px;background:#1a1a2e;color:#eee;padding:20px;line-height:1.8'>";
echo "<b style='color:#f8c471;font-size:14px'>DIAGNÓSTICO API REST SAMAI — radicado: $radicado</b>\n\n";

// ── Prueba 1: /backend/api/v1/procesos ─────────────────────────
echo "<b style='color:#85c1e9'>── 1. GET /backend/api/v1/procesos?numeroRadicacion=</b>\n";
$r = hit("$baseUrl/backend/api/v1/procesos?numeroRadicacion=$radicado");
echo "  {$r['code']} — {$r['len']}b — {$r['ms']}ms\n";
echo "  " . substr($r['body'], 0, 300) . "\n\n";

// ── Prueba 2: variante con numero= ──────────────────────────────
echo "<b style='color:#85c1e9'>── 2. GET /backend/api/v1/procesos?numero=</b>\n";
$r = hit("$baseUrl/backend/api/v1/procesos?numero=$radicado");
echo "  {$r['code']} — {$r['len']}b\n";
echo "  " . substr($r['body'], 0, 200) . "\n\n";

// ── Prueba 3: /api/v1/ (sin backend) ───────────────────────────
echo "<b style='color:#85c1e9'>── 3. GET /api/v1/procesos?numeroRadicacion=</b>\n";
$r = hit("$baseUrl/api/v1/procesos?numeroRadicacion=$radicado");
echo "  {$r['code']} — {$r['len']}b\n";
echo "  " . substr($r['body'], 0, 200) . "\n\n";

// ── Prueba 4: /api/Proceso/Buscar ───────────────────────────────
echo "<b style='color:#85c1e9'>── 4. GET /api/Proceso/Buscar?radicado=</b>\n";
$r = hit("$baseUrl/api/Proceso/Buscar?radicado=$radicado");
echo "  {$r['code']} — {$r['len']}b\n";
echo "  " . substr($r['body'], 0, 200) . "\n\n";

// Si alguna de las anteriores dio 200 con JSON, buscar el ID
$id = null;
$urls_200 = [];

$tests = [
    "$baseUrl/backend/api/v1/procesos?numeroRadicacion=$radicado",
    "$baseUrl/backend/api/v1/procesos?numero=$radicado",
    "$baseUrl/api/v1/procesos?numeroRadicacion=$radicado",
];

foreach ($tests as $url) {
    $r = hit($url);
    $data = json_decode($r['body'], true);
    if (is_array($data) && !empty($data)) {
        $id = $data[0]['id'] ?? $data[0]['ID'] ?? null;
        if ($id) {
            echo "<b style='color:#2ecc71'>PROCESO ENCONTRADO — ID: $id</b>\n";
            echo json_encode($data[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
            break;
        }
    }
}

// ── Prueba 5: actuaciones si tenemos ID ────────────────────────
if ($id) {
    echo "<b style='color:#85c1e9'>── 5. GET actuaciones del proceso ID=$id</b>\n";
    $endpoints = [
        "/backend/api/v1/procesos/$id/actuaciones",
        "/api/v1/procesos/$id/actuaciones",
        "/api/Proceso/Actuaciones/$id",
    ];
    foreach ($endpoints as $ep) {
        $r = hit($baseUrl . $ep);
        echo "  $ep → {$r['code']} — {$r['len']}b\n";
        if ($r['len'] > 10 && str_contains($r['body'], '[')) {
            echo "  " . substr($r['body'], 0, 400) . "\n";
        }
    }
} else {
    echo "<b style='color:#e74c3c'>No se encontró ID del proceso — API REST no disponible en esas rutas</b>\n";
    echo "Probando rutas adicionales:\n";
    $extra = [
        "/backend/api/procesos/$radicado",
        "/backend/api/v1/proceso/$radicado",
        "/Vistas/Casos/Jprocesos.ashx/procesosdata?radicado=$radicado",
    ];
    foreach ($extra as $ep) {
        $r = hit($baseUrl . $ep);
        echo "  $ep → {$r['code']} — {$r['len']}b — " . substr($r['body'], 0, 60) . "\n";
    }
}

echo "\n<b style='color:red'>BORRAR después de usar</b></pre>";
?>