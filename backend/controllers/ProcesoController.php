<?php
require_once __DIR__ . '/../models/Proceso.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../libs/auth.php';

// Verificar token (excepto para opciones públicas si las hubiera)
/*
$acciones_publicas = ['login']; // si tuvieras acciones públicas

if(!in_array($action, $acciones_publicas)) {
    $usuario = verificarToken();
    // $usuario contiene los datos del usuario autenticado
    // Podrías usarlo para auditoría
}
*/

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$proceso = new Proceso();

if($action == 'list') {
    $pagina = $_GET['pagina'] ?? 1;
    $buscar = $_GET['buscar'] ?? '';
    $por_pagina = 6;
    $inicio = ($pagina - 1) * $por_pagina;
    
    $resultado = $proceso->getAllPaginated($inicio, $por_pagina, $buscar);
    echo json_encode($resultado);
    exit;
}

if($action == 'get') {
    $id = $_GET['id'] ?? 0;
    echo json_encode($proceso->getById($id));
    exit;
}

if($action == 'getClientes') {
    $cliente = new Cliente();
    echo json_encode($cliente->getAll());
    exit;
}

if($action == 'getByCliente') {
    $cliente_id = $_GET['cliente_id'] ?? 0;
    echo json_encode($proceso->getByCliente($cliente_id));
    exit;
}

if($action == 'create') {
    $data = [
        ':cliente_id'        => $_POST['cliente_id'],
        ':tipo_proceso_id'   => $_POST['tipo_proceso_id'] ?: null,
        ':estado_proceso_id' => $_POST['estado_proceso_id'] ?: null,
        ':numero_radicado'   => $_POST['numero_radicado'],
        ':descripcion'       => $_POST['descripcion'],
        ':fecha_inicio'      => $_POST['fecha_inicio'],
        ':fecha_vencimiento' => $_POST['fecha_vencimiento'] ?: null,
        ':es_privado'        => isset($_POST['es_privado']) ? 1 : 0,
        ':fuente_consulta'   => $_POST['fuente_consulta'] ?? 'ninguna',
    ];
    echo json_encode(['success' => $proceso->create($data)]);
    exit;
}

if($action == 'update') {
    $data = [
        ':id'                => $_POST['id'],
        ':cliente_id'        => $_POST['cliente_id'],
        ':tipo_proceso_id'   => $_POST['tipo_proceso_id'] ?: null,
        ':estado_proceso_id' => $_POST['estado_proceso_id'] ?: null,
        ':numero_radicado'   => $_POST['numero_radicado'],
        ':descripcion'       => $_POST['descripcion'],
        ':fecha_inicio'      => $_POST['fecha_inicio'],
        ':fecha_vencimiento' => $_POST['fecha_vencimiento'] ?: null,
        ':es_privado'        => isset($_POST['es_privado']) ? 1 : 0,
        ':fuente_consulta'   => $_POST['fuente_consulta'] ?? 'ninguna',
    ];
    echo json_encode(['success' => $proceso->update($data)]);
    exit;
}

if($action == 'delete') {
    $id = $_POST['id'] ?? 0;
    echo json_encode(['success' => $proceso->delete($id)]);
    exit;
}

if($action == 'proximosVencer') {
    echo json_encode($proceso->getProximosVencer());
    exit;
}

if($action == 'enEspera') {
    echo json_encode($proceso->getEnEspera());
    exit;
}


if($action == 'sinMovimiento') {
    echo json_encode($proceso->getSinMovimiento());
    exit;
}

if($action == 'desistimientoTacito') {
    echo json_encode($proceso->getDesistimientoTacito());
    exit;
}

if($action == 'stats') {
    echo json_encode($proceso->getStats());
    exit;
}

if($action == 'getTipos') {
    require_once __DIR__ . '/../models/TipoProceso.php';
    $tipo = new TipoProceso();
    echo json_encode($tipo->getAll());
    exit;
}

if($action == 'getEstados') {
    require_once __DIR__ . '/../models/EstadoProceso.php';
    $estado = new EstadoProceso();
    echo json_encode($estado->getAll());
    exit;
}
?>