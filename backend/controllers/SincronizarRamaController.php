<?php
require_once __DIR__ . '/../api/ApiRamaJudicial.php';
require_once __DIR__ . '/../models/Actuacion.php';
require_once __DIR__ . '/../models/Proceso.php';
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

header('Content-Type: application/json');

$proceso_id = $_POST['proceso_id'] ?? 0;

// Obtener el proceso
$procesoModel = new Proceso();
$proceso = $procesoModel->getById($proceso_id);

if(!$proceso) {
    echo json_encode(['success' => false, 'message' => 'Proceso no encontrado']);
    exit;
}

// Consultar API
$api = new ApiRamaJudicial();

$actuaciones = $api->consultarActuacionesPorRadicado($proceso['numero_radicado']);

if(empty($actuaciones)) {
    echo json_encode(['success' => false, 'message' => 'No se encontraron actuaciones nuevas']);
    exit;
}

// Guardar actuaciones
$actuacionModel = new Actuacion();
$contador = 0;

foreach($actuaciones as $act) {
    // Verificar si ya existe por id_api
    $existe = $actuacionModel->getByIdApi($act['id']);
    
    if(!$existe) {
        $data = [
            ':proceso_id' => $proceso_id,
            ':id_api' => $act['id'],
            ':fecha' => $act['fecha'],
            ':actuacion' => $act['actuacion'],
            ':observaciones' => $act['observaciones']
        ];
        
        if($actuacionModel->create($data)) {
            $contador++;
        }
    }
}

if ($contador > 0){
    echo json_encode(['success' => true, 'message' => "Se importaron $contador actuaciones"]);
}else{
    echo json_encode(['success' => true, 'message' => "No se encontraron actuaciones nuevas"]);
}
?>