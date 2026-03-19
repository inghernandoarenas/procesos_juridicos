<?php
require_once __DIR__ . '/../config/database.php';

class EstadoProceso {
    private $conn;
    private $table = 'estados_proceso';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " WHERE activo = 1 ORDER BY id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (nombre, color) VALUES (:nombre, :color)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    public function update($data) {
        $query = "UPDATE " . $this->table . " SET nombre = :nombre, color = :color WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    public function delete($id) {
        // Soft delete - solo desactivar
        $query = "UPDATE " . $this->table . " SET activo = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>