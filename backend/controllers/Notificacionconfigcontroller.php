<?php
require_once __DIR__ . '/../models/NotificacionConfig.php';
require_once __DIR__ . '/../models/Usuario.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$config = new NotificacionConfig();

if ($action === 'list') {
    echo json_encode($config->getAll());
    exit;
}

if ($action === 'get') {
    $id = $_GET['id'] ?? 0;
    echo json_encode($config->getById($id));
    exit;
}

if ($action === 'get_usuario') {
    $id      = $_GET['id'] ?? 0;
    $usuario = new Usuario();
    $u       = $usuario->getById($id);
    if ($u) {
        echo json_encode([
            'email'    => $u['email']    ?? '',
            'telefono' => $u['telefono'] ?? '',
        ]);
    } else {
        echo json_encode(null);
    }
    exit;
}

if ($action === 'create') {
    $data = [
        'usuario_id' => $_POST['usuario_id'],
        'tipo'       => $_POST['tipo'],
        'email'      => !empty($_POST['email'])    ? $_POST['email']    : null,
        'telefono'   => !empty($_POST['telefono']) ? $_POST['telefono'] : null,
    ];
    echo json_encode(['success' => $config->create($data)]);
    exit;
}

if ($action === 'update') {
    $data = [
        'id'         => $_POST['id'],
        'usuario_id' => $_POST['usuario_id'],
        'tipo'       => $_POST['tipo'],
        'email'      => !empty($_POST['email'])    ? $_POST['email']    : null,
        'telefono'   => !empty($_POST['telefono']) ? $_POST['telefono'] : null,
    ];
    echo json_encode(['success' => $config->update($data)]);
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? 0;
    echo json_encode(['success' => $config->delete($id)]);
    exit;
}
?>