<?php
require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/../models/NotificacionConfig.php';

/**
 * Notificaciones de nuevas actuaciones — solo por correo electrónico.
 * (SMS y WhatsApp vía Twilio se retiraron: el email es gratuito y suficiente.)
 */
class NotificacionService {
    private $emailService;
    private $notificacionModel;

    public function __construct() {
        $this->emailService      = new EmailService();
        $this->notificacionModel = new NotificacionConfig();
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
        $enviados   = []; // evita mandar dos veces el mismo correo al mismo destinatario

        foreach ($destinatarios as $dest) {
            // Toda configuración activa con email recibe el correo, sin importar
            // el "tipo" que tuviera antes (sms/whatsapp/todos ya no existen).
            $email = trim($dest['email'] ?? '');
            if ($email === '' || isset($enviados[strtolower($email)])) continue;
            $enviados[strtolower($email)] = true;

            $ok = $this->emailService->enviar($email, $asunto, $mensaje);
            $this->registrarYAcumular($resultados, $proceso, $actuacion, $mensaje, 'email', $email, $ok);
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