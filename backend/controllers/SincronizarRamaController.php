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

// null = timeout/error de red; [] = proceso sin actuaciones
if ($actuaciones === null) {
    echo json_encode(['success' => false, 'message' => 'No se pudo conectar con la Rama Judicial. Verifique su conexión e intente nuevamente.']);
    exit;
}

if (empty($actuaciones)) {
    echo json_encode(['success' => true, 'message' => 'El proceso no tiene actuaciones registradas en la Rama Judicial.']);
    exit;
}

// Guardar actuaciones y notificar
$actuacionModel    = new Actuacion();
require_once __DIR__ . '/../services/NotificacionService.php';
$notificacionService = new NotificacionService();
$contador = 0;

foreach($actuaciones as $act) {
    $existe = $actuacionModel->getByIdApi($act['id'], $proceso_id);

    if(!$existe) {
        $data = [
            ':proceso_id'    => $proceso_id,
            ':id_api'        => $act['id'],
            ':fecha'         => $act['fecha'],
            ':actuacion'     => $act['actuacion'],
            ':observaciones' => $act['observaciones']
        ];

        $nuevoId = $actuacionModel->createAndGetId($data);
        if($nuevoId) {
            $contador++;
            // Notificar igual que el cron automático
            try {
                $act['id'] = $nuevoId;
                $notificacionService->notificarNuevaActuacion($proceso, $act);
            } catch(Exception $e) {
                // No interrumpir el flujo si falla la notificación
            }
        }
    }
}

if ($contador > 0){
    echo json_encode(['success' => true, 'message' => "Se importaron $contador actuaciones"]);
}else{
    echo json_encode(['success' => true, 'message' => "No se encontraron actuaciones nuevas"]);
}
?>