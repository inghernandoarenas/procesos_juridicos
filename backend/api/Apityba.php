<?php
/**
 * ApiTyba
 * Consulta actuaciones en Justicia XXI Web (TYBA) via servicio Node.js/Playwright.
 */
class ApiTyba {

    private string $serviceUrl = 'http://127.0.0.1:3001';
    private int    $timeout    = 90;
    private string $logFile;

    public function __construct() {
        $this->logFile = __DIR__ . '/../../logs/tyba_sync.log';
    }

    private function log(string $msg): void {
        file_put_contents($this->logFile, '[' . date('H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
    }

    public function consultarActuacionesPorRadicado(string $radicado): ?array {
        $this->log("Consultando TYBA: $radicado");

        // Verificar servicio Node
        $health = @file_get_contents($this->serviceUrl . '/health', false,
            stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]));
        if ($health === false) {
            $this->log("ERROR: servicio Node no disponible");
            return null;
        }

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
        $raw = @file_get_contents($this->serviceUrl . '/tyba/actuaciones', false, $ctx);
        $ms  = round((microtime(true) - $t0) * 1000);

        if ($raw === false) {
            $this->log("ERROR: timeout ({$ms}ms)");
            return null;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $this->log("ERROR: respuesta inválida");
            return null;
        }
        if (isset($data['error'])) {
            $this->log("ERROR servicio: " . $data['error']);
            return null;
        }

        $actuaciones = $data['actuaciones'] ?? [];
        $this->log("✓ " . count($actuaciones) . " actuaciones ({$ms}ms)");
        return $actuaciones;
    }
}
?>