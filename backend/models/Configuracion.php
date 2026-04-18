<?php
require_once __DIR__ . '/../config/database.php';

class Configuracion {
    private $conn;
    private $table = 'configuracion';

    public function __construct() {
        $database   = new Database();
        $this->conn = $database->getConnection();
    }

    // Obtener todas las configuraciones como array clave => valor
    public function getAll() {
        $stmt = $this->conn->prepare("SELECT clave, valor, descripcion FROM {$this->table} ORDER BY id");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un valor específico por clave
    public function get($clave, $default = '') {
        $stmt = $this->conn->prepare("SELECT valor FROM {$this->table} WHERE clave = :clave LIMIT 1");
        $stmt->bindParam(':clave', $clave);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? ($row['valor'] ?? $default) : $default;
    }

    // Obtener todas como objeto clave => valor (más cómodo para usar en vistas)
    public function getMap() {
        $rows = $this->getAll();
        $map  = [];
        foreach ($rows as $row) {
            $map[$row['clave']] = $row['valor'];
        }
        return $map;
    }

    // Guardar — INSERT si no existe, UPDATE si ya existe
    public function saveAll($data) {
        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table} (clave, valor) VALUES (:clave, :valor)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
        );
        foreach ($data as $clave => $valor) {
            $stmt->bindValue(':clave', $clave);
            $stmt->bindValue(':valor', $valor);
            $stmt->execute();
        }
        return true;
    }
}
?>