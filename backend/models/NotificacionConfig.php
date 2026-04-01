<?php
require_once __DIR__ . '/../config/database.php';

class NotificacionConfig {
    private $conn;
    private $table = 'notificaciones_config';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAll() {
        $query = "SELECT nc.*, u.nombre AS usuario_nombre, u.usuario AS usuario_login
                  FROM " . $this->table . " nc
                  JOIN usuarios u ON nc.usuario_id = u.id
                  WHERE nc.activo = 1
                  ORDER BY u.nombre, nc.id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT nc.*, u.nombre AS usuario_nombre, u.usuario AS usuario_login,
                         u.email AS usuario_email, u.telefono AS usuario_telefono
                  FROM " . $this->table . " nc
                  JOIN usuarios u ON nc.usuario_id = u.id
                  WHERE nc.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getDestinatariosActivos() {
        $query = "SELECT * FROM " . $this->table . " WHERE activo = 1";
        $stmt  = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (usuario_id, tipo, email, telefono)
                  VALUES (:usuario_id, :tipo, :email, :telefono)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':usuario_id', $data['usuario_id']);
        $stmt->bindValue(':tipo',       $data['tipo']);
        $stmt->bindValue(':email',      $data['email']);
        $stmt->bindValue(':telefono',   $data['telefono']);
        return $stmt->execute();
    }

    public function update($data) {
        $query = "UPDATE " . $this->table . "
                  SET usuario_id = :usuario_id, tipo = :tipo,
                      email = :email, telefono = :telefono,
                      updated_at = NOW()
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id',         $data['id']);
        $stmt->bindValue(':usuario_id', $data['usuario_id']);
        $stmt->bindValue(':tipo',       $data['tipo']);
        $stmt->bindValue(':email',      $data['email']);
        $stmt->bindValue(':telefono',   $data['telefono']);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "UPDATE " . $this->table . " SET activo = 0 WHERE id = :id";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function registrarLog($data) {
        $query = "INSERT INTO notificaciones_log
                  (proceso_id, actuacion_id, tipo_envio, destinatario, estado, mensaje, fecha_envio)
                  VALUES (:proceso_id, :actuacion_id, :tipo_envio, :destinatario, :estado, :mensaje, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':proceso_id',   $data['proceso_id']);
        $stmt->bindParam(':actuacion_id', $data['actuacion_id']);
        $stmt->bindParam(':tipo_envio',   $data['tipo_envio']);
        $stmt->bindParam(':destinatario', $data['destinatario']);
        $stmt->bindParam(':estado',       $data['estado']);
        $stmt->bindParam(':mensaje',      $data['mensaje']);
        return $stmt->execute();
    }

    public function getLogs($limite = 100) {
        $query = "SELECT l.*, p.numero_radicado
                  FROM notificaciones_log l
                  JOIN procesos p ON l.proceso_id = p.id
                  ORDER BY l.fecha_envio DESC LIMIT :limite";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
?>