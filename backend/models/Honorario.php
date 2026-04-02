<?php
require_once __DIR__ . '/../config/database.php';

class Honorario {
    private $conn;
    private $table = 'honorarios';

    public function __construct() {
        $database   = new Database();
        $this->conn = $database->getConnection();
    }

    public function getByProceso($proceso_id) {
        $query = "SELECT * FROM {$this->table}
                  WHERE proceso_id = :proceso_id
                  ORDER BY fecha_causacion DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':proceso_id', $proceso_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getResumenProceso($proceso_id) {
        $query = "SELECT
                    COUNT(*)                                        AS total_registros,
                    COALESCE(SUM(valor), 0)                         AS total_cobrado,
                    COALESCE(SUM(CASE WHEN estado='pagado'   THEN valor ELSE 0 END), 0) AS total_pagado,
                    COALESCE(SUM(CASE WHEN estado='pendiente' OR estado='vencido' THEN valor ELSE 0 END), 0) AS total_pendiente,
                    COALESCE(SUM(CASE WHEN estado='vencido'  THEN valor ELSE 0 END), 0) AS total_vencido
                  FROM {$this->table}
                  WHERE proceso_id = :proceso_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':proceso_id', $proceso_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO {$this->table}
                  (proceso_id, concepto, tipo, valor, fecha_causacion, fecha_pago, estado, observaciones)
                  VALUES (:proceso_id, :concepto, :tipo, :valor, :fecha_causacion, :fecha_pago, :estado, :observaciones)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':proceso_id',      $data['proceso_id']);
        $stmt->bindValue(':concepto',        $data['concepto']);
        $stmt->bindValue(':tipo',            $data['tipo']);
        $stmt->bindValue(':valor',           $data['valor']);
        $stmt->bindValue(':fecha_causacion', $data['fecha_causacion']);
        $stmt->bindValue(':fecha_pago',      $data['fecha_pago'] ?: null);
        $stmt->bindValue(':estado',          $data['estado']);
        $stmt->bindValue(':observaciones',   $data['observaciones'] ?: null);
        return $stmt->execute();
    }

    public function update($data) {
        $query = "UPDATE {$this->table}
                  SET concepto = :concepto, tipo = :tipo, valor = :valor,
                      fecha_causacion = :fecha_causacion, fecha_pago = :fecha_pago,
                      estado = :estado, observaciones = :observaciones
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id',              $data['id']);
        $stmt->bindValue(':concepto',        $data['concepto']);
        $stmt->bindValue(':tipo',            $data['tipo']);
        $stmt->bindValue(':valor',           $data['valor']);
        $stmt->bindValue(':fecha_causacion', $data['fecha_causacion']);
        $stmt->bindValue(':fecha_pago',      $data['fecha_pago'] ?: null);
        $stmt->bindValue(':estado',          $data['estado']);
        $stmt->bindValue(':observaciones',   $data['observaciones'] ?: null);
        return $stmt->execute();
    }

    public function marcarPagado($id, $fecha_pago) {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} SET estado='pagado', fecha_pago=:fecha_pago WHERE id=:id"
        );
        $stmt->bindParam(':fecha_pago', $fecha_pago);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id=:id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Para el dashboard — resumen financiero global
    public function getResumenGlobal() {
        $query = "SELECT
                    COALESCE(SUM(CASE WHEN MONTH(fecha_causacion)=MONTH(CURDATE()) AND YEAR(fecha_causacion)=YEAR(CURDATE()) AND estado='pagado' THEN valor ELSE 0 END),0) AS cobrado_mes,
                    COALESCE(SUM(CASE WHEN estado IN ('pendiente','vencido') THEN valor ELSE 0 END),0)  AS pendiente_total,
                    COALESCE(SUM(CASE WHEN estado='vencido' THEN valor ELSE 0 END),0)                   AS vencido_total,
                    COUNT(DISTINCT CASE WHEN estado IN ('pendiente','vencido') THEN proceso_id END)      AS procesos_con_pendiente
                  FROM {$this->table}";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar estados vencidos automáticamente
    public function actualizarVencidos() {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} SET estado='vencido'
             WHERE estado='pendiente' AND fecha_causacion < CURDATE()"
        );
        return $stmt->execute();
    }

    public function getAll() {
        $query = "SELECT h.*, p.numero_radicado,
                         CONCAT(c.nombre,' ',c.apellido) AS cliente
                  FROM {$this->table} h
                  JOIN procesos p  ON h.proceso_id = p.id
                  JOIN clientes c  ON p.cliente_id = c.id
                  ORDER BY h.fecha_causacion DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
?>