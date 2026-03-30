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
}
?>