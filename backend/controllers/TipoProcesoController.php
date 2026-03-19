<?php
//require_once __DIR__ . '/../libs/auth.php';
require_once __DIR__ . '/../models/TipoProceso.php';

//verificarToken();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$tipo = new TipoProceso();

if($action == 'list') {
    echo json_encode($tipo->getAll());
    exit;
}

if($action == 'get') {
    $id = $_GET['id'] ?? 0;
    echo json_encode($tipo->getById($id));
    exit;
}

if($action == 'create') {
    $data = [
        ':nombre' => $_POST['nombre'],
        ':descripcion' => $_POST['descripcion'] ?? null
    ];
    echo json_encode(['success' => $tipo->create($data)]);
    exit;
}

if($action == 'update') {
    $data = [
        ':id' => $_POST['id'],
        ':nombre' => $_POST['nombre'],
        ':descripcion' => $_POST['descripcion'] ?? null
    ];
    echo json_encode(['success' => $tipo->update($data)]);
    exit;
}

if($action == 'delete') {
    $id = $_POST['id'] ?? 0;
    echo json_encode(['success' => $tipo->delete($id)]);
    exit;
}
?>