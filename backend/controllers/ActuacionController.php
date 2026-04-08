<?php
require_once __DIR__ . '/../models/Actuacion.php';
require_once __DIR__ . '/../libs/auth.php';

$action    = $_POST['action'] ?? $_GET['action'] ?? '';
$actuacion = new Actuacion();

if ($action == 'list') {
    $proceso_id = (int)($_GET['proceso_id'] ?? 0);
    $data = $actuacion->getByProceso($proceso_id);

    // Log temporal para diagnosticar discrepancia BD vs frontend
    $logFile = __DIR__ . '/../../logs/rama_sync.log';
    $ts      = date('H:i:s');
    file_put_contents($logFile,
        "[$ts] list proceso_id=$proceso_id — filas BD: " . count($data) .
        " — nulas_fecha: " . count(array_filter($data, fn($r) => empty($r['fecha']))) .
        " — nulos_id: "   . count(array_filter($data, fn($r) => empty($r['id']))) . "\n",
        FILE_APPEND
    );

    echo json_encode($data);
    exit;
}

if ($action == 'create') {
    $data = [
        ':proceso_id'    => $_POST['proceso_id'],
        ':id_api'        => null,
        ':despacho'      => $_POST['despacho'] ?? null,
        ':fecha'         => $_POST['fecha'],
        ':actuacion'     => $_POST['actuacion'],
        ':observaciones' => $_POST['observaciones'] ?? null
    ];
    echo json_encode(['success' => $actuacion->create($data)]);
    exit;
}

if ($action == 'delete') {
    $id = $_POST['id'] ?? 0;
    echo json_encode(['success' => $actuacion->delete($id)]);
    exit;
}
?>