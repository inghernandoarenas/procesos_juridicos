<?php
require_once __DIR__ . '/../models/Municipio.php';
header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$model  = new Municipio();

if ($action === 'list')              { echo json_encode($model->getAll()); exit; }
if ($action === 'byDepartamento')    { echo json_encode($model->getByDepartamento($_GET['departamento_id'] ?? 0)); exit; }
if ($action === 'get')               { echo json_encode($model->getById($_GET['id'] ?? 0)); exit; }
if ($action === 'create') {
    $id = $model->create([':departamento_id' => $_POST['departamento_id'], ':nombre' => trim($_POST['nombre'] ?? '')]);
    echo json_encode(['success' => (bool)$id, 'id' => $id]);
    exit;
}
if ($action === 'update') {
    echo json_encode(['success' => $model->update([':id' => $_POST['id'], ':departamento_id' => $_POST['departamento_id'], ':nombre' => trim($_POST['nombre'] ?? '')])]);
    exit;
}
if ($action === 'delete') { echo json_encode(['success' => $model->delete($_POST['id'] ?? 0)]); exit; }
?>