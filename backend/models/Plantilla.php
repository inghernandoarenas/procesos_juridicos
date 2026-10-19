<?php
require_once __DIR__ . '/../config/database.php';

class Plantilla {
    private $conn;
    private $table = 'plantillas';

    public function __construct() {
        $database   = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAll(bool $soloActivas = true): array {
        $sql = "SELECT id, nombre, categoria, descripcion, archivo, activo, created_at, updated_at
                FROM {$this->table}" . ($soloActivas ? " WHERE activo = 1" : "") . "
                ORDER BY categoria ASC, nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $d): int {
        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table} (nombre, categoria, descripcion, archivo, usuario_creacion)
             VALUES (:nombre, :categoria, :descripcion, :archivo, :usuario)"
        );
        $stmt->execute([
            ':nombre'      => $d['nombre'],
            ':categoria'   => $d['categoria'],
            ':descripcion' => $d['descripcion'],
            ':archivo'     => $d['archivo'],
            ':usuario'     => $d['usuario'],
        ]);
        return (int)$this->conn->lastInsertId();
    }

    public function update(int $id, array $d): bool {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} SET nombre = :nombre, categoria = :categoria,
                    descripcion = :descripcion, activo = :activo WHERE id = :id"
        );
        return $stmt->execute([
            ':nombre'      => $d['nombre'],
            ':categoria'   => $d['categoria'],
            ':descripcion' => $d['descripcion'],
            ':activo'      => $d['activo'],
            ':id'          => $id,
        ]);
    }

    public function setArchivo(int $id, string $archivo): bool {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET archivo = :a WHERE id = :id");
        return $stmt->execute([':a' => $archivo, ':id' => $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}