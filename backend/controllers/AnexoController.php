<?php
require_once __DIR__ . '/../models/Anexo.php';
require_once __DIR__ . '/../libs/auth.php';

header('Content-Type: application/json');
verificarToken();

$action     = $_POST['action'] ?? $_GET['action'] ?? '';
$anexo      = new Anexo();
$upload_dir = __DIR__ . '/../../uploads/';

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Listar anexos de un proceso
if ($action === 'list') {
    $proceso_id = $_GET['proceso_id'] ?? 0;
    echo json_encode($anexo->getByProceso($proceso_id));
    exit;
}

// Listar categorías disponibles
if ($action === 'categorias') {
    echo json_encode($anexo->getCategorias());
    exit;
}

// Subir archivo
if ($action === 'upload') {
    $proceso_id  = $_POST['proceso_id']  ?? 0;
    $categoria_id = !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null;
    $file        = $_FILES['archivo']    ?? null;

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Error al recibir el archivo']);
        exit;
    }

    // ── Validación de seguridad (lista blanca + tamaño, del lado del servidor) ──
    $maxBytes   = 10 * 1024 * 1024; // 10 MB
    $permitidas = ['pdf','doc','docx','xls','xlsx','ppt','pptx','odt','ods','txt','rtf','csv',
                   'jpg','jpeg','png','gif','bmp','tif','tiff','webp','zip','rar','7z',
                   'mp3','wav','mp4','avi','mov','wmv','mpeg','mpg','m4a','ogg','msg','eml'];

    if ($file['size'] > $maxBytes) {
        echo json_encode(['success' => false, 'error' => 'El archivo supera el máximo de 10 MB']);
        exit;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $permitidas, true)) {
        echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido (.' . htmlspecialchars($extension) . ')']);
        exit;
    }

    // Doble extensión tipo "x.php.pdf": se rechaza si alguna parte es ejecutable
    if (preg_match('/\.(php\d?|phtml|phar|pht|cgi|pl|py|sh|exe|bat|cmd|js|html?|svg)(\.|$)/i', $file['name'])) {
        echo json_encode(['success' => false, 'error' => 'Nombre de archivo no permitido']);
        exit;
    }

    // El contenido real no puede ser código ejecutable ni HTML, aunque la extensión sea inocente
    $mime = function_exists('finfo_open') ? (string)finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']) : '';
    if (preg_match('#^(text/x-php|application/x-httpd-php|text/html|application/x-sh|application/x-dosexec|image/svg\+xml)#', $mime)) {
        echo json_encode(['success' => false, 'error' => 'El contenido del archivo no está permitido']);
        exit;
    }

    $nombre_unico = uniqid() . '.' . $extension;
    $ruta_destino = $upload_dir . $nombre_unico;

    if (move_uploaded_file($file['tmp_name'], $ruta_destino)) {
        $data = [
            ':proceso_id'    => $proceso_id,
            ':categoria_id'  => $categoria_id,
            ':nombre_archivo'=> $file['name'],
            ':ruta_archivo'  => 'uploads/' . $nombre_unico,
            ':tipo_archivo'  => $file['type'],
        ];
        echo json_encode(['success' => $anexo->create($data)]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al mover el archivo']);
    }
    exit;
}

// Eliminar archivo
if ($action === 'delete') {
    $id = $_POST['id'] ?? 0;

    $fila = $anexo->getById($id);
    if (!$fila) {
        echo json_encode(['success' => false, 'error' => 'Anexo no encontrado']);
        exit;
    }

    // No se permite borrar un anexo que la app trajo sola desde TYBA/SAMAI
    // (por actuación, o del expediente general del proceso) — es copia de
    // un documento oficial externo, no algo que el usuario deba poder
    // perder desde aquí. El frontend ya deshabilita el botón para estos,
    // esta es la validación real del lado del servidor.
    if ($anexo->esOrigenAutomatico($fila)) {
        echo json_encode(['success' => false, 'error' => 'Este documento fue traído automáticamente desde TYBA/SAMAI y no se puede eliminar']);
        exit;
    }

    $archivo = __DIR__ . '/../../' . $fila['ruta_archivo'];
    if (file_exists($archivo)) unlink($archivo);

    echo json_encode(['success' => $anexo->delete($id)]);
    exit;
}
?>