<?php
/**
 * PlantillaController — biblioteca de plantillas .docx y generación de
 * documentos con los datos de un proceso.
 *
 * Acciones:
 *   GET  list         (?todas=1 incluye inactivas)
 *   GET  variables    catálogo de variables {{grupo.campo}}
 *   GET  descargar    (?id=)  baja el .docx original de la plantilla
 *   POST generar      (plantilla_id, proceso_id, guardar=0|1) → devuelve el .docx
 *   POST subir        (nombre, categoria, descripcion, archivo .docx)
 *   POST reemplazar   (id, archivo .docx)   cambia el archivo de una plantilla
 *   POST actualizar   (id, nombre, categoria, descripcion, activo)
 *   POST eliminar     (id)
 */
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../models/Plantilla.php';
require_once __DIR__ . '/../models/Anexo.php';
require_once __DIR__ . '/../services/PlantillaService.php';
require_once __DIR__ . '/../libs/auth.php';

header('Content-Type: application/json');
$payload = verificarToken();
$usuarioId = (int)($payload['id'] ?? 0);

$action    = $_POST['action'] ?? $_GET['action'] ?? '';
$plantilla = new Plantilla();
$dir       = PlantillaService::dirPlantillas();

const PLANTILLA_MAX_BYTES = 5 * 1024 * 1024;

function responder($data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

/** Valida y guarda un .docx subido; devuelve el nombre de archivo guardado. */
function guardarDocxSubido(array $file, string $dir): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        responder(['success' => false, 'message' => 'No se recibió el archivo'], 400);
    }
    if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'docx') {
        responder(['success' => false, 'message' => 'La plantilla debe ser un archivo Word (.docx)'], 400);
    }
    if ($file['size'] > PLANTILLA_MAX_BYTES) {
        responder(['success' => false, 'message' => 'El archivo supera el límite de 5 MB'], 400);
    }
    if (!PlantillaService::esDocxValido($file['tmp_name'])) {
        responder(['success' => false, 'message' => 'El archivo no es un .docx válido'], 400);
    }
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $nombre = 'tpl_' . uniqid() . '.docx';
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $nombre)) {
        responder(['success' => false, 'message' => 'No se pudo guardar el archivo'], 500);
    }
    return $nombre;
}

function limpiarNombreArchivo(string $s): string {
    $s = preg_replace('/[\\\\\/:*?"<>|\r\n]+/', ' ', $s);
    return trim(preg_replace('/\s+/', ' ', $s));
}

/** Ruta absoluta del .docx de una plantilla, con protección contra rutas raras. */
function rutaPlantilla(string $dir, array $p): string {
    return $dir . '/' . basename($p['archivo']);
}

if ($action === 'list') {
    $todas = !empty($_GET['todas']);
    responder($plantilla->getAll(!$todas));
}

if ($action === 'variables') {
    $out = [];
    foreach (PlantillaService::catalogo() as $clave => [$etiqueta, $grupo]) {
        $out[] = ['clave' => $clave, 'etiqueta' => $etiqueta, 'grupo' => $grupo];
    }
    responder($out);
}

if ($action === 'descargar') {
    $p = $plantilla->getById((int)($_GET['id'] ?? 0));
    if (!$p || !is_file(rutaPlantilla($dir, $p))) responder(['success' => false, 'message' => 'Plantilla no encontrada'], 404);
    $ruta = rutaPlantilla($dir, $p);
    $nombre = limpiarNombreArchivo($p['nombre']) . ' (plantilla).docx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . rawurlencode($nombre) . '"; filename*=UTF-8\'\'' . rawurlencode($nombre));
    header('Content-Length: ' . filesize($ruta));
    readfile($ruta);
    exit;
}

if ($action === 'generar') {
    $p = $plantilla->getById((int)($_POST['plantilla_id'] ?? 0));
    if (!$p || !$p['activo']) responder(['success' => false, 'message' => 'Plantilla no disponible'], 404);
    $procesoId = (int)($_POST['proceso_id'] ?? 0);
    if ($procesoId <= 0) responder(['success' => false, 'message' => 'Selecciona un proceso'], 400);

    try {
        $datos = PlantillaService::construirDatos($procesoId, $usuarioId);
        $res   = PlantillaService::render(rutaPlantilla($dir, $p), $datos);
    } catch (Throwable $e) {
        error_log('PlantillaController generar: ' . $e->getMessage());
        responder(['success' => false, 'message' => $e->getMessage()], 500);
    }

    $nombreDoc = limpiarNombreArchivo($p['nombre'] . ' - ' . $datos['proceso.radicado']) . '.docx';

    // Copia opcional en Anexos del proceso
    $guardado = false;
    if (!empty($_POST['guardar'])) {
        $upDir = __DIR__ . '/../../uploads/';
        if (!is_dir($upDir)) mkdir($upDir, 0775, true);
        $nombreUnico = 'doc_' . uniqid() . '.docx';
        if (copy($res['archivo'], $upDir . $nombreUnico)) {
            $guardado = (new Anexo())->crearDocumentoGenerado($procesoId, $nombreDoc, 'uploads/' . $nombreUnico, $usuarioId ?: null);
        }
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . rawurlencode($nombreDoc) . '"; filename*=UTF-8\'\'' . rawurlencode($nombreDoc));
    header('Content-Length: ' . filesize($res['archivo']));
    header('X-Plantilla-Faltantes: ' . rawurlencode(json_encode($res['faltantes'], JSON_UNESCAPED_UNICODE)));
    header('X-Plantilla-Desconocidas: ' . rawurlencode(json_encode($res['desconocidas'])));
    header('X-Plantilla-Guardado: ' . ($guardado ? '1' : '0'));
    header('Access-Control-Expose-Headers: X-Plantilla-Faltantes, X-Plantilla-Desconocidas, X-Plantilla-Guardado, Content-Disposition');
    readfile($res['archivo']);
    @unlink($res['archivo']);
    exit;
}

if ($action === 'subir') {
    $nombre = trim($_POST['nombre'] ?? '');
    if ($nombre === '') responder(['success' => false, 'message' => 'El nombre es obligatorio'], 400);
    $archivo = guardarDocxSubido($_FILES['archivo'] ?? [], $dir);
    $id = $plantilla->create([
        'nombre'      => $nombre,
        'categoria'   => trim($_POST['categoria'] ?? '') ?: 'General',
        'descripcion' => trim($_POST['descripcion'] ?? '') ?: null,
        'archivo'     => $archivo,
        'usuario'     => $usuarioId ?: null,
    ]);
    responder(['success' => true, 'id' => $id]);
}

if ($action === 'reemplazar') {
    $id = (int)($_POST['id'] ?? 0);
    $p  = $plantilla->getById($id);
    if (!$p) responder(['success' => false, 'message' => 'Plantilla no encontrada'], 404);
    $nuevo = guardarDocxSubido($_FILES['archivo'] ?? [], $dir);
    $plantilla->setArchivo($id, $nuevo);
    // El archivo anterior se conserva si es una plantilla base del sistema
    // (no empieza con tpl_), para poder volver atrás; los subidos se limpian.
    if (strpos($p['archivo'], 'tpl_') === 0) @unlink(rutaPlantilla($dir, $p));
    responder(['success' => true]);
}

if ($action === 'actualizar') {
    $id = (int)($_POST['id'] ?? 0);
    $p  = $plantilla->getById($id);
    if (!$p) responder(['success' => false, 'message' => 'Plantilla no encontrada'], 404);
    $nombre = trim($_POST['nombre'] ?? $p['nombre']);
    if ($nombre === '') responder(['success' => false, 'message' => 'El nombre es obligatorio'], 400);
    responder(['success' => $plantilla->update($id, [
        'nombre'      => $nombre,
        'categoria'   => trim($_POST['categoria'] ?? $p['categoria']) ?: 'General',
        'descripcion' => array_key_exists('descripcion', $_POST) ? (trim($_POST['descripcion']) ?: null) : $p['descripcion'],
        'activo'      => isset($_POST['activo']) ? (int)(bool)$_POST['activo'] : (int)$p['activo'],
    ])]);
}

if ($action === 'eliminar') {
    $id = (int)($_POST['id'] ?? 0);
    $p  = $plantilla->getById($id);
    if (!$p) responder(['success' => false, 'message' => 'Plantilla no encontrada'], 404);
    $ok = $plantilla->delete($id);
    if ($ok && strpos($p['archivo'], 'tpl_') === 0) @unlink(rutaPlantilla($dir, $p));
    responder(['success' => $ok]);
}

responder(['success' => false, 'message' => 'Acción no válida'], 400);