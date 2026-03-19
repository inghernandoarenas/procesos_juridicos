<?php
require_once __DIR__ . '/../models/Anexo.php';
require_once __DIR__ . '/../libs/auth.php';

// Verificar token (excepto para opciones públicas si las hubiera)
/*
$acciones_publicas = ['login']; // si tuvieras acciones públicas

if(!in_array($action, $acciones_publicas)) {
    $usuario = verificarToken();
    // $usuario contiene los datos del usuario autenticado
    // Podrías usarlo para auditoría
}
*/

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$anexo = new Anexo();
$upload_dir = __DIR__ . '/../../uploads/';

if(!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if($action == 'list') {
    $proceso_id = $_GET['proceso_id'] ?? 0;
    echo json_encode($anexo->getByProceso($proceso_id));
    exit;
}

if($action == 'upload') {
    $proceso_id = $_POST['proceso_id'];
    $file = $_FILES['archivo'];
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombre_unico = uniqid() . '.' . $extension;
    $ruta_destino = $upload_dir . $nombre_unico;
    
    if(move_uploaded_file($file['tmp_name'], $ruta_destino)) {
        $data = [
            ':proceso_id' => $proceso_id,
            ':nombre_archivo' => $file['name'],
            ':ruta_archivo' => 'uploads/' . $nombre_unico,
            ':tipo_archivo' => $file['type']
        ];
        echo json_encode(['success' => $anexo->create($data)]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al subir archivo']);
    }
    exit;
}

if($action == 'delete') {
    $id = $_POST['id'] ?? 0;
    // Primero obtener la ruta para eliminar el archivo físico
    $anexos = $anexo->getByProceso($_POST['proceso_id'] ?? 0);
    foreach($anexos as $a) {
        if($a['id'] == $id) {
            $archivo = __DIR__ . '/../../' . $a['ruta_archivo'];
            if(file_exists($archivo)) {
                unlink($archivo);
            }
            break;
        }
    }
    echo json_encode(['success' => $anexo->delete($id)]);
    exit;
}
?>