<?php
require_once __DIR__ . '/../models/Actuacion.php';
require_once __DIR__ . '/../libs/auth.php';

// Verificar token (excepto para opciones públicas si las hubiera)

/*$acciones_publicas = ['login']; // si tuvieras acciones públicas

if(!in_array($action, $acciones_publicas)) {
    $usuario = verificarToken();
    // $usuario contiene los datos del usuario autenticado
    // Podrías usarlo para auditoría
}
*/

//file_put_contents('debug_log.txt', "Inicio sincronización\n", FILE_APPEND);
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$actuacion = new Actuacion();

if($action == 'list') {
    $proceso_id = $_GET['proceso_id'] ?? 0;
    echo json_encode($actuacion->getByProceso($proceso_id));
    exit;
}

if($action == 'create') {
    $data = [
        ':proceso_id' => $_POST['proceso_id'],
        ':fecha' => $_POST['fecha'],
        ':actuacion' => $_POST['actuacion'],
        ':observaciones' => $_POST['observaciones'] ?? null
    ];
    echo json_encode(['success' => $actuacion->create($data)]);
    exit;
}

if($action == 'delete') {
    $id = $_POST['id'] ?? 0;
    echo json_encode(['success' => $actuacion->delete($id)]);
    exit;
}
?>