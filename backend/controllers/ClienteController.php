<?php
require_once __DIR__ . '/../models/Cliente.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$cliente = new Cliente();

if($action == 'list') {
    echo json_encode($cliente->getAll());
    exit;
}

if($action == 'get') {
    $id = $_GET['id'] ?? 0;
    echo json_encode($cliente->getById($id));
    exit;
}

if($action == 'create') {
    $data = [
        ':nombre' => $_POST['nombre'],
        ':apellido' => $_POST['apellido'],
        ':email' => $_POST['email'],
        ':telefono' => $_POST['telefono'],
        ':direccion' => $_POST['direccion']
    ];
    echo json_encode(['success' => $cliente->create($data)]);
    exit;
}

if($action == 'update') {
    $data = [
        ':id' => $_POST['id'],
        ':nombre' => $_POST['nombre'],
        ':apellido' => $_POST['apellido'],
        ':email' => $_POST['email'],
        ':telefono' => $_POST['telefono'],
        ':direccion' => $_POST['direccion']
    ];
    echo json_encode(['success' => $cliente->update($data)]);
    exit;
}

if($action == 'delete') {
    $id = $_POST['id'] ?? 0;
    echo json_encode(['success' => $cliente->delete($id)]);
    exit;
}
?>