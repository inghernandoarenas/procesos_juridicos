<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../libs/JWT.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if($action == 'login') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if(empty($usuario) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Usuario y contraseña requeridos']);
        exit;
    }
    
    $userModel = new Usuario();
    $user = $userModel->login($usuario, $password);
    
    if($user) {
        // Generar token JWT
        $token = JWT::encode([
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'usuario' => $user['usuario']
        ]);
        
        echo json_encode([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Credenciales inválidas']);
    }
    exit;
}

if($action == 'verify') {
    $headers = getallheaders();
    $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
    
    if(empty($token)) {
        echo json_encode(['success' => false, 'message' => 'Token no proporcionado']);
        exit;
    }
    
    $payload = JWT::decode($token);
    
    if($payload) {
        echo json_encode(['success' => true, 'user' => $payload]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Token inválido o expirado']);
    }
    exit;
}
?>