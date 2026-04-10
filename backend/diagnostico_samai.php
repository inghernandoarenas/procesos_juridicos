<?php
/**
 * DIAGNÓSTICO SAMAI v2 — prueba múltiples formatos de payload
 * URL: /procesos_juridicos/backend/diagnostico_samai.php?radicado=08001333300320230008000
 * BORRAR después de usar.
 */
$radicado = $_GET['radicado'] ?? '08001333300320230008000';
$baseUrl  = 'https://samai.consejodeestado.gov.co';

function hit($url, $method, $body, $contentType) {
    $ctx = stream_context_create([
        'http' => [
            'method'        => $method,
            'timeout'       => 15,
            'ignore_errors' => true,
            'header'        => implode("\r\n", [
                "Content-Type: $contentType",
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Accept-Language: es-CO,es;q=0.9,en;q=0.8',
                'Referer: https://samai.consejodeestado.gov.co/Vistas/Casos/list_procesos.aspx',
                'Origin: https://samai.consejodeestado.gov.co',
                'X-Requested-With: XMLHttpRequest',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120',
            ]),
            'content' => $body,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $t0  = microtime(true);
    $res = @file_get_contents($url, false, $ctx);
    $ms  = round((microtime(true) - $t0) * 1000);
    return ['body' => $res, 'ms' => $ms, 'len' => strlen($res ?? '')];
}

echo "<pre style='font-size:12px;background:#1a1a2e;color:#eee;padding:20px;line-height:1.6'>";
echo "<b style='color:#f8c471;font-size:14px'>DIAGNÓSTICO SAMAI v2 — radicado: $radicado</b>\n\n";

$url = $baseUrl . '/Vistas/Casos/Jprocesos.ashx/listaprocesosdata';

// Prueba 1: JSON con "numero"
echo "<b style='color:#85c1e9'>── Prueba 1: JSON {\"numero\": radicado}</b>\n";
$r = hit($url, 'POST', json_encode(['numero' => $radicado]), 'application/json; charset=utf-8');
echo "  {$r['ms']}ms — {$r['len']} bytes\n";
echo "  Respuesta: " . substr($r['body'], 0, 300) . "\n\n";

// Prueba 2: JSON con "Numero"
echo "<b style='color:#85c1e9'>── Prueba 2: JSON {\"Numero\": radicado}</b>\n";
$r = hit($url, 'POST', json_encode(['Numero' => $radicado]), 'application/json; charset=utf-8');
echo "  {$r['ms']}ms — {$r['len']} bytes\n";
echo "  Respuesta: " . substr($r['body'], 0, 300) . "\n\n";

// Prueba 3: JSON con "numeroRadicado"
echo "<b style='color:#85c1e9'>── Prueba 3: JSON {\"numeroRadicado\": radicado}</b>\n";
$r = hit($url, 'POST', json_encode(['numeroRadicado' => $radicado]), 'application/json; charset=utf-8');
echo "  {$r['ms']}ms — {$r['len']} bytes\n";
echo "  Respuesta: " . substr($r['body'], 0, 300) . "\n\n";

// Prueba 4: Form-encoded
echo "<b style='color:#85c1e9'>── Prueba 4: form-urlencoded numero=radicado</b>\n";
$r = hit($url, 'POST', 'numero=' . urlencode($radicado), 'application/x-www-form-urlencoded');
echo "  {$r['ms']}ms — {$r['len']} bytes\n";
echo "  Respuesta: " . substr($r['body'], 0, 300) . "\n\n";

// Prueba 5: form-encoded NumeroRadicado
echo "<b style='color:#85c1e9'>── Prueba 5: form-urlencoded NumeroRadicado=radicado</b>\n";
$r = hit($url, 'POST', 'NumeroRadicado=' . urlencode($radicado), 'application/x-www-form-urlencoded');
echo "  {$r['ms']}ms — {$r['len']} bytes\n";
echo "  Respuesta: " . substr($r['body'], 0, 300) . "\n\n";

// Prueba 6: JSON objeto envuelto {"busqueda": {...}}
echo "<b style='color:#85c1e9'>── Prueba 6: JSON envuelto {\"busqueda\":{\"numero\":radicado}}</b>\n";
$r = hit($url, 'POST', json_encode(['busqueda' => ['numero' => $radicado]]), 'application/json; charset=utf-8');
echo "  {$r['ms']}ms — {$r['len']} bytes\n";
echo "  Respuesta: " . substr($r['body'], 0, 300) . "\n\n";

// Prueba 7: GET con query string
$urlGet = $url . '?numero=' . urlencode($radicado);
echo "<b style='color:#85c1e9'>── Prueba 7: GET con ?numero=radicado</b>\n";
$ctx = stream_context_create([
    'http' => ['method' => 'GET', 'timeout' => 15, 'ignore_errors' => true,
        'header' => "Accept: application/json\r\nX-Requested-With: XMLHttpRequest\r\nReferer: $baseUrl/Vistas/Casos/list_procesos.aspx"],
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
]);
$t0 = microtime(true);
$res = @file_get_contents($urlGet, false, $ctx);
$ms  = round((microtime(true) - $t0) * 1000);
echo "  {$ms}ms — " . strlen($res ?? '') . " bytes\n";
echo "  Respuesta: " . substr($res ?? '', 0, 300) . "\n\n";

echo "<b style='color:#ff6b6b'>⚠ BORRAR este archivo después de leer</b>\n</pre>";
?>