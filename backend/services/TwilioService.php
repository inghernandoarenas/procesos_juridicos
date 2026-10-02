<?php
/**
 * TwilioService.php
 * Envía notificaciones por SMS y/o WhatsApp vía Twilio (https://www.twilio.com).
 * Reemplaza por completo a WhatsAppService.php (CallMeBot) — CallMeBot no
 * pudo enviar al número de prueba porque WhatsApp le entregó un
 * identificador "@lid" en vez del número real, algo que CallMeBot no
 * soporta. Twilio no tiene ese problema para SMS (solo necesita el número
 * normal). Para WhatsApp, Twilio usa la misma API oficial de Meta por
 * debajo, así que aplican las mismas reglas de WhatsApp Business:
 *
 *   - SMS: funciona de inmediato. En cuenta trial, solo a números
 *     verificados en el Console (Phone Numbers → Verified Caller IDs).
 *     Al pasar a cuenta de pago, a cualquier número, sin fricción.
 *
 *   - WhatsApp: en cuenta trial usa el "Sandbox" de Twilio — CADA
 *     destinatario debe mandarle un "join <código>" por WhatsApp al número
 *     sandbox UNA VEZ (código visible en Twilio Console → Messaging →
 *     Try it out → Send a WhatsApp message), y esa activación expira si no
 *     hay actividad. Para producción real (sin ese paso) se necesita un
 *     WhatsApp Sender propio aprobado por Meta vía Twilio, con plantillas
 *     de mensaje aprobadas — igual de laborioso que lo que ya intentamos
 *     con Meta directamente, solo que Twilio hace de intermediario.
 *
 * Mismo patrón de logging que EmailService.php: si algo falla, se registra
 * el motivo real (logs/sms.log y logs/whatsapp.log), nunca falla en silencio.
 */
class TwilioService {

    // ─────────────────────────────────────────────
    // CONFIGURA AQUÍ TUS DATOS DE TWILIO
    // (Twilio Console → pantalla principal, arriba)
    // ─────────────────────────────────────────────
    private $accountSid        = 'AC5252d4436bd739cd061f5dad9ef834a8';
    private $authToken         = '7bea3f810e5738d45a400681611e0e4b';
    private $numeroSms         = '+17372508034';
    private $numeroWhatsapp    = 'PEGA_AQUI_TU_NUMERO_SANDBOX_WA';   // ej: +14155238886 — Console → Messaging → Try it out → Send a WhatsApp message
    // ─────────────────────────────────────────────

    private $logFileSms;
    private $logFileWhatsapp;

    public function __construct() {
        $this->logFileSms      = __DIR__ . '/../../logs/sms.log';
        $this->logFileWhatsapp = __DIR__ . '/../../logs/whatsapp.log';
    }

    public function enviarSms(string $telefono, string $mensaje): bool {
        if (str_contains($this->numeroSms, 'PEGA_AQUI')) {
            $this->log($this->logFileSms, $telefono, 'FALLIDO', 'TwilioService.php: falta configurar $numeroSms');
            return false;
        }
        return $this->enviarMensaje($telefono, $mensaje, $this->numeroSms, false, $this->logFileSms);
    }

    public function enviarWhatsapp(string $telefono, string $mensaje): bool {
        if (str_contains($this->numeroWhatsapp, 'PEGA_AQUI')) {
            $this->log($this->logFileWhatsapp, $telefono, 'FALLIDO', 'TwilioService.php: falta configurar $numeroWhatsapp');
            return false;
        }
        return $this->enviarMensaje($telefono, $mensaje, $this->numeroWhatsapp, true, $this->logFileWhatsapp);
    }

    private function enviarMensaje(string $telefono, string $mensaje, string $numeroOrigen, bool $esWhatsapp, string $logFile): bool {
        $numero = $this->normalizarTelefono($telefono);
        if (!$numero) {
            $this->log($logFile, $telefono, 'FALLIDO', 'Número de teléfono vacío o con formato inválido');
            return false;
        }

        // Twilio limita el mensaje a 1600 caracteres (se parte en varios
        // segmentos y cobra por cada uno) — recortamos para no disparar
        // costos innecesarios con mensajes largos.
        $texto  = $this->truncar($mensaje, 600);
        $prefijo = $esWhatsapp ? 'whatsapp:' : '';

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'To'   => $prefijo . $numero,
            'From' => $prefijo . $numeroOrigen,
            'Body' => $texto,
        ]));
        curl_setopt($ch, CURLOPT_USERPWD, $this->accountSid . ':' . $this->authToken);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->log($logFile, $numero, 'FALLIDO', "Error de red: $error");
            return false;
        }

        $data = json_decode($response, true);

        // Twilio responde 201 Created con el SID del mensaje cuando lo acepta
        // (aceptado ≠ entregado, pero para esto nos basta).
        if ($httpCode === 201 && isset($data['sid'])) {
            $this->log($logFile, $numero, 'ENVIADO', 'sid=' . $data['sid'] . ' status=' . ($data['status'] ?? '?'));
            return true;
        }

        // Twilio devuelve el motivo exacto en 'message' (ej: número no
        // verificado en modo trial, destinatario no se unió al sandbox de
        // WhatsApp, token inválido, etc.) — se registra completo.
        $motivo = $data['message'] ?? "HTTP {$httpCode}: " . substr((string)$response, 0, 300);
        $this->log($logFile, $numero, 'FALLIDO', $motivo);
        return false;
    }

    /**
     * Twilio exige formato E.164 (+indicativo+número, sin espacios).
     * Si viene sin indicativo (10 dígitos), asume Colombia (+57).
     */
    private function normalizarTelefono(string $telefono): ?string {
        $limpio = preg_replace('/[^0-9]/', '', $telefono);
        if (!$limpio) return null;
        if (strlen($limpio) === 10) {
            $limpio = '57' . $limpio;
        }
        return '+' . $limpio;
    }

    private function truncar(string $mensaje, int $limite): string {
        return mb_strlen($mensaje) > $limite ? mb_substr($mensaje, 0, $limite - 3) . '...' : $mensaje;
    }

    private function log(string $logFile, string $telefono, string $estado, string $detalle): void {
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0777, true);
        }
        $linea = '[' . date('Y-m-d H:i:s') . "] Para: $telefono | Estado: $estado | $detalle\n";
        file_put_contents($logFile, $linea, FILE_APPEND);
    }
}
?>