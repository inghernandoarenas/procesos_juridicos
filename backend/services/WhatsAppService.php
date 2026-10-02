<?php
/**
 * WhatsAppService.php
 * Envía mensajes de WhatsApp vía CallMeBot (https://www.callmebot.com).
 *
 * Servicio gratuito de un solo sentido (no es un bot conversacional, solo
 * envía). Cada destinatario debe activarse UNA VEZ: agregar el número de
 * CallMeBot a sus contactos (el número vigente está en su página oficial,
 * cambia de vez en cuando) y mandarle por WhatsApp el mensaje:
 *   "I allow callmebot to send me messages"
 * El bot responde con un apikey personal, que se guarda en
 * notificaciones_config.apikey_whatsapp (uno por destinatario).
 *
 * Mismo patrón de logging que EmailService.php: si algo falla, se registra
 * el motivo real en logs/whatsapp.log, nunca falla en silencio.
 */
class WhatsAppService {

    private $endpoint = 'https://api.callmebot.com/whatsapp.php';
    private $logFile;

    // CallMeBot es un servicio gratuito no oficial, sin límite documentado,
    // pero en la práctica corta con "too many requests" si se le manda varios
    // mensajes seguidos sin pausa (lo que pasa cuando una sincronización trae
    // varias actuaciones nuevas de una vez). Throttle simple + reintento.
    private static $ultimoEnvio = 0;
    private $pausaMinimaSegundos = 5;

    public function __construct() {
        $this->logFile = __DIR__ . '/../../logs/whatsapp.log';
    }

    /**
     * Envía $mensaje al $telefono indicado usando el $apikey de ESE destinatario.
     * $apikey viene de notificaciones_config.apikey_whatsapp (NotificacionService
     * lo saca de ahí y lo pasa aquí).
     *
     * $telefonoCallMeBot (opcional): algunas cuentas de WhatsApp reciben de
     * CallMeBot un identificador especial tipo "208765928345845@lid" en vez
     * del número normal — el apikey queda atado a ESE valor exacto, no al
     * teléfono real. Si viene, se usa tal cual (sin tocarlo); si no, se cae
     * al teléfono normal.
     */
    public function enviar(string $telefono, string $mensaje, ?string $apikey = null, ?string $telefonoCallMeBot = null): bool {
        $numero = !empty($telefonoCallMeBot)
            ? trim($telefonoCallMeBot)           // tal cual lo dio CallMeBot — puede traer "@lid"
            : $this->normalizarTelefono($telefono);

        if (!$numero) {
            $this->log($telefono, 'FALLIDO', 'Número de teléfono vacío o con formato inválido');
            return false;
        }

        if (empty($apikey)) {
            $this->log($numero, 'FALLIDO', 'Falta el apikey de CallMeBot para este destinatario (configúralo en Parametrización → Notificaciones)');
            return false;
        }

        $intentosMax = 3;
        for ($intento = 1; $intento <= $intentosMax; $intento++) {
            $this->esperarTurno();

            [$ok, $respuesta] = $this->llamarApi($numero, $mensaje, $apikey);
            self::$ultimoEnvio = time();

            if ($ok) {
                $this->log($numero, 'ENVIADO', trim(strip_tags($respuesta)));
                return true;
            }

            $esRateLimit = stripos($respuesta, 'too many') !== false || stripos($respuesta, 'too often') !== false;
            if (!$esRateLimit) {
                // Falla permanente (apikey inválido, número no activado, etc.) — no sirve reintentar
                $this->log($numero, 'FALLIDO', trim(strip_tags($respuesta)) ?: 'Respuesta vacía de CallMeBot');
                return false;
            }

            if ($intento < $intentosMax) {
                $espera = 10 * $intento; // 10s, luego 20s
                $this->log($numero, 'REINTENTO', "Rate limit de CallMeBot, esperando {$espera}s (intento $intento/$intentosMax)");
                sleep($espera);
            }
        }

        $this->log($numero, 'FALLIDO', "Rate limit de CallMeBot persistente tras $intentosMax intentos");
        return false;
    }

    /** Deja pasar al menos $pausaMinimaSegundos desde el último envío de esta clase. */
    private function esperarTurno(): void {
        $transcurrido = time() - self::$ultimoEnvio;
        if (self::$ultimoEnvio > 0 && $transcurrido < $this->pausaMinimaSegundos) {
            sleep($this->pausaMinimaSegundos - $transcurrido);
        }
    }

    /** @return array{0: bool, 1: string} [ok, textoRespuesta] */
    private function llamarApi(string $numero, string $mensaje, string $apikey): array {
        $url = $this->endpoint . '?' . http_build_query([
            'phone'  => $numero,
            'text'   => $mensaje,
            'apikey' => $apikey,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [false, "Error de red: $error"];
        }

        // CallMeBot responde 200 con texto plano "Message queued..." si salió
        // bien, o un mensaje de error (apikey inválido, rate limit, etc.)
        // también con 200 — hay que revisar el contenido, no solo el código HTTP.
        $ok = $httpCode === 200 && stripos((string)$response, 'queued') !== false;
        return [$ok, (string)$response ?: "HTTP $httpCode sin cuerpo de respuesta"];
    }

    /**
     * CallMeBot espera el número con indicativo de país, sin '+' ni espacios.
     * Si viene sin indicativo (10 dígitos), asume Colombia (57).
     */
    private function normalizarTelefono(string $telefono): ?string {
        $limpio = preg_replace('/\D/', '', $telefono);
        if (!$limpio) return null;
        if (strlen($limpio) === 10) {
            $limpio = '57' . $limpio;
        }
        return $limpio;
    }

    private function log(string $telefono, string $estado, string $detalle): void {
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0777, true);
        }
        $linea = '[' . date('Y-m-d H:i:s') . "] Para: $telefono | Estado: $estado | $detalle\n";
        file_put_contents($this->logFile, $linea, FILE_APPEND);
    }
}
?>