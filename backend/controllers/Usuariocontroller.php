<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../libs/auth.php';

header('Content-Type: application/json');
$payload = verificarToken();

$action  = $_POST['action'] ?? $_GET['action'] ?? '';
$usuario = new Usuario();

// Preferencias del dashboard del usuario autenticado (id tomado del JWT,
// nunca del request: cada usuario solo lee/escribe las suyas).
if ($action === 'get_preferencias') {
    echo json_encode(['success' => true, 'dashboard' => $usuario->getPreferenciasDashboard((int)$payload['id'])]);
    exit;
}

if ($action === 'save_preferencias') {
    $visibles = json_decode($_POST['dashboard'] ?? '', true);
    if (!is_array($visibles)) {
        echo json_encode(['success' => false, 'message' => 'Formato inválido']);
        exit;
    }
    $visibles = array_values(array_filter($visibles, 'is_string'));
    echo json_encode(['success' => $usuario->savePreferenciasDashboard((int)$payload['id'], $visibles)]);
    exit;
}

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