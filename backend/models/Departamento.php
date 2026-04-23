<?php
require_once __DIR__ . '/../config/database.php';

class Departamento {
    private $conn;
    private $table = 'departamentos';

    public function __construct() {
        $this->conn = (new Database())->getConnection();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE activo=1 ORDER BY nombre");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id=:id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (nombre) VALUES (:nombre)");
        if ($stmt->execute([':nombre' => $data[':nombre']])) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($data) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET nombre=:nombre WHERE id=:id");
        return $stmt->execute($data);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET activo=0 WHERE id=:id");
        return $stmt->execute([':id' => $id]);
    }
}
?>