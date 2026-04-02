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
                (proceso_id, id_api, fecha, actuacion, observaciones) 
                VALUES (:proceso_id, :id_api, :fecha, :actuacion, :observaciones)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    public function createAndGetId($data) {
        $query = "INSERT INTO " . $this->table . " 
                (proceso_id, id_api, fecha, actuacion, observaciones) 
                VALUES (:proceso_id, :id_api, :fecha, :actuacion, :observaciones)";
        $stmt = $this->conn->prepare($query);
        
        if($stmt->execute($data)) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getByIdApi($id_api, $proceso_id = null) {
        if ($proceso_id) {
            $query = "SELECT id FROM " . $this->table . " WHERE id_api = :id_api AND proceso_id = :proceso_id LIMIT 1";
            $stmt  = $this->conn->prepare($query);
            $stmt->bindParam(':id_api',     $id_api);
            $stmt->bindParam(':proceso_id', $proceso_id);
        } else {
            $query = "SELECT id FROM " . $this->table . " WHERE id_api = :id_api LIMIT 1";
            $stmt  = $this->conn->prepare($query);
            $stmt->bindParam(':id_api', $id_api);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function existeActuacion($id_api, $proceso_id) {
        $query = "SELECT id FROM " . $this->table . " WHERE id_api = :id_api AND proceso_id = :proceso_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_api', $id_api);
        $stmt->bindParam(':proceso_id', $proceso_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUltimasPorProceso($proceso_id, $limite = 5) {
        $query = "SELECT * FROM " . $this->table . " WHERE proceso_id = :proceso_id ORDER BY fecha DESC LIMIT :limite";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':proceso_id', $proceso_id);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>