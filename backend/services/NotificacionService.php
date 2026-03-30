<?php
require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/../models/NotificacionConfig.php';

// WhatsApp es opcional: solo se carga si el archivo existe
if (file_exists(__DIR__ . '/WhatsAppService.php')) {
    require_once __DIR__ . '/WhatsAppService.php';
}

class NotificacionService {
    private $emailService;
    private $whatsappService;
    private $notificacionModel;

    public function __construct() {
        $this->emailService      = new EmailService();
        $this->notificacionModel = new NotificacionConfig();

        // WhatsApp solo si está disponible
        $this->whatsappService = class_exists('WhatsAppService')
            ? new WhatsAppService()
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

            // ── Envío por correo ──────────────────────────────────────
            if (!empty($dest['email'])) {
                $emailOk = $this->emailService->enviar($dest['email'], $asunto, $mensaje);

                $this->notificacionModel->registrarLog([
                    'proceso_id'   => $proceso['id'],
                    'actuacion_id' => $actuacion['id'],
                    'tipo_envio'   => 'email',
                    'destinatario' => $dest['email'],
                    'estado'       => $emailOk ? 'enviado' : 'fallido',
                    'mensaje'      => $mensaje,
                ]);

                $resultados[] = [
                    'tipo'        => 'email',
                    'destinatario' => $dest['email'],
                    'resultado'   => $emailOk,
                ];
            }

            // ── Envío por WhatsApp (solo si el servicio está disponible) ──
            if (!empty($dest['telefono']) && $this->whatsappService !== null) {
                $waOk = $this->whatsappService->enviar($dest['telefono'], $mensaje);

                $this->notificacionModel->registrarLog([
                    'proceso_id'   => $proceso['id'],
                    'actuacion_id' => $actuacion['id'],
                    'tipo_envio'   => 'whatsapp',
                    'destinatario' => $dest['telefono'],
                    'estado'       => $waOk ? 'enviado' : 'fallido',
                    'mensaje'      => $mensaje,
                ]);

                $resultados[] = [
                    'tipo'         => 'whatsapp',
                    'destinatario' => $dest['telefono'],
                    'resultado'    => $waOk,
                ];
            }
        }

        return $resultados;
    }

    private function getSistemaUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "$protocol://$host/procesos_juridicos/frontend/index.php?view=procesos";
    }
}