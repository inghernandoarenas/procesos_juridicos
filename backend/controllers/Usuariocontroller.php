<?php
require_once __DIR__ . '/../models/Usuario.php';

header('Content-Type: application/json');

$action  = $_POST['action'] ?? $_GET['action'] ?? '';
$usuario = new Usuario();

if ($action === 'list') {
    echo json_encode($usuario->getAll());
    exit;
}

if ($action === 'get') {
    $id = $_GET['id'] ?? 0;
    echo json_encode($usuario->getById($id));
    exit;
}

if ($action === 'create') {
    // Validar duplicados
    if ($usuario->emailExiste($_POST['email'])) {
        echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
        exit;
    }
    if ($usuario->usuarioExiste($_POST['usuario'])) {
        echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya existe']);
        exit;
    }

    $data = [
        ':nombre'   => $_POST['nombre'],
        ':email'    => $_POST['email'],
        ':usuario'  => $_POST['usuario'],
        ':password' => $_POST['password'],
        ':telefono' => $_POST['telefono'] ?? null,
    ];
    echo json_encode(['success' => $usuario->create($data)]);
    exit;
}

if ($action === 'update') {
    $id = $_POST['id'];

    if ($usuario->emailExiste($_POST['email'], $id)) {
        echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
        exit;
    }
    if ($usuario->usuarioExiste($_POST['usuario'], $id)) {
        echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya existe']);
        exit;
    }

    $data = [
        ':id'       => $id,
        ':nombre'   => $_POST['nombre'],
        ':email'    => $_POST['email'],
        ':usuario'  => $_POST['usuario'],
        ':password' => $_POST['password'] ?? '',
        ':telefono' => $_POST['telefono'] ?? null,
    ];
    echo json_encode(['success' => $usuario->update($data)]);
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? 0;
    echo json_encode(['success' => $usuario->delete($id)]);
    exit;
}
?>