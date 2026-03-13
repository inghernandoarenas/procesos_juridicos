<?php
require_once __DIR__ . '/../config/database.php';

class Actuacion {
    private $conn;
    private $table = 'actuaciones';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getByProceso($proceso_id) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE proceso_id = :proceso_id 
                  ORDER BY fecha DESC, created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':proceso_id', $proceso_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (proceso_id, fecha, actuacion, observaciones) 
                  VALUES (:proceso_id, :fecha, :actuacion, :observaciones)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>