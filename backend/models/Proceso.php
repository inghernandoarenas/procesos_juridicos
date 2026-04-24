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
                ORDER BY p.id DESC";
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
                ep.color as estado_color,
                p.sincronizar_api,
                COALESCE(p.es_privado, 0) as es_privado,
                COALESCE(p.fuente_consulta, 'ninguna') as fuente_consulta,
                p.departamento_id, p.municipio_id, p.despacho_id, p.entidad_id,
                dep.nombre as departamento_nombre, mun.nombre as municipio_nombre,
                des.nombre as despacho_nombre, ent.nombre as entidad_nombre
                FROM " . $this->table . " p 
                JOIN clientes c ON p.cliente_id = c.id 
                LEFT JOIN tipos_proceso tp ON p.tipo_proceso_id = tp.id
                LEFT JOIN estados_proceso ep ON p.estado_proceso_id = ep.id
                LEFT JOIN departamentos dep ON p.departamento_id = dep.id
                LEFT JOIN municipios mun     ON p.municipio_id    = mun.id
                LEFT JOIN despachos des      ON p.despacho_id     = des.id
                LEFT JOIN entidades ent      ON p.entidad_id      = ent.id
                WHERE p.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Procesos sin actuaciones por 365+ días — riesgo de desistimiento tácito
    public function getDesistimientoTacito() {
        $query = "SELECT
                    p.id, p.numero_radicado, p.fecha_inicio,
                    c.nombre, c.apellido,
                    tp.nombre AS tipo_proceso,
                    ep.nombre AS estado,
                    ep.color  AS estado_color,
                    MAX(a.fecha)                        AS ultima_actuacion,
                    DATEDIFF(CURDATE(), MAX(a.fecha))   AS dias_sin_movimiento
                  FROM procesos p
                  JOIN clientes c             ON p.cliente_id        = c.id
                  LEFT JOIN tipos_proceso   tp ON p.tipo_proceso_id   = tp.id
                  LEFT JOIN estados_proceso ep ON p.estado_proceso_id = ep.id
                  LEFT JOIN actuaciones     a  ON a.proceso_id        = p.id
                  WHERE (ep.nombre IS NULL OR ep.nombre NOT IN ('Finalizado','Archivado','Cerrado'))
                  GROUP BY p.id, p.numero_radicado, p.fecha_inicio,
                           c.nombre, c.apellido, tp.nombre, ep.nombre, ep.color
                  HAVING dias_sin_movimiento >= 365 OR ultima_actuacion IS NULL
                  ORDER BY dias_sin_movimiento DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Retorna procesos activos que tienen despacho con codigo_oficial asignado.
     * Usado por SincronizarPublicacionesController para saber qué despachos consultar.
     */
    public function getActivosConDespacho(): array {
        $stmt = $this->conn->prepare("
            SELECT p.id, p.numero_radicado,
                   c.nombre, c.apellido,
                   tp.nombre  AS especialidad,
                   ep.nombre  AS estado_proceso_nombre,
                   des.id     AS despacho_id,
                   des.nombre AS despacho_nombre,
                   des.codigo_oficial
            FROM {$this->table} p
            JOIN clientes c              ON p.cliente_id        = c.id
            LEFT JOIN tipos_proceso   tp ON p.tipo_proceso_id   = tp.id
            LEFT JOIN estados_proceso ep ON p.estado_proceso_id = ep.id
            JOIN despachos des           ON p.despacho_id       = des.id
            WHERE des.codigo_oficial IS NOT NULL
              AND des.codigo_oficial != ''
              AND (ep.nombre IS NULL OR ep.nombre NOT IN ('Finalizado','Archivado','Cerrado'))
            ORDER BY p.id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create($data) {
        $query = "INSERT INTO " . $this->table . "
                (cliente_id, tipo_proceso_id, estado_proceso_id, numero_radicado, descripcion, fecha_inicio, fecha_vencimiento, es_privado, fuente_consulta, departamento_id, municipio_id, despacho_id, entidad_id)
                VALUES (:cliente_id, :tipo_proceso_id, :estado_proceso_id, :numero_radicado, :descripcion, :fecha_inicio, :fecha_vencimiento, :es_privado, :fuente_consulta, :departamento_id, :municipio_id, :despacho_id, :entidad_id)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    public function update($data) {
        $query = "UPDATE " . $this->table . "
                SET cliente_id=:cliente_id, tipo_proceso_id=:tipo_proceso_id, estado_proceso_id=:estado_proceso_id,
                    numero_radicado=:numero_radicado, descripcion=:descripcion,
                    fecha_inicio=:fecha_inicio, fecha_vencimiento=:fecha_vencimiento, es_privado=:es_privado, fuente_consulta=:fuente_consulta,
                    departamento_id=:departamento_id, municipio_id=:municipio_id, despacho_id=:despacho_id, entidad_id=:entidad_id
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
        $query = "SELECT p.*, c.nombre, c.apellido,
                         tp.nombre AS tipo_proceso,
                         ep.nombre AS estado,
                         ep.color  AS estado_color
                  FROM " . $this->table . " p
                  JOIN clientes c            ON p.cliente_id        = c.id
                  LEFT JOIN tipos_proceso   tp ON p.tipo_proceso_id   = tp.id
                  LEFT JOIN estados_proceso ep ON p.estado_proceso_id = ep.id
                  WHERE (ep.nombre IS NULL OR ep.nombre NOT IN ('Finalizado','Archivado','Cerrado'))
                  AND p.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)
                  ORDER BY p.fecha_vencimiento ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEnEspera() {
        $query = "SELECT p.*, c.nombre, c.apellido,
                         tp.nombre AS tipo_proceso,
                         ep.nombre AS estado,
                         ep.color  AS estado_color
                  FROM " . $this->table . " p
                  JOIN clientes c            ON p.cliente_id        = c.id
                  LEFT JOIN tipos_proceso   tp ON p.tipo_proceso_id   = tp.id
                  LEFT JOIN estados_proceso ep ON p.estado_proceso_id = ep.id
                  WHERE ep.nombre = 'En espera'
                  ORDER BY p.fecha_vencimiento ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllPaginated($inicio, $por_pagina, $buscar = '') {
        // Consulta base
        $sql = "SELECT p.*, 
                c.nombre, c.apellido,
                tp.nombre as tipo_proceso_nombre,
                ep.nombre as estado_proceso_nombre,
                ep.color as estado_color
                FROM " . $this->table . " p 
                JOIN clientes c ON p.cliente_id = c.id 
                LEFT JOIN tipos_proceso tp ON p.tipo_proceso_id = tp.id
                LEFT JOIN estados_proceso ep ON p.estado_proceso_id = ep.id";
        
        // Agregar condición de búsqueda si hay término
        $params = [];
        if(!empty($buscar)) {
            $sql .= " WHERE p.numero_radicado LIKE :buscar 
                    OR c.nombre LIKE :buscar 
                    OR c.apellido LIKE :buscar 
                    OR CONCAT(c.nombre, ' ', c.apellido) LIKE :buscar
                    OR tp.nombre LIKE :buscar
                    OR p.descripcion LIKE :buscar";
            $buscarParam = "%$buscar%";
            $params[':buscar'] = $buscarParam;
        }
        
        // Agregar orden y paginación
        $sql .= " ORDER BY p.id DESC LIMIT :inicio, :por_pagina";
        
        $stmt = $this->conn->prepare($sql);
        
        // Bind parámetros de búsqueda
        if(!empty($buscar)) {
            $stmt->bindParam(':buscar', $buscarParam);
        }
        
        // Bind parámetros de paginación
        $stmt->bindParam(':inicio', $inicio, PDO::PARAM_INT);
        $stmt->bindParam(':por_pagina', $por_pagina, PDO::PARAM_INT);
        
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener total de registros (con la misma búsqueda)
        $sqlCount = "SELECT COUNT(*) as total 
                    FROM " . $this->table . " p 
                    JOIN clientes c ON p.cliente_id = c.id 
                    LEFT JOIN tipos_proceso tp ON p.tipo_proceso_id = tp.id";
        
        if(!empty($buscar)) {
            $sqlCount .= " WHERE p.numero_radicado LIKE :buscar 
                        OR c.nombre LIKE :buscar 
                        OR c.apellido LIKE :buscar 
                        OR CONCAT(c.nombre, ' ', c.apellido) LIKE :buscar
                        OR tp.nombre LIKE :buscar
                        OR p.descripcion LIKE :buscar";
        }
        
        $stmtCount = $this->conn->prepare($sqlCount);
        if(!empty($buscar)) {
            $stmtCount->bindParam(':buscar', $buscarParam);
        }
        $stmtCount->execute();
        $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
        
        return [
            'data' => $data,
            'total' => $total,
            'pagina' => ($inicio / $por_pagina) + 1,
            'por_pagina' => $por_pagina,
            'total_paginas' => ceil($total / $por_pagina),
            'buscar' => $buscar
        ];
    }

    public function getSinMovimiento() {
        $query = "SELECT
                    p.id, p.numero_radicado, p.fecha_inicio,
                    c.nombre, c.apellido,
                    tp.nombre AS tipo_proceso,
                    ep.nombre AS estado,
                    ep.color  AS estado_color,
                    MAX(a.fecha) AS ultima_actuacion,
                    DATEDIFF(CURDATE(), MAX(a.fecha)) AS dias_sin_movimiento
                  FROM procesos p
                  JOIN clientes c           ON p.cliente_id        = c.id
                  LEFT JOIN tipos_proceso   tp ON p.tipo_proceso_id   = tp.id
                  LEFT JOIN estados_proceso ep ON p.estado_proceso_id = ep.id
                  LEFT JOIN actuaciones a      ON a.proceso_id        = p.id
                  WHERE ep.nombre IS NULL
                     OR ep.nombre NOT IN ('Finalizado', 'Archivado', 'Cerrado')
                  GROUP BY p.id, p.numero_radicado, p.fecha_inicio,
                           c.nombre, c.apellido,
                           tp.nombre, ep.nombre, ep.color
                  HAVING dias_sin_movimiento >= 30
                      OR ultima_actuacion IS NULL
                  ORDER BY dias_sin_movimiento DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats() {
        $query = "SELECT
                    COUNT(p.id) AS total,
                    SUM(CASE WHEN ep.nombre NOT IN ('Finalizado','Archivado','Cerrado') OR ep.nombre IS NULL THEN 1 ELSE 0 END) AS activos,
                    SUM(CASE WHEN p.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)
                             AND (ep.nombre NOT IN ('Finalizado','Archivado','Cerrado') OR ep.nombre IS NULL) THEN 1 ELSE 0 END) AS por_vencer
                  FROM " . $this->table . " p
                  LEFT JOIN estados_proceso ep ON p.estado_proceso_id = ep.id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

}
?>