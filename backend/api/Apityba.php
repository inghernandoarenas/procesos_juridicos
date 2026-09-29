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


    /**
     * Consulta los anexos de un proceso en TYBA a través del servicio Node.js
     * 
     * @param string $radicado El número de radicado del proceso
     * @return array|null Retorna un array con los anexos o null si hay error de conexión
     */
    public function consultarAnexosPorRadicado($radicado) {
        $url = 'http://localhost:3001/tyba/anexos';
        
        $data = [
            'radicado' => trim($radicado)
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ]);
        
        // Aumentamos el timeout porque ahora el servicio Node descarga el
        // binario de cada PDF (antes solo raspaba una tabla vacía) — puede
        // tardar más si el proceso tiene varios archivos adjuntos.
        curl_setopt($ch, CURLOPT_TIMEOUT, 120); 

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // 1. Validar errores de red
        if ($error) {
            error_log("Error cURL en ApiTyba (Anexos): " . $error);
            return null;
        }

        // 2. Validar respuesta del servidor Node
        if ($httpCode !== 200) {
            error_log("Error HTTP en ApiTyba (Anexos): Código " . $httpCode . " - Respuesta: " . $response);
            return null;
        }

        // 3. Decodificar la respuesta
        $result = json_decode($response, true);
        
        // El servicio Node devuelve: { "anexos": [{nombre, tipo, contenido_base64}, ...] }
        // o { "error": "..." }. Anexo::insertarLoteTyba se encarga de decodificar
        // y guardar cada archivo en uploads/.
        if (isset($result['anexos']) && is_array($result['anexos'])) {
            return $result['anexos'];
        }

        // Si no hay anexos o vino un mensaje de "No encontrado", retornamos array vacío
        return [];
    }
}
?>