<?php
require_once __DIR__ . '/../config/database.php';

class Notificacion {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getDestinatariosActivos() {
        $query = "SELECT * FROM notificaciones_config WHERE activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function registrarLog($data) {
        $query = "INSERT INTO notificaciones_log 
                  (proceso_id, actuacion_id, tipo_envio, destinatario, estado, mensaje, fecha_envio) 
                  VALUES (:proceso_id, :actuacion_id, :tipo_envio, :destinatario, :estado, :mensaje, NOW())";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':proceso_id', $data['proceso_id']);
        $stmt->bindParam(':actuacion_id', $data['actuacion_id']);
        $stmt->bindParam(':tipo_envio', $data['tipo_envio']);
        $stmt->bindParam(':destinatario', $data['destinatario']);
        $stmt->bindParam(':estado', $data['estado']);
        $stmt->bindParam(':mensaje', $data['mensaje']);
        
        return $stmt->execute();
    }
    
    public function getLogs($limite = 50) {
        $query = "SELECT l.*, p.numero_radicado 
                  FROM notificaciones_log l 
                  JOIN procesos p ON l.proceso_id = p.id 
                  ORDER BY l.created_at DESC LIMIT :limite";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>