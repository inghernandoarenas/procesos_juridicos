<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mail;

    // ─────────────────────────────────────────────
    // CONFIGURA AQUÍ TUS DATOS SMTP
    // ─────────────────────────────────────────────
    private $smtpHost     = 'smtp.gmail.com';
    private $smtpUsuario  = 'ing.hernando.arenas@gmail.com';      // ← cambia esto
    private $smtpPassword = 'sowutqvysqzduyru'; // ← cambia esto (16 chars sin espacios)
    private $smtpPuerto   = 587;
    private $remitente    = 'ing.hernando.arenas@gmail.com';       // ← cambia esto
    private $nombreRemit  = 'Sistema Procesos Jurídicos';
    // ─────────────────────────────────────────────

    private $logFile;

    public function __construct() {
        $this->logFile = __DIR__ . '/../../logs/emails.log';

        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        $this->mail->Host       = $this->smtpHost;
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = $this->smtpUsuario;
        $this->mail->Password   = $this->smtpPassword;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port       = $this->smtpPuerto;
        $this->mail->Timeout    = 10; // 10s máximo por conexión SMTP
        $this->mail->CharSet    = 'UTF-8';

        $this->mail->setFrom($this->remitente, $this->nombreRemit);
    }

    public function enviar($destinatario, $asunto, $mensaje) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($destinatario);
            $this->mail->Subject = $asunto;
            $this->mail->isHTML(true);
            $this->mail->Body    = $this->construirHtml($asunto, $mensaje);
            $this->mail->AltBody = strip_tags($mensaje);

            $this->mail->send();
            $this->registrarLog($destinatario, $asunto, $mensaje, 'ENVIADO');
            return true;

        } catch (Exception $e) {
            // Ahora sí registramos el error real para poder diagnosticarlo
            $this->registrarLog($destinatario, $asunto, $mensaje, 'FALLIDO', $e->getMessage());
            return false;
        }
    }

    private function construirHtml($asunto, $mensaje) {
        $mensajeHtml = nl2br(htmlspecialchars($mensaje));
        return "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;'>
                <div style='background-color: #1a3a5c; padding: 20px;'>
                    <h2 style='color: white; margin: 0;'>Sistema de Procesos Jurídicos</h2>
                </div>
                <div style='padding: 24px;'>
                    <h3 style='color: #1a3a5c;'>{$asunto}</h3>
                    <p style='line-height: 1.6;'>{$mensajeHtml}</p>
                </div>
                <div style='background-color: #f5f5f5; padding: 12px 24px; font-size: 12px; color: #888;'>
                    Este es un mensaje automático. Por favor no responda este correo.
                </div>
            </div>
        </body>
        </html>";
    }

    private function registrarLog($destinatario, $asunto, $mensaje, $estado, $error = '') {
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0777, true);
        }
        $timestamp = date('Y-m-d H:i:s');
        $linea = "[$timestamp] Para: $destinatario | Asunto: $asunto | Estado: $estado";
        if ($error) {
            $linea .= " | Error: $error";
        }
        $linea .= "\n";
        file_put_contents($this->logFile, $linea, FILE_APPEND);
    }
}