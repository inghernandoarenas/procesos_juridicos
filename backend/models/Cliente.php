<?php
require_once __DIR__ . '/../config/database.php';

class Cliente {
    private $conn;
    private $table = 'clientes';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY id DESC";
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
        $query = "INSERT INTO " . $this->table . " (nombre, apellido, email, telefono, direccion) VALUES (:nombre, :apellido, :email, :telefono, :direccion)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    public function update($data) {
        $query = "UPDATE " . $this->table . " SET nombre=:nombre, apellido=:apellido, email=:email, telefono=:telefono, direccion=:direccion WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getAllPaginated($inicio, $por_pagina, $buscar = '') {
        $params = [];
        $where  = '';

        if (!empty($buscar)) {
            $where = " WHERE nombre LIKE :buscar
                          OR apellido LIKE :buscar
                          OR email LIKE :buscar
                          OR telefono LIKE :buscar
                          OR CONCAT(nombre,' ',apellido) LIKE :buscar";
            $params[':buscar'] = '%' . $buscar . '%';
        }

        $query = "SELECT * FROM " . $this->table . $where . " ORDER BY nombre ASC LIMIT :inicio, :por_pagina";
        $stmt  = $this->conn->prepare($query);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindParam(':inicio',    $inicio,    PDO::PARAM_INT);
        $stmt->bindParam(':por_pagina',$por_pagina,PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $queryCount = "SELECT COUNT(*) as total FROM " . $this->table . $where;
        $stmtCount  = $this->conn->prepare($queryCount);
        foreach ($params as $k => $v) $stmtCount->bindValue($k, $v);
        $stmtCount->execute();
        $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

        return [
            'data'          => $data,
            'total'         => $total,
            'pagina'        => ($inicio / $por_pagina) + 1,
            'por_pagina'    => $por_pagina,
            'total_paginas' => ceil($total / $por_pagina),
        ];
    }

    public function getResumen($id) {
        // Conteos por estado
        $query = "SELECT
                    COUNT(p.id) AS total_procesos,
                    SUM(CASE WHEN ep.nombre = 'Activo'    THEN 1 ELSE 0 END) AS activos,
                    SUM(CASE WHEN ep.nombre = 'En espera' THEN 1 ELSE 0 END) AS en_espera,
                    SUM(CASE WHEN ep.nombre = 'Finalizado' THEN 1 ELSE 0 END) AS finalizados,
                    SUM(CASE WHEN p.fecha_vencimiento BETWEEN CURDATE()
                             AND DATE_ADD(CURDATE(), INTERVAL 15 DAY) THEN 1 ELSE 0 END) AS proximos_vencer
                  FROM clientes c
                  LEFT JOIN procesos p        ON p.cliente_id        = c.id
                  LEFT JOIN estados_proceso ep ON p.estado_proceso_id = ep.id
                  WHERE c.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $conteos = $stmt->fetch(PDO::FETCH_ASSOC);

        // Últimas 5 actuaciones de cualquier proceso del cliente
        $query2 = "SELECT a.fecha, a.actuacion, a.observaciones, p.numero_radicado
                   FROM actuaciones a
                   JOIN procesos p ON a.proceso_id = p.id
                   WHERE p.cliente_id = :id
                   ORDER BY a.fecha DESC, a.created_at DESC
                   LIMIT 5";
        $stmt2 = $this->conn->prepare($query2);
        $stmt2->bindParam(':id', $id);
        $stmt2->execute();
        $actuaciones = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // Lista de procesos con su estado
        $query3 = "SELECT p.id, p.numero_radicado, p.fecha_vencimiento,
                          tp.nombre AS tipo_proceso,
                          ep.nombre AS estado, ep.color AS estado_color
                   FROM procesos p
                   LEFT JOIN tipos_proceso   tp ON p.tipo_proceso_id   = tp.id
                   LEFT JOIN estados_proceso ep ON p.estado_proceso_id = ep.id
                   WHERE p.cliente_id = :id
                   ORDER BY p.fecha_vencimiento ASC";
        $stmt3 = $this->conn->prepare($query3);
        $stmt3->bindParam(':id', $id);
        $stmt3->execute();
        $procesos = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        return [
            'conteos'     => $conteos,
            'actuaciones' => $actuaciones,
            'procesos'    => $procesos,
        ];
    }

}
?>