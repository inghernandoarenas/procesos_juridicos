<?php
/**
 * ApiSamai.php
 * Delega la extracción de actuaciones al servicio Playwright (Node.js)
 * que corre en localhost:3001.
 *
 * La búsqueda del proceso (listaprocesosdata) sigue funcionando directo
 * porque ese endpoint no requiere sesión de navegador.
 */
class ApiSamai {

    private $timeout    = 90; // Playwright puede tardar ~30s
    private $baseUrl    = 'https://samai.consejodeestado.gov.co';
    private $serviceUrl = 'http://127.0.0.1:3001';
    private $logFile;

    public function __construct() {
        $logDir = __DIR__ . '/../../logs';
        if (!file_exists($logDir)) mkdir($logDir, 0777, true);
        $this->logFile = $logDir . '/samai_sync.log';
    }

    private function log($msg) {
        file_put_contents($this->logFile, '[' . date('H:i:s') . "] $msg\n", FILE_APPEND);
    }

    public function tieneCookies(): bool {
        return true; // Con Playwright no necesitamos cookies manuales
    }

    private function inferirCorporacion(string $radicado): string {
        return substr(preg_replace('/[^0-9]/', '', $radicado), 0, 7);
    }

    private function limpiarRadicado(string $r): string {
        return trim(ltrim(trim($r), "'\""));
    }

    private function extraerGuid(string $html): ?string {
        if (preg_match("/goprocs_gestion\('([^']+)','([^']+)'/", $html, $m))
            return $m[1] . $m[2];
        return null;
    }

    private function parsearDetallesHtml(string $html): array {
        $texto  = strip_tags(str_replace(['<br>','<br/>','<br />'], "\n", $html));
        $lineas = array_filter(array_map('trim', explode("\n", $texto)));
        $tipo = $demandante = $demandado = $ponente = '';
        foreach ($lineas as $l) {
            if (empty($tipo) && str_contains($l, 'Ingreso:'))
                $tipo = trim(explode('-', $l)[0]);
            if (str_contains($l, 'Ponente:'))
                $ponente    = trim(str_replace('Ponente:', '', $l));
            if (str_contains($l, 'Demandante:'))
                $demandante = trim(str_replace('Demandante:', '', $l));
            if (str_contains($l, 'Demandado:'))
                $demandado  = trim(str_replace('Demandado:', '', $l));
        }
        return compact('tipo', 'ponente', 'demandante', 'demandado');
    }

    /**
     * Busca el proceso en SAMAI (endpoint que sí funciona sin navegador).
     */
    public function buscarProceso(string $radicado): ?array {
        $this->log("=== SAMAI buscarProceso: $radicado ===");
        $corp = $this->inferirCorporacion($radicado);
        $url  = $this->baseUrl . '/Vistas/Casos/Jprocesos.ashx/listaprocesosdata';
        $payload = [
            'FW_tipobusqueda'   => 'FW_Rbtradicado',
            'FW_ppexacta'       => '',
            'FW_tipoarea'       => 'FW_RbtCorporacion',
            'FW_Txtcriterios'   => $radicado,
            'FW_LstCorporacion' => $corp,
            'FW_LstSeccion'     => '',
            'FW_LstPonente'     => '',
            'FW_FechaI'         => '',
            'FW_FechaF'         => '',
            'FW_LstcriterioV'   => '',
            'FW_LstcriterioP'   => '',
        ];

        $ctx = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'timeout'       => 20,
                'ignore_errors' => true,
                'header'        => implode("\r\n", [
                    'Content-Type: application/json; charset=UTF-8',
                    'Accept: application/json',
                    'Origin: '  . $this->baseUrl,
                    'Referer: ' . $this->baseUrl . '/Vistas/Casos/procesos.aspx',
                    'X-Requested-With: XMLHttpRequest',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120',
                ]),
                'content' => json_encode($payload),
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        $raw  = @file_get_contents($url, false, $ctx);
        $data = $raw ? json_decode($raw, true) : null;

        if (!is_array($data) || empty($data)) {
            // Reintentar sin corporación
            $payload['FW_LstCorporacion'] = '';
            $raw2 = @file_get_contents($url, false, stream_context_create([
                'http' => [
                    'method'        => 'POST',
                    'timeout'       => 20,
                    'ignore_errors' => true,
                    'header'        => implode("\r\n", [
                        'Content-Type: application/json; charset=UTF-8',
                        'Accept: application/json',
                        'X-Requested-With: XMLHttpRequest',
                    ]),
                    'content' => json_encode($payload),
                ],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ]));
            $data = $raw2 ? json_decode($raw2, true) : null;
        }

        if (!is_array($data)) { $this->log("  ✗ JSON inválido"); return null; }

        $this->log("  Registros: " . count($data));
        $result = [];
        foreach ($data as $item) {
            $det = $this->parsearDetallesHtml($item['DETALLES'] ?? '');
            preg_match("/goprocs_gestion\('[^']+','([^']+)'/", $item['ACCIONES'] ?? '', $mDesp);
            $result[] = [
                'radicado'    => $this->limpiarRadicado($item['RADICADO'] ?? ''),
                'guid'        => $this->extraerGuid($item['ACCIONES'] ?? ''),
                'cod_despacho'=> $mDesp[1] ?? $corp,
                'tipo'        => $det['tipo'],
                'ponente'     => $det['ponente'],
                'demandante'  => $det['demandante'],
                'demandado'   => $det['demandado'],
            ];
        }
        return $result;
    }

    /**
     * Llama al servicio Playwright para obtener las actuaciones.
     * El servicio abre un browser real, navega SAMAI y extrae la tabla.
     */
    private function llamarServicioPlaywright(string $radicado): ?array {
        $this->log("  → Llamando servicio Playwright en {$this->serviceUrl}...");
        $url = $this->serviceUrl . '/samai/actuaciones';

        // Verificar que el servicio está vivo antes de hacer el request largo
        $t0check = microtime(true);
        $health  = @file_get_contents($this->serviceUrl . '/health', false,
            stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]));
        $msCheck = round((microtime(true) - $t0check) * 1000);

        if ($health === false) {
            $this->log("  ✗ Servicio no responde en {$this->serviceUrl} ({$msCheck}ms)");
            $this->log("  Asegúrate de correr: node server.js en samai-service");
            return null;
        }
        $this->log("  Health OK ({$msCheck}ms) — iniciando consulta Playwright...");

        $ctx = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'timeout'       => $this->timeout,
                'ignore_errors' => true,
                'header'        => "Content-Type: application/json\r\n",
                'content'       => json_encode(['radicado' => $radicado]),
            ],
        ]);

        $t0  = microtime(true);
        $raw = @file_get_contents($url, false, $ctx);
        $ms  = round((microtime(true) - $t0) * 1000);

        if ($raw === false || $raw === null) {
            $this->log("  ✗ Timeout o error en la llamada ({$ms}ms)");
            return null;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $this->log("  ✗ Respuesta inválida: " . substr($raw, 0, 100));
            return null;
        }
        if (isset($data['error'])) {
            $this->log("  ✗ Error del servicio: " . $data['error']);
            return null;
        }

        $actuaciones = $data['actuaciones'] ?? [];
        $this->log("  ✓ Servicio devolvió " . count($actuaciones) . " actuaciones ({$ms}ms)");
        return $actuaciones;
    }

    /**
     * Método principal — busca proceso y trae actuaciones via Playwright.
     */
    public function consultarActuacionesPorRadicado(string $radicado): ?array {
        $this->log("=== SAMAI INICIO radicado=$radicado ===");

        // Verificar que el proceso existe en SAMAI (rápido, sin browser)
        $procesos = $this->buscarProceso($radicado);
        if ($procesos === null) {
            $this->log("  ✗ Error conectando con SAMAI");
            return null;
        }
        if (empty($procesos)) {
            $this->log("  Proceso no encontrado en SAMAI");
            return [];
        }

        $proc     = $procesos[0];
        $despacho = trim(($proc['tipo'] ?: 'SAMAI') . ($proc['demandante'] ? ' — ' . $proc['demandante'] : ''));

        // Obtener actuaciones via Playwright
        $rawActs = $this->llamarServicioPlaywright($radicado);
        if ($rawActs === null) {
            return null;
        }

        // Normalizar al formato que espera insertarLote
        $resultado = [];
        foreach ($rawActs as $i => $act) {
            // ID único: radicado + índice de fila + fecha — garantiza unicidad aunque el texto sea igual
            $rowIdx = $act['_rowIdx'] ?? $i;
            $idUnico = 'sm_' . substr(md5($radicado . '_' . $rowIdx . '_' . ($act['fecha'] ?? '')), 0, 16);
            $resultado[] = [
                'id'            => $idUnico,
                'fecha'         => $act['fecha'] ?? null,
                'actuacion'     => $act['actuacion'] ?? 'Sin descripción',
                'observaciones' => $act['observaciones'] ?? null,
                'despacho'      => $despacho,
            ];
        }

        $this->log("=== SAMAI FIN — " . count($resultado) . " actuaciones ===");
        return $resultado;
    }
}
?>