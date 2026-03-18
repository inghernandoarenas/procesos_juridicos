<?php
    require_once __DIR__ . '/../models/Proceso.php';
    require_once __DIR__ . '/../models/Cliente.php';

    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $proceso = new Proceso();

    if($action == 'list') {
        echo json_encode($proceso->getAll());
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
            ':cliente_id' => $_POST['cliente_id'],
            ':tipo_proceso_id' => $_POST['tipo_proceso_id'] ?: null,  // Nuevo
            ':estado_proceso_id' => $_POST['estado_proceso_id'] ?: null, // Nuevo
            ':numero_radicado' => $_POST['numero_radicado'],
            //':tipo_proceso' => $_POST['tipo_proceso'],
            ':descripcion' => $_POST['descripcion'],
            //':estado' => $_POST['estado'],
            ':fecha_inicio' => $_POST['fecha_inicio'],
            ':fecha_vencimiento' => $_POST['fecha_vencimiento'] ?: null
        ];
        echo json_encode(['success' => $proceso->create($data)]);
        exit;
    }

    if($action == 'update') {
        $data = [
            ':id' => $_POST['id'],
            ':cliente_id' => $_POST['cliente_id'],
            ':tipo_proceso_id' => $_POST['tipo_proceso_id'] ?: null,
            ':estado_proceso_id' => $_POST['estado_proceso_id'] ?: null,
            ':numero_radicado' => $_POST['numero_radicado'],
            ':descripcion' => $_POST['descripcion'],
            ':fecha_inicio' => $_POST['fecha_inicio'],
            ':fecha_vencimiento' => $_POST['fecha_vencimiento'] ?: null
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