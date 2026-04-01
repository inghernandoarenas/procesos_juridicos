<?php
require_once __DIR__ . '/../models/Configuracion.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$config = new Configuracion();

// Obtener toda la configuración
if ($action === 'get') {
    echo json_encode($config->getMap());
    exit;
}

// Guardar configuración
if ($action === 'save') {
    $campos = [
        'nombre_empresa', 'subtitulo', 'nit', 'telefono',
        'email', 'direccion', 'ciudad', 'website',
        'pie_reporte', 'anio_copyright'
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
?>