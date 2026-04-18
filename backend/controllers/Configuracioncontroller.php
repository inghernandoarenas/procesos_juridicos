<?php
require_once __DIR__ . '/../models/Configuracion.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$config = new Configuracion();

if ($action === 'get') {
    echo json_encode($config->getMap());
    exit;
}

if ($action === 'save') {
    $campos = [
        'nombre_empresa', 'subtitulo', 'nit', 'telefono',
        'email', 'direccion', 'ciudad', 'website',
        'pie_reporte', 'anio_copyright',
        'samai_session_id', 'samai_xsrf_token', 'samai_tipmix',   // ← SAMAI
    ];
    $data = [];
    foreach ($campos as $campo) {
        if (isset($_POST[$campo])) {
            $data[$campo] = trim($_POST[$campo]);
        }
    }
    echo json_encode(['success' => $config->saveAll($data)]);
    exit;
}

if ($action === 'test_samai') {
    require_once __DIR__ . '/../api/ApiSamai.php';
    $api = new ApiSamai();
    if (!$api->tieneCookies()) {
        echo json_encode(['success' => false, 'message' => 'Cookies no configuradas o vacías']);
        exit;
    }
    // Probar con una búsqueda simple
    $test = $api->buscarProceso('08001333300320230008000');
    if ($test === null) {
        echo json_encode(['success' => false, 'message' => 'No se pudo conectar con SAMAI']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Conexión OK — ' . count($test) . ' resultado(s)']);
    }
    exit;
}
?>