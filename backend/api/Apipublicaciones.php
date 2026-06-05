<?php
/**
 * ApiPublicaciones
 * Consulta el portal de Publicaciones Procesales via el servicio Node.js/Playwright.
 * El portal bloquea requests directos (403) — Playwright con browser real lo evita.
 */
class ApiPublicaciones {

    private string $serviceUrl = 'http://127.0.0.1:3001';
    private int    $timeout    = 60; // el browser puede tardar ~5-10s
    private string $logFile;

    public function __construct() {
        $this->logFile = __DIR__ . '/../../logs/publicaciones_sync.log';
    }

    private function log(string $msg): void {
        file_put_contents($this->logFile, '[' . date('H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
    }

    /**
     * Consulta publicaciones de un despacho en un rango de fechas.
     * Llama al servicio Node.js que usa Playwright para evitar el 403.
     *
     * @return array|null  null = error de conexión, [] = sin publicaciones
     */
    public function consultarPorDespacho(string $codigoDespacho, string $fechaInicio, string $fechaFin): ?array {
        $this->log("GET despacho=$codigoDespacho rango=$fechaInicio/$fechaFin");

        // Verificar que el servicio Node está corriendo
        $health = @file_get_contents($this->serviceUrl . '/health', false,
            stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]));

        if ($health === false) {
            $this->log("  ERROR: servicio Node.js no disponible en {$this->serviceUrl}");
            $this->log("  Asegúrate de correr: node server.js en la carpeta samai-service");
            return null;
        }

        // Llamar al endpoint de publicaciones
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'timeout'       => $this->timeout,
                'ignore_errors' => true,
                'header'        => "Content-Type: application/json\r\n",
                'content'       => json_encode([
                    'codigo_despacho' => $codigoDespacho,
                    'fecha_inicio'    => $fechaInicio,
                    'fecha_fin'       => $fechaFin,
                ]),
            ],
        ]);

        $t0  = microtime(true);
        $raw = @file_get_contents($this->serviceUrl . '/publicaciones/consultar', false, $ctx);
        $ms  = round((microtime(true) - $t0) * 1000);

        if ($raw === false) {
            $this->log("  ERROR: timeout o fallo en la llamada al servicio ({$ms}ms)");
            return null;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $this->log("  ERROR: respuesta inválida del servicio: " . substr($raw, 0, 100));
            return null;
        }

        if (isset($data['error'])) {
            $this->log("  ERROR del servicio: " . $data['error']);
            return null;
        }

        $publicaciones = $data['publicaciones'] ?? [];
        $this->log("  Encontradas: " . count($publicaciones) . " publicaciones ({$ms}ms)");
        return $publicaciones;
    }
}
?>