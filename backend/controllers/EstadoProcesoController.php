<?php
//equire_once __DIR__ . '/../libs/auth.php';
require_once __DIR__ . '/../models/EstadoProceso.php';

//verificarToken();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$estado = new EstadoProceso();

if($action == 'list') {
    echo json_encode($estado->getAll());
    exit;
}

if($action == 'get') {
    $id = $_GET['id'] ?? 0;
    echo json_encode($estado->getById($id));
    exit;
}

if($action == 'create') {
    $data = [
        ':nombre' => $_POST['nombre'],
        ':color' => $_POST['color'] ?? '#3498db'
    ];
    echo json_encode(['success' => $estado->create($data)]);
    exit;
}

if($action == 'update') {
    $data = [
        ':id' => $_POST['id'],
        ':nombre' => $_POST['nombre'],
        ':color' => $_POST['color'] ?? '#3498db'
    ];
    echo json_encode(['success' => $estado->update($data)]);
    exit;
}

if($action == 'delete') {
    $id = $_POST['id'] ?? 0;
    echo json_encode(['success' => $estado->delete($id)]);
    exit;
}
?>