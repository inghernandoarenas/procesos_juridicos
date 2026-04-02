<?php
require_once __DIR__ . '/../models/Honorario.php';

header('Content-Type: application/json');

$action    = $_POST['action'] ?? $_GET['action'] ?? '';
$honorario = new Honorario();

// Actualizar vencidos en cada consulta
$honorario->actualizarVencidos();

if ($action === 'list') {
    $proceso_id = $_GET['proceso_id'] ?? 0;
    echo json_encode($honorario->getByProceso($proceso_id));
    exit;
}

if ($action === 'resumen') {
    $proceso_id = $_GET['proceso_id'] ?? 0;
    echo json_encode($honorario->getResumenProceso($proceso_id));
    exit;
}

if ($action === 'resumen_global') {
    echo json_encode($honorario->getResumenGlobal());
    exit;
}

if ($action === 'get') {
    $id = $_GET['id'] ?? 0;
    echo json_encode($honorario->getById($id));
    exit;
}

if ($action === 'create') {
    $data = [
        'proceso_id'      => $_POST['proceso_id'],
        'concepto'        => $_POST['concepto'],
        'tipo'            => $_POST['tipo'],
        'valor'           => $_POST['valor'],
        'fecha_causacion' => $_POST['fecha_causacion'],
        'fecha_pago'      => !empty($_POST['fecha_pago']) ? $_POST['fecha_pago'] : null,
        'estado'          => $_POST['estado'] ?? 'pendiente',
        'observaciones'   => $_POST['observaciones'] ?? null,
    ];
    echo json_encode(['success' => $honorario->create($data)]);
    exit;
}

if ($action === 'update') {
    $data = [
        'id'              => $_POST['id'],
        'concepto'        => $_POST['concepto'],
        'tipo'            => $_POST['tipo'],
        'valor'           => $_POST['valor'],
        'fecha_causacion' => $_POST['fecha_causacion'],
        'fecha_pago'      => !empty($_POST['fecha_pago']) ? $_POST['fecha_pago'] : null,
        'estado'          => $_POST['estado'],
        'observaciones'   => $_POST['observaciones'] ?? null,
    ];
    echo json_encode(['success' => $honorario->update($data)]);
    exit;
}

if ($action === 'pagar') {
    $id         = $_POST['id'] ?? 0;
    $fecha_pago = $_POST['fecha_pago'] ?? date('Y-m-d');
    echo json_encode(['success' => $honorario->marcarPagado($id, $fecha_pago)]);
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? 0;
    echo json_encode(['success' => $honorario->delete($id)]);
    exit;
}
?>