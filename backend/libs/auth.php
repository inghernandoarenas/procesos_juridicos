<?php
require_once __DIR__ . '/JWT.php';

function verificarToken() {
    $headers = getallheaders();
    $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
    
    if(empty($token)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token no proporcionado']);
        exit;
    }
    
    $payload = JWT::decode($token);
    
    if(!$payload) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token inválido o expirado']);
        exit;
    }
    
    return $payload;
}
?>