<?php
require_once __DIR__ . '/../config/database.php';

class Actuacion {
    private $conn;
    private $table = 'actuaciones';

    public function __construct() {
        $database   = new Database();
        $this->conn = $database->getConnection();
    }

    public function getByProceso($proceso_id) {
        $query = "SELECT * FROM " . $this->table . "
                  WHERE proceso_id = :proceso_id
                  ORDER BY despacho ASC, fecha DESC, created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':proceso_id', $proceso_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inserta un lote de actuaciones en UN SOLO QUERY usando INSERT IGNORE.
     * El UNIQUE KEY (proceso_id, id_api, despacho) en la BD previene duplicados
     * sin necesitar un SELECT previo por cada fila.
     *
     * Retorna array con los IDs de las filas realmente insertadas (nuevas).
     */
    public function insertarLote(array $actuaciones, int $proceso_id, string $fuente = 'rama'): array {
        if (empty($actuaciones)) return [];

        // Primero: cargar los id_api que ya existen para este proceso
        // Un solo SELECT vs 52 SELECTs individuales
        $stmt = $this->conn->prepare(
            "SELECT id_api, despacho FROM {$this->table} WHERE proceso_id = :pid"
        );
        $stmt->bindParam(':pid', $proceso_id);
        $stmt->execute();
        $existentes = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $existentes[$row['id_api'] . '||' . $row['despacho']] = true;
        }

        // Filtrar solo las nuevas
        $nuevas = [];
        foreach ($actuaciones as $act) {
            $key = ((string)$act['id']) . '||' . ($act['despacho'] ?? '');
            if (!isset($existentes[$key])) {
                $nuevas[] = $act;
            }
        }

        if (empty($nuevas)) return [];

        // INSERT IGNORE de todas las nuevas en un solo query
        $placeholders = [];
        $params       = [];
        foreach ($nuevas as $i => $act) {
            $placeholders[] = "(:pid{$i}, :id_api{$i}, :fuente{$i}, :despacho{$i}, :fecha{$i}, :actuacion{$i}, :obs{$i})";
            $params[":pid{$i}"]       = $proceso_id;
            $params[":id_api{$i}"]    = (string)$act['id'];
            $params[":fuente{$i}"]    = $act['fuente'] ?? $fuente;
            $params[":despacho{$i}"]  = $act['despacho'] ?? null;
            $params[":fecha{$i}"]     = substr((string)($act['fecha'] ?? ''), 0, 10);
            $params[":actuacion{$i}"] = $act['actuacion'] ?? 'Sin descripción';
            $params[":obs{$i}"]       = $act['observaciones'] ?? null;
        }

        $sql  = "INSERT IGNORE INTO {$this->table}
                 (proceso_id, id_api, fuente, despacho, fecha, actuacion, observaciones)
                 VALUES " . implode(', ', $placeholders);
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        // Devolver cuántas se insertaron realmente
        $insertadas = $stmt->rowCount();

        // Para notificaciones: obtener los IDs de las nuevas insertadas
        // Buscamos por id_api IN (...) de las que intentamos insertar
        $ids_api = array_map(fn($a) => (string)$a['id'], $nuevas);
        $in      = implode(',', array_fill(0, count($ids_api), '?'));
        $stmt2   = $this->conn->prepare(
            "SELECT id, id_api, despacho, fecha, actuacion, observaciones
             FROM {$this->table}
             WHERE proceso_id = ? AND id_api IN ($in)
             ORDER BY id DESC"
        );
        $stmt2->execute(array_merge([$proceso_id], $ids_api));
        $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // Solo retornar las que fueron realmente nuevas (no las que ya existían con ese id_api)
        $insertadasRows = [];
        $yaVistas = [];
        foreach ($rows as $row) {
            $key = $row['id_api'] . '||' . $row['despacho'];
            if (!isset($existentes[$key]) && !isset($yaVistas[$key])) {
                $insertadasRows[] = $row;
                $yaVistas[$key]   = true;
            }
        }

        return $insertadasRows;
    }

    public function create($data) {
        if (!empty($data[':fecha'])) $data[':fecha'] = substr($data[':fecha'], 0, 10);
        $query = "INSERT IGNORE INTO " . $this->table . "
                  (proceso_id, id_api, despacho, fecha, actuacion, observaciones)
                  VALUES (:proceso_id, :id_api, :despacho, :fecha, :actuacion, :observaciones)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    public function createAndGetId($data) {
        if (!empty($data[':fecha'])) $data[':fecha'] = substr($data[':fecha'], 0, 10);
        $query = "INSERT IGNORE INTO " . $this->table . "
                  (proceso_id, id_api, despacho, fecha, actuacion, observaciones)
                  VALUES (:proceso_id, :id_api, :despacho, :fecha, :actuacion, :observaciones)";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute($data) && $stmt->rowCount() > 0) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function existeActuacion($id_api, $proceso_id, $despacho = null) {
        if ($despacho !== null) {
            $query = "SELECT id FROM " . $this->table . "
                      WHERE id_api = :id_api AND proceso_id = :proceso_id AND despacho = :despacho LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_api',     $id_api);
            $stmt->bindParam(':proceso_id', $proceso_id);
            $stmt->bindParam(':despacho',   $despacho);
        } else {
            $query = "SELECT id FROM " . $this->table . "
                      WHERE id_api = :id_api AND proceso_id = :proceso_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_api',     $id_api);
            $stmt->bindParam(':proceso_id', $proceso_id);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

    public function getUltimasPorProceso($proceso_id, $limite = 5) {
        $query = "SELECT * FROM " . $this->table . "
                  WHERE proceso_id = :proceso_id
                  ORDER BY fecha DESC LIMIT :limite";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':proceso_id', $proceso_id);
        $stmt->bindParam(':limite',     $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>