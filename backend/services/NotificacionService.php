<?php
require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/../models/NotificacionConfig.php';

// Twilio (SMS + WhatsApp) es opcional: solo se carga si el archivo existe.
if (file_exists(__DIR__ . '/TwilioService.php')) {
    require_once __DIR__ . '/TwilioService.php';
}

class NotificacionService {
    private $emailService;
    private $twilioService;
    private $notificacionModel;

    public function __construct() {
        $this->emailService      = new EmailService();
        $this->notificacionModel = new NotificacionConfig();

        // Twilio solo si está disponible
        $this->twilioService = class_exists('TwilioService')
            ? new TwilioService()
            : null;
    }

    public function notificarNuevaActuacion($proceso, $actuacion) {
        $destinatarios = $this->notificacionModel->getDestinatariosActivos();

        $asunto  = "Nueva actuación en proceso {$proceso['numero_radicado']}";
        $mensaje = "Se ha registrado una nueva actuación para el proceso {$proceso['numero_radicado']}.\n\n"
                 . "Actuación: {$actuacion['actuacion']}\n"
                 . "Fecha: " . date('d/m/Y', strtotime($actuacion['fecha'])) . "\n"
                 . "Observaciones: " . (!empty($actuacion['observaciones']) ? $actuacion['observaciones'] : 'Sin observaciones') . "\n\n"
                 . "Ingrese al sistema para más detalles: " . $this->getSistemaUrl();

        $resultados = [];

        foreach ($destinatarios as $dest) {
            // tipo: email | sms | whatsapp | todos
            $tipo = $dest['tipo'] ?? 'email';

            $quiereEmail    = in_array($tipo, ['email', 'todos']);
            $quiereSms      = in_array($tipo, ['sms', 'todos']);
            $quiereWhatsapp = in_array($tipo, ['whatsapp', 'todos']);

            // ── Email ──────────────────────────────────────────────────
            if ($quiereEmail && !empty($dest['email'])) {
                $ok = $this->emailService->enviar($dest['email'], $asunto, $mensaje);
                $this->registrarYAcumular($resultados, $proceso, $actuacion, $mensaje, 'email', $dest['email'], $ok);
            }

            // ── SMS ────────────────────────────────────────────────────
            if ($quiereSms && !empty($dest['telefono']) && $this->twilioService !== null) {
                $ok = $this->twilioService->enviarSms($dest['telefono'], $mensaje);
                $this->registrarYAcumular($resultados, $proceso, $actuacion, $mensaje, 'sms', $dest['telefono'], $ok);
            }

            // ── WhatsApp ───────────────────────────────────────────────
            if ($quiereWhatsapp && !empty($dest['telefono']) && $this->twilioService !== null) {
                $ok = $this->twilioService->enviarWhatsapp($dest['telefono'], $mensaje);
                $this->registrarYAcumular($resultados, $proceso, $actuacion, $mensaje, 'whatsapp', $dest['telefono'], $ok);
            }
        }

        return $resultados;
    }

    private function registrarYAcumular(array &$resultados, $proceso, $actuacion, $mensaje, $tipoEnvio, $destinatario, $ok) {
        $this->notificacionModel->registrarLog([
            'proceso_id'   => $proceso['id'],
            'actuacion_id' => $actuacion['id'],
            'tipo_envio'   => $tipoEnvio,
            'destinatario' => $destinatario,
            'estado'       => $ok ? 'enviado' : 'fallido',
            'mensaje'      => $mensaje,
        ]);

        $resultados[] = [
            'tipo'         => $tipoEnvio,
            'destinatario' => $destinatario,
            'resultado'    => $ok,
        ];
    }

    private function getSistemaUrl() {
        // Cuando corre desde cron no hay HTTP_HOST — usar la URL configurada aquí
        $baseUrl = defined('SISTEMA_URL') ? SISTEMA_URL : 'http://localhost';
        $host    = $_SERVER['HTTP_HOST'] ?? null;
        if ($host) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $baseUrl  = "$protocol://$host";
        }
        return "$baseUrl/procesos_juridicos/frontend/index.php?view=procesos";
    }
}