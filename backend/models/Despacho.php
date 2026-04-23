<?php
require_once __DIR__ . '/../config/database.php';

class Despacho {
    private $conn;
    private $table = 'despachos';

    public function __construct() {
        $this->conn = (new Database())->getConnection();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("
            SELECT d.*, e.nombre as entidad_nombre,
                   dep.nombre as departamento_nombre, m.nombre as municipio_nombre
            FROM {$this->table} d
            LEFT JOIN entidades e       ON d.entidad_id      = e.id
            LEFT JOIN departamentos dep ON d.departamento_id = dep.id
            LEFT JOIN municipios m      ON d.municipio_id    = m.id
            WHERE d.activo = 1 ORDER BY d.nombre");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search(string $q, int $limit = 30): array {
        $stmt = $this->conn->prepare("
            SELECT d.id, d.nombre, d.codigo_oficial,
                   e.nombre as entidad_nombre,
                   dep.nombre as departamento_nombre, m.nombre as municipio_nombre
            FROM {$this->table} d
            LEFT JOIN entidades e       ON d.entidad_id      = e.id
            LEFT JOIN departamentos dep ON d.departamento_id = dep.id
            LEFT JOIN municipios m      ON d.municipio_id    = m.id
            WHERE d.activo = 1
              AND (d.nombre LIKE :q OR d.codigo_oficial LIKE :q2)
            ORDER BY d.nombre LIMIT :lim");
        $like = '%' . $q . '%';
        $stmt->bindValue(':q',   $like);
        $stmt->bindValue(':q2',  $like);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("
            SELECT d.*, e.nombre as entidad_nombre,
                   dep.nombre as departamento_nombre, m.nombre as municipio_nombre
            FROM {$this->table} d
            LEFT JOIN entidades e       ON d.entidad_id      = e.id
            LEFT JOIN departamentos dep ON d.departamento_id = dep.id
            LEFT JOIN municipios m      ON d.municipio_id    = m.id
            WHERE d.id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table}
                (nombre, codigo_oficial, descripcion, entidad_id, departamento_id, municipio_id)
            VALUES
                (:nombre, :codigo_oficial, :descripcion, :entidad_id, :departamento_id, :municipio_id)");
        if ($stmt->execute($data)) return $this->conn->lastInsertId();
        return false;
    }

    public function update($data) {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table}
            SET nombre=:nombre, codigo_oficial=:codigo_oficial, descripcion=:descripcion,
                entidad_id=:entidad_id, departamento_id=:departamento_id, municipio_id=:municipio_id
            WHERE id=:id");
        return $stmt->execute($data);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET activo=0 WHERE id=:id");
        return $stmt->execute([':id' => $id]);
    }
}
?>