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
     * Evita duplicados comparando proceso_id + nombre_archivo + ruta_archivo.
     */
    public function insertarLoteTyba(array $anexosTyba, int $proceso_id, int $usuario_id = null): array {
        if (empty($anexosTyba)) return [];

        $insertados = 0;
        $omitidos = 0;

        // Preparar statement de inserción
        $query = "INSERT INTO " . $this->table . " 
                  (proceso_id, categoria_id, nombre_archivo, ruta_archivo, tipo_archivo, usuario_creacion) 
                  VALUES (:proceso_id, :categoria_id, :nombre, :ruta, :tipo, :usuario)";
        $stmt = $this->conn->prepare($query);

        // Preparar statement de verificación de duplicados
        $checkQuery = "SELECT id FROM " . $this->table . " 
                       WHERE proceso_id = :proceso_id AND nombre_archivo = :nombre AND ruta_archivo = :ruta LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);

        foreach ($anexosTyba as $anexo) {
            $nombre = trim($anexo['nombre']);
            $ruta = trim($anexo['url']);
            
            // 1. Validar duplicado
            $checkStmt->execute([
                ':proceso_id' => $proceso_id,
                ':nombre'     => $nombre,
                ':ruta'       => $ruta
            ]);
            if ($checkStmt->fetch()) {
                $omitidos++;
                continue; // Ya existe, saltar
            }

            // 2. Determinar categoria_id según el tipo o nombre del documento
            $textoBusqueda = strtolower($nombre . ' ' . $anexo['tipo']);
            if (strpos($textoBusqueda, 'auto') !== false || strpos($textoBusqueda, 'sentencia') !== false || strpos($textoBusqueda, 'proveído') !== false || strpos($textoBusqueda, 'interlocutorio') !== false) {
                $categoria_id = 4; // Respuestas del juez
            } elseif (strpos($textoBusqueda, 'demanda') !== false || strpos($textoBusqueda, 'tutela') !== false || strpos($textoBusqueda, 'escrito') !== false) {
                $categoria_id = 7; // Expediente
            } elseif (strpos($textoBusqueda, 'prueba') !== false || strpos($textoBusqueda, 'certificado') !== false || strpos($textoBusqueda, 'anexo') !== false) {
                $categoria_id = 3; // Evidencias
            } else {
                $categoria_id = 8; // Otros
            }

            // 3. Determinar tipo de archivo (extensión)
            $tipo_archivo = 'DESCONOCIDO';
            if (preg_match('/\.([a-zA-Z0-9]+)$/', $nombre, $matches)) {
                $tipo_archivo = strtoupper($matches[1]);
            }

            // 4. Insertar
            $stmt->execute([
                ':proceso_id'   => $proceso_id,
                ':categoria_id' => $categoria_id,
                ':nombre'       => $nombre,
                ':ruta'         => $ruta,
                ':tipo'         => $tipo_archivo,
                ':usuario'      => $usuario_id
            ]);
            $insertados++;
        }

        return ['insertados' => $insertados, 'omitidos' => $omitidos];
    }    
}
?>