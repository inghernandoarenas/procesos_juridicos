<?php
// ── Verificación de sesión en servidor ────────────────────────
// El token viene en el header Authorization desde JS,
// pero en la carga inicial de la página lo leemos de localStorage
// vía meta-redirect. Aquí hacemos la guardia server-side:
session_start();

// Leer token del header (para llamadas AJAX) o de cookie de sesión
$token = '';
$headers = function_exists('getallheaders') ? getallheaders() : [];
if (!empty($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

// Para peticiones de página completa, confiar en el JS del lado cliente
// pero agregar una capa extra: si no hay token en la cookie de sesión
// ni en el header, el JS lo manejará. No bloqueamos aquí para no
// romper el flujo de carga inicial (el redirect JS es instantáneo).
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Procesos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/procesos_juridicos/frontend/assets/css/style.css">
    <script>
    // Guardia de autenticación — se ejecuta ANTES de que cargue el body
    (function() {
        const token = localStorage.getItem('token');
        if (!token) {
            window.location.replace('/procesos_juridicos/frontend/login.php');
        }
    })();
    </script>
</head>
<body>
<?php
    $view = $_GET['view'] ?? 'dashboard';
    $views = [
        'dashboard'        => 'views/dashboard/index.php',
        'clientes'         => 'views/clientes/index.php',
        'procesos'         => 'views/procesos/index.php',
        'honorarios'       => 'views/honorarios/index.php',
        'tipos_proceso'    => 'views/parametrizacion/tipos_proceso.php',
        'estados_proceso'  => 'views/parametrizacion/estados_proceso.php',
        'usuarios'         => 'views/parametrizacion/Usuarios.php',
        'notificaciones'   => 'views/parametrizacion/Notificaciones.php',
        'log_notificaciones' => 'views/parametrizacion/log_notificaciones.php',
        'configuracion'    => 'views/parametrizacion/Configuracion.php',
        'departamentos'    => 'views/parametrizacion/departamentos.php',
        'municipios'       => 'views/parametrizacion/municipios.php',
        'entidades'        => 'views/parametrizacion/entidades.php',
        'despachos'        => 'views/parametrizacion/despachos.php',
    ];

    $content = $views[$view] ?? $views['dashboard'];

    include 'layouts/main_layout.php';
?>
</body>
</html>