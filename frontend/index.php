<?php
$token = $_COOKIE['token'] ?? '';
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
        (function() {
            const token = localStorage.getItem('token');
            if (!token) window.location.href = '/procesos_juridicos/frontend/login.php';
        })();
    </script>
</head>
<body>
    <?php
        $view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';

        switch ($view) {
            case 'clientes':
                $content = 'views/clientes/index.php';
                break;
            case 'procesos':
                $content = 'views/procesos/index.php';
                break;
            case 'tipos_proceso':
                $content = 'views/parametrizacion/tipos_proceso.php';
                break;
            case 'estados_proceso':
                $content = 'views/parametrizacion/estados_proceso.php';
                break;
            case 'usuarios':
                $content = 'views/parametrizacion/usuarios.php';
                break;
            case 'notificaciones':
                $content = 'views/parametrizacion/notificaciones.php';
                break;
            case 'log_notificaciones':
                $content = 'views/parametrizacion/log_notificaciones.php';
                break;
            case 'configuracion':
                $content = 'views/parametrizacion/configuracion.php';
                break;
            case 'dashboard':
            default:
                $content = 'views/dashboard/index.php';
                break;
        }

        include 'layouts/main_layout.php';
    ?>
</body>
</html>