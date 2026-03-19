<?php
// Verificar token vía PHP también (segunda capa de seguridad)
$token = $_COOKIE['token'] ?? ''; // Si usaras cookies
// Por ahora dejamos solo JS pero podríamos validar aquí también
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Procesos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/procesos_juridicos/frontend/assets/css/style.css">
    <script>
        // Verificar token al cargar la página
        (function() {
            const token = localStorage.getItem('token');
            if (!token) {
                window.location.href = '/procesos_juridicos/frontend/login.php';
            }
        })();
    </script>
</head>
<body>
    <?php
    // Determinar qué vista cargar
    $view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';

    switch ($view) {
        case 'clientes':
            $content = 'views/clientes/index.php';
            break;
        case 'procesos':
            $content = 'views/procesos/index.php';
            break;
        case 'dashboard':
        default:
            $content = 'views/dashboard/index.php';
            break;
    }

    // Incluir el layout (que contiene sidebar, header, footer y el contenido)
    include 'layouts/main_layout.php';
    ?>
</body>
</html>