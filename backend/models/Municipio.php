<?php
require_once __DIR__ . '/../config/database.php';

class Municipio {
    private $conn;
    private $table = 'municipios';

    public function __construct() {
        $this->conn = (new Database())->getConnection();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("
            SELECT m.*, d.nombre as departamento_nombre
            FROM {$this->table} m
            JOIN departamentos d ON m.departamento_id = d.id
            WHERE m.activo=1 ORDER BY d.nombre, m.nombre");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByDepartamento($departamento_id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE departamento_id=:did AND activo=1 ORDER BY nombre");
        $stmt->execute([':did' => $departamento_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT m.*, d.nombre as departamento_nombre FROM {$this->table} m JOIN departamentos d ON m.departamento_id=d.id WHERE m.id=:id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (departamento_id, nombre) VALUES (:departamento_id, :nombre)");
        if ($stmt->execute($data)) return $this->conn->lastInsertId();
        return false;
    }

    public function update($data) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET departamento_id=:departamento_id, nombre=:nombre WHERE id=:id");
        return $stmt->execute($data);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET activo=0 WHERE id=:id");
        return $stmt->execute([':id' => $id]);
    }
}
?>