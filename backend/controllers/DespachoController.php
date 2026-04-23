<?php
require_once __DIR__ . '/../models/Despacho.php';
header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$model  = new Despacho();

if ($action === 'list')   { echo json_encode($model->getAll()); exit; }
if ($action === 'search') { echo json_encode($model->search($_GET['q'] ?? '', 30)); exit; }
if ($action === 'get')    { echo json_encode($model->getById($_GET['id'] ?? 0)); exit; }

if ($action === 'create') {
    $id = $model->create([
        ':nombre'          => trim($_POST['nombre']         ?? ''),
        ':codigo_oficial'  => trim($_POST['codigo_oficial'] ?? '') ?: null,
        ':descripcion'     => trim($_POST['descripcion']    ?? '') ?: null,
        ':entidad_id'      => $_POST['entidad_id']          ?: null,
        ':departamento_id' => $_POST['departamento_id']     ?: null,
        ':municipio_id'    => $_POST['municipio_id']        ?: null,
    ]);
    echo json_encode(['success' => (bool)$id, 'id' => $id]);
    exit;
}

if ($action === 'update') {
    echo json_encode(['success' => $model->update([
        ':id'              => $_POST['id'],
        ':nombre'          => trim($_POST['nombre']         ?? ''),
        ':codigo_oficial'  => trim($_POST['codigo_oficial'] ?? '') ?: null,
        ':descripcion'     => trim($_POST['descripcion']    ?? '') ?: null,
        ':entidad_id'      => $_POST['entidad_id']          ?: null,
        ':departamento_id' => $_POST['departamento_id']     ?: null,
        ':municipio_id'    => $_POST['municipio_id']        ?: null,
    ])]);
    exit;
}

if ($action === 'delete') { echo json_encode(['success' => $model->delete($_POST['id'] ?? 0)]); exit; }
?>