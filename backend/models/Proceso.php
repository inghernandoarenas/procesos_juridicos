<?php
require_once __DIR__ . '/../config/database.php';

class Proceso {
    private $conn;
    private $table = 'procesos';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAll() {
        $query = "SELECT p.*, 
                c.nombre, c.apellido,
                tp.nombre as tipo_proceso_nombre,
                ep.nombre as estado_proceso_nombre,
                ep.color as estado_color
                FROM " . $this->table . " p 
                JOIN clientes c ON p.cliente_id = c.id 
                LEFT JOIN tipos_proceso tp ON p.tipo_proceso_id = tp.id
                LEFT JOIN estados_proceso ep ON p.estado_proceso_id = ep.id
                ORDER BY p.id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCliente($cliente_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE cliente_id = :cliente_id ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cliente_id', $cliente_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT p.*, 
                c.nombre, c.apellido,
                tp.nombre as tipo_proceso_nombre,
                ep.nombre as estado_proceso_nombre,
                ep.color as estado_color
                FROM " . $this->table . " p 
                JOIN clientes c ON p.cliente_id = c.id 
                LEFT JOIN tipos_proceso tp ON p.tipo_proceso_id = tp.id
                LEFT JOIN estados_proceso ep ON p.estado_proceso_id = ep.id
                WHERE p.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                (cliente_id, tipo_proceso_id, estado_proceso_id, numero_radicado, descripcion, fecha_inicio, fecha_vencimiento) 
                VALUES (:cliente_id, :tipo_proceso_id, :estado_proceso_id, :numero_radicado, :descripcion, :fecha_inicio, :fecha_vencimiento)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    public function update($data) {
        $query = "UPDATE " . $this->table . " 
                SET cliente_id=:cliente_id, tipo_proceso_id=:tipo_proceso_id, estado_proceso_id=:estado_proceso_id, 
                    numero_radicado=:numero_radicado, descripcion=:descripcion, 
                    fecha_inicio=:fecha_inicio, fecha_vencimiento=:fecha_vencimiento 
                WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getProximosVencer() {
        $query = "SELECT p.*, c.nombre, c.apellido 
                  FROM " . $this->table . " p 
                  JOIN clientes c ON p.cliente_id = c.id 
                  WHERE p.estado != 'Finalizado' 
                  AND p.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)
                  ORDER BY p.fecha_vencimiento ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEnEspera() {
        $query = "SELECT p.*, c.nombre, c.apellido 
                  FROM " . $this->table . " p 
                  JOIN clientes c ON p.cliente_id = c.id 
                  WHERE p.estado = 'En espera'
                  ORDER BY p.fecha_vencimiento ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>