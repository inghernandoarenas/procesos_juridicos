<?php
require_once __DIR__ . '/../config/database.php';

class Anexo {
    private $conn;
    private $table = 'anexos';

    public function __construct() {
        $database   = new Database();
        $this->conn = $database->getConnection();
    }

    public function getByProceso($proceso_id) {
        $query = "SELECT a.*, ac.nombre AS categoria_nombre
                  FROM " . $this->table . " a
                  LEFT JOIN anexo_categorias ac ON a.categoria_id = ac.id
                  WHERE a.proceso_id = :proceso_id
                  ORDER BY ac.nombre ASC, a.fecha_subida DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':proceso_id', $proceso_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategorias() {
        $stmt = $this->conn->prepare(
            "SELECT id, nombre FROM anexo_categorias WHERE activo = 1 ORDER BY nombre ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . "
                  (proceso_id, categoria_id, nombre_archivo, ruta_archivo, tipo_archivo)
                  VALUES (:proceso_id, :categoria_id, :nombre_archivo, :ruta_archivo, :tipo_archivo)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Inserta anexos traídos de TYBA, mapeando a la estructura existente.
     * Recibe {nombre, tipo, contenido_base64} por cada archivo (Node ya no
     * puede dar una URL de TYBA porque el botón real es un postback de
     * ASP.NET, no un link) y guarda el binario en uploads/, igual que un
     * anexo subido a mano.
     */
    public function insertarLoteTyba(array $anexosTyba, int $proceso_id, ?int $usuario_id = null): array {
        if (empty($anexosTyba)) return [];

        $insertados = 0;
        $omitidos   = 0;

        $query = "INSERT INTO " . $this->table . "
                  (proceso_id, categoria_id, nombre_archivo, ruta_archivo, tipo_archivo, usuario_creacion)
                  VALUES (:proceso_id, :categoria_id, :nombre, :ruta, :tipo, :usuario)";
        $stmt = $this->conn->prepare($query);

        // FIX: antes el duplicado se comparaba por (nombre_archivo + ruta_archivo).
        // Como ruta_archivo se genera con uniqid() en CADA descarga, nunca iba a
        // coincidir con una sincronización anterior — cada corrida insertaba el
        // mismo PDF de nuevo. Ahora se compara solo por nombre_archivo, y además
        // solo se decodifica/escribe el binario cuando de verdad es nuevo (evita
        // archivos huérfanos en uploads/ que nunca quedan referenciados en BD).
        $checkQuery = "SELECT id FROM " . $this->table . "
                       WHERE proceso_id = :proceso_id AND nombre_archivo = :nombre LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);

        $upload_dir = __DIR__ . '/../../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($anexosTyba as $anexo) {
            $nombre = trim($anexo['nombre'] ?? '');
            if ($nombre === '') {
                $omitidos++;
                continue;
            }

            $checkStmt->execute([':proceso_id' => $proceso_id, ':nombre' => $nombre]);
            if ($checkStmt->fetch()) {
                $omitidos++;
                continue; // ya lo tenemos, no se vuelve a descargar/guardar
            }

            $b64 = $anexo['contenido_base64'] ?? null;
            if (!$b64) {
                $omitidos++;
                continue;
            }
            $contenido = base64_decode($b64, true);
            if ($contenido === false || strlen($contenido) === 0) {
                error_log("Anexo::insertarLoteTyba: no se pudo decodificar '{$nombre}'");
                $omitidos++;
                continue;
            }

            $extension    = pathinfo($nombre, PATHINFO_EXTENSION) ?: strtolower($anexo['tipo'] ?? 'pdf');
            $nombre_unico = uniqid('tyba_') . '.' . $extension;
            $ruta_destino = $upload_dir . $nombre_unico;
            if (file_put_contents($ruta_destino, $contenido) === false) {
                error_log("Anexo::insertarLoteTyba: no se pudo guardar '{$nombre}'");
                $omitidos++;
                continue;
            }
            $ruta = 'uploads/' . $nombre_unico;

            // Determinar categoria_id según el tipo o nombre del documento
            $textoBusqueda = strtolower($nombre . ' ' . ($anexo['tipo'] ?? ''));
            if (strpos($textoBusqueda, 'auto') !== false || strpos($textoBusqueda, 'sentencia') !== false || strpos($textoBusqueda, 'proveído') !== false || strpos($textoBusqueda, 'interlocutorio') !== false) {
                $categoria_id = 4; // Respuestas del juez
            } elseif (strpos($textoBusqueda, 'demanda') !== false || strpos($textoBusqueda, 'tutela') !== false || strpos($textoBusqueda, 'escrito') !== false) {
                $categoria_id = 7; // Expediente
            } elseif (strpos($textoBusqueda, 'prueba') !== false || strpos($textoBusqueda, 'certificado') !== false || strpos($textoBusqueda, 'anexo') !== false) {
                $categoria_id = 3; // Evidencias
            } else {
                $categoria_id = 8; // Otros
            }

            $stmt->execute([
                ':proceso_id'   => $proceso_id,
                ':categoria_id' => $categoria_id,
                ':nombre'       => $nombre,
                ':ruta'         => $ruta,
                ':tipo'         => strtoupper($extension),
                ':usuario'      => $usuario_id,
            ]);
            $insertados++;
        }

        return ['insertados' => $insertados, 'omitidos' => $omitidos];
    }
}
?>