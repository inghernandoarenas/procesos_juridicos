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
        // "origen_automatico" marca los anexos que la app trajo sola desde
        // TYBA/SAMAI (por actuación, o del expediente general del proceso)
        // — nunca subidos a mano por el usuario. Se detectan por
        // actuacion_id (anexos por actuación) o por el prefijo del archivo
        // guardado (tyba_/samai_, ver Anexo::guardarArchivoBase64) que los
        // anexos subidos manualmente (uniqid() sin prefijo) no tienen.
        // Extras para la vista de tabla: "origen" (TYBA/SAMAI/Manual),
        // la actuación a la que pertenece el documento (si aplica) y quién
        // lo subió (solo los manuales).
        $query = "SELECT a.*, ac.nombre AS categoria_nombre,
                  CASE WHEN a.actuacion_id IS NOT NULL
                       OR a.ruta_archivo LIKE 'uploads/tyba\\_%'
                       OR a.ruta_archivo LIKE 'uploads/samai\\_%'
                  THEN 1 ELSE 0 END AS origen_automatico,
                  CASE WHEN a.ruta_archivo LIKE 'uploads/tyba\\_%'  THEN 'TYBA'
                       WHEN a.ruta_archivo LIKE 'uploads/samai\\_%' THEN 'SAMAI'
                       ELSE 'Manual' END AS origen,
                  act.actuacion AS actuacion_nombre,
                  act.fecha     AS actuacion_fecha,
                  u.nombre      AS subido_por
                  FROM " . $this->table . " a
                  LEFT JOIN anexo_categorias ac ON a.categoria_id = ac.id
                  LEFT JOIN actuaciones act     ON a.actuacion_id = act.id
                  LEFT JOIN usuarios u          ON a.usuario_creacion = u.id
                  WHERE a.proceso_id = :proceso_id
                  ORDER BY ac.nombre ASC, a.fecha_subida DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':proceso_id', $proceso_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Un solo anexo por id — usado para validar su origen antes de borrar. */
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** true si el anexo vino solo (TYBA/SAMAI), no se puede borrar desde la app. */
    public function esOrigenAutomatico(array $anexo): bool {
        if (!empty($anexo['actuacion_id'])) return true;
        $ruta = $anexo['ruta_archivo'] ?? '';
        return (bool) preg_match('#^uploads/(tyba|samai)_#', $ruta);
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

    /**
     * Guarda en Anexos la copia de un documento generado desde una plantilla,
     * en la categoría "Documentos generados" (si existe). Queda como documento
     * "Manual" (se puede eliminar), no como automático de TYBA/SAMAI.
     */
    public function crearDocumentoGenerado(int $proceso_id, string $nombre, string $ruta, ?int $usuario_id = null): bool {
        $cat = $this->conn->prepare("SELECT id FROM anexo_categorias WHERE nombre = 'Documentos generados' LIMIT 1");
        $cat->execute();
        $catId = $cat->fetchColumn() ?: null;
        $stmt = $this->conn->prepare(
            "INSERT INTO " . $this->table . "
             (proceso_id, categoria_id, nombre_archivo, ruta_archivo, tipo_archivo, usuario_creacion)
             VALUES (:p, :c, :n, :r, 'DOCX', :u)"
        );
        return $stmt->execute([':p' => $proceso_id, ':c' => $catId, ':n' => $nombre, ':r' => $ruta, ':u' => $usuario_id]);
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

            $guardado = $this->guardarArchivoBase64($nombre, $anexo['contenido_base64'] ?? null, $anexo['tipo'] ?? null, $upload_dir);
            if ($guardado === null) {
                $omitidos++;
                continue;
            }

            $stmt->execute([
                ':proceso_id'   => $proceso_id,
                ':categoria_id' => $this->determinarCategoria($nombre, $anexo['tipo'] ?? ''),
                ':nombre'       => $nombre,
                ':ruta'         => $guardado['ruta'],
                ':tipo'         => $guardado['tipo'],
                ':usuario'      => $usuario_id,
            ]);
            $insertados++;
        }

        return ['insertados' => $insertados, 'omitidos' => $omitidos];
    }

    /**
     * Inserta los anexos propios de CADA actuación (distinto de los anexos
     * generales del proceso que maneja insertarLoteTyba). $anexosPorActuacion
     * viene de ApiTyba::consultarAnexosPorActuacion():
     *   [ { id_api: 'TYBA_xxxx', archivos: [{nombre,tipo,contenido_base64}, ...] }, ... ]
     *
     * El id_api se usa para encontrar la fila real en `actuaciones` (misma
     * tabla/columna que ya usa Actuacion::insertarLote para deduplicar) y
     * así guardar el anexo con su actuacion_id correcto.
     */
    public function insertarLoteTybaPorActuacion(array $anexosPorActuacion, int $proceso_id, ?int $usuario_id = null): array {
        if (empty($anexosPorActuacion)) return ['insertados' => 0, 'omitidos' => 0, 'sin_actuacion' => 0];

        $insertados    = 0;
        $omitidos      = 0;
        $sinActuacion  = 0;

        $buscarActuacion = $this->conn->prepare(
            "SELECT id FROM actuaciones WHERE proceso_id = :proceso_id AND id_api = :id_api LIMIT 1"
        );

        $checkQuery = "SELECT id FROM " . $this->table . "
                       WHERE proceso_id = :proceso_id AND actuacion_id = :actuacion_id AND nombre_archivo = :nombre LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);

        $insertQuery = "INSERT INTO " . $this->table . "
                        (proceso_id, actuacion_id, categoria_id, nombre_archivo, ruta_archivo, tipo_archivo, usuario_creacion)
                        VALUES (:proceso_id, :actuacion_id, :categoria_id, :nombre, :ruta, :tipo, :usuario)";
        $stmt = $this->conn->prepare($insertQuery);

        $upload_dir = __DIR__ . '/../../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($anexosPorActuacion as $grupo) {
            $idApi    = $grupo['id_api'] ?? null;
            $archivos = $grupo['archivos'] ?? [];
            if (!$idApi || empty($archivos)) continue;

            $buscarActuacion->execute([':proceso_id' => $proceso_id, ':id_api' => $idApi]);
            $actuacionRow = $buscarActuacion->fetch(PDO::FETCH_ASSOC);
            if (!$actuacionRow) {
                // No debería pasar (las actuaciones se guardan antes que sus anexos),
                // pero si pasa, no hay a qué actuacion_id asociarlo — se omite.
                error_log("Anexo::insertarLoteTybaPorActuacion: no se encontró actuación con id_api={$idApi} para proceso_id={$proceso_id}");
                $sinActuacion += count($archivos);
                continue;
            }
            $actuacionId = $actuacionRow['id'];

            foreach ($archivos as $anexo) {
                $nombre = trim($anexo['nombre'] ?? '');
                if ($nombre === '') {
                    $omitidos++;
                    continue;
                }

                $checkStmt->execute([':proceso_id' => $proceso_id, ':actuacion_id' => $actuacionId, ':nombre' => $nombre]);
                if ($checkStmt->fetch()) {
                    $omitidos++;
                    continue;
                }

                $guardado = $this->guardarArchivoBase64($nombre, $anexo['contenido_base64'] ?? null, $anexo['tipo'] ?? null, $upload_dir);
                if ($guardado === null) {
                    $omitidos++;
                    continue;
                }

                $ok = $stmt->execute([
                    ':proceso_id'   => $proceso_id,
                    ':actuacion_id' => $actuacionId,
                    ':categoria_id' => $this->determinarCategoria($nombre, $anexo['tipo'] ?? ''),
                    ':nombre'       => $nombre,
                    ':ruta'         => $guardado['ruta'],
                    ':tipo'         => $guardado['tipo'],
                    ':usuario'      => $usuario_id,
                ]);
                // FIX: antes no se revisaba si el INSERT realmente funcionó —
                // si fallaba por cualquier motivo (FK inválida, dato muy largo,
                // etc.) igual se contaba como "insertado" sin dejar rastro.
                if ($ok) {
                    $insertados++;
                } else {
                    error_log("Anexo::insertarLoteTybaPorActuacion: INSERT falló para '{$nombre}' (actuacion_id={$actuacionId}): " . json_encode($stmt->errorInfo()));
                    $omitidos++;
                }
            }
        }

        return ['insertados' => $insertados, 'omitidos' => $omitidos, 'sin_actuacion' => $sinActuacion];
    }

    /**
     * Inserta los documentos del "expediente" general de SAMAI (link
     * "Visualizar expediente"). Estructuralmente idéntico a insertarLoteTyba
     * (anexos generales del proceso, NO por actuación — SAMAI no tiene eso),
     * solo cambia el prefijo del archivo guardado (samai_ en vez de tyba_)
     * para distinguir el origen en uploads/.
     */
    public function insertarLoteSamai(array $anexosSamai, int $proceso_id, ?int $usuario_id = null): array {
        if (empty($anexosSamai)) return ['insertados' => 0, 'omitidos' => 0];

        $insertados = 0;
        $omitidos   = 0;

        $query = "INSERT INTO " . $this->table . "
                  (proceso_id, categoria_id, nombre_archivo, ruta_archivo, tipo_archivo, usuario_creacion)
                  VALUES (:proceso_id, :categoria_id, :nombre, :ruta, :tipo, :usuario)";
        $stmt = $this->conn->prepare($query);

        // Mismo criterio de deduplicación que insertarLoteTyba: solo por
        // nombre_archivo (ruta_archivo siempre es única por uniqid()).
        $checkQuery = "SELECT id FROM " . $this->table . "
                       WHERE proceso_id = :proceso_id AND nombre_archivo = :nombre LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);

        $upload_dir = __DIR__ . '/../../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($anexosSamai as $anexo) {
            $nombre = trim($anexo['nombre'] ?? '');
            if ($nombre === '') {
                $omitidos++;
                continue;
            }

            $checkStmt->execute([':proceso_id' => $proceso_id, ':nombre' => $nombre]);
            if ($checkStmt->fetch()) {
                $omitidos++;
                continue;
            }

            $guardado = $this->guardarArchivoBase64($nombre, $anexo['contenido_base64'] ?? null, $anexo['tipo'] ?? null, $upload_dir, 'samai_');
            if ($guardado === null) {
                $omitidos++;
                continue;
            }

            $ok = $stmt->execute([
                ':proceso_id'   => $proceso_id,
                ':categoria_id' => $this->determinarCategoria($nombre, $anexo['tipo'] ?? ''),
                ':nombre'       => $nombre,
                ':ruta'         => $guardado['ruta'],
                ':tipo'         => $guardado['tipo'],
                ':usuario'      => $usuario_id,
            ]);
            if ($ok) {
                $insertados++;
            } else {
                error_log("Anexo::insertarLoteSamai: INSERT falló para '{$nombre}' (proceso_id={$proceso_id}): " . json_encode($stmt->errorInfo()));
                $omitidos++;
            }
        }

        return ['insertados' => $insertados, 'omitidos' => $omitidos];
    }

    /** Decodifica un anexo en base64 y lo guarda en uploads/. Null si falla. */
    private function guardarArchivoBase64(string $nombre, ?string $b64, ?string $tipoSugerido, string $upload_dir, string $prefijo = 'tyba_'): ?array {
        if (!$b64) return null;

        $contenido = base64_decode($b64, true);
        if ($contenido === false || strlen($contenido) === 0) {
            error_log("Anexo: no se pudo decodificar '{$nombre}'");
            return null;
        }

        $extension    = $this->detectarExtension($contenido, $nombre, $tipoSugerido);
        $nombre_unico = uniqid($prefijo) . '.' . $extension;
        $ruta_destino = $upload_dir . $nombre_unico;
        if (file_put_contents($ruta_destino, $contenido) === false) {
            error_log("Anexo: no se pudo guardar '{$nombre}'");
            return null;
        }

        return ['ruta' => 'uploads/' . $nombre_unico, 'tipo' => strtoupper($extension)];
    }

    /**
     * Determina la extensión REAL del archivo. Antes se tomaba del nombre que
     * enviaba el portal y salían cosas como ".674 kb" (el nombre incluía el
     * tamaño) o extensiones peligrosas. Ahora: 1) contenido real (finfo),
     * 2) extensión del nombre solo si está en la lista permitida,
     * 3) tipo sugerido si está permitido, 4) "bin" (nunca ejecutable).
     */
    private function detectarExtension(string $contenido, string $nombre, ?string $tipoSugerido): string {
        $permitidas = ['pdf','doc','docx','xls','xlsx','ppt','pptx','odt','ods','txt','rtf','csv',
                       'jpg','jpeg','png','gif','bmp','tif','tiff','webp','zip','rar','7z',
                       'mp3','wav','mp4','avi','mov','wmv','mpeg','mpg','m4a','ogg','msg','eml'];
        $porMime = [
            'application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif',
            'image/bmp' => 'bmp', 'image/tiff' => 'tif', 'image/webp' => 'webp', 'application/zip' => 'zip',
            'application/x-rar' => 'rar', 'application/vnd.rar' => 'rar', 'application/x-7z-compressed' => '7z',
            'audio/mpeg' => 'mp3', 'audio/x-wav' => 'wav', 'video/mp4' => 'mp4', 'video/x-msvideo' => 'avi',
            'video/quicktime' => 'mov', 'text/plain' => 'txt', 'application/rtf' => 'rtf', 'text/rtf' => 'rtf',
            'application/msword' => 'doc', 'application/vnd.ms-excel' => 'xls', 'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        ];

        $extNombre = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        $extNombre = in_array($extNombre, $permitidas, true) ? $extNombre : '';

        $mime = '';
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            if ($f) { $mime = (string)finfo_buffer($f, $contenido); finfo_close($f); }
        }
        $extMime = $porMime[$mime] ?? '';

        // Si el contenido es un contenedor genérico (zip/ole/texto) y el nombre
        // aporta una extensión permitida compatible (docx, xlsx, csv...), se respeta el nombre.
        if ($extMime === 'zip' && in_array($extNombre, ['docx','xlsx','pptx','odt','ods','zip'], true)) return $extNombre;
        if ($extMime === 'txt' && in_array($extNombre, ['txt','csv','eml'], true)) return $extNombre;
        if ($extMime !== '') return $extMime;
        if ($mime === 'application/x-ole-storage' || $mime === 'application/CDFV2') {
            if (in_array($extNombre, ['doc','xls','ppt','msg'], true)) return $extNombre;
        }
        if ($extNombre !== '') return $extNombre;

        $sug = strtolower(trim((string)$tipoSugerido));
        if (in_array($sug, $permitidas, true)) return $sug;
        return 'bin';
    }

    /** Determina categoria_id a partir del nombre/tipo del documento. */
    private function determinarCategoria(string $nombre, string $tipo): int {
        $textoBusqueda = strtolower($nombre . ' ' . $tipo);
        if (strpos($textoBusqueda, 'auto') !== false || strpos($textoBusqueda, 'sentencia') !== false || strpos($textoBusqueda, 'proveído') !== false || strpos($textoBusqueda, 'interlocutorio') !== false) {
            return 4; // Respuestas del juez
        }
        if (strpos($textoBusqueda, 'demanda') !== false || strpos($textoBusqueda, 'tutela') !== false || strpos($textoBusqueda, 'escrito') !== false) {
            return 7; // Expediente
        }
        if (strpos($textoBusqueda, 'prueba') !== false || strpos($textoBusqueda, 'certificado') !== false || strpos($textoBusqueda, 'anexo') !== false) {
            return 3; // Evidencias
        }
        return 8; // Otros
    }
}
?>