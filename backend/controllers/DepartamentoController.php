<?php
require_once __DIR__ . '/../models/Departamento.php';
header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$model  = new Departamento();

if ($action === 'list')   { echo json_encode($model->getAll()); exit; }
if ($action === 'get')    { echo json_encode($model->getById($_GET['id'] ?? 0)); exit; }
if ($action === 'create') {
    $id = $model->create([':nombre' => trim($_POST['nombre'] ?? '')]);
    echo json_encode(['success' => (bool)$id, 'id' => $id]);
    exit;
}
if ($action === 'update') {
    echo json_encode(['success' => $model->update([':id' => $_POST['id'], ':nombre' => trim($_POST['nombre'] ?? '')])]);
    exit;
}
if ($action === 'delete') { echo json_encode(['success' => $model->delete($_POST['id'] ?? 0)]); exit; }
?>