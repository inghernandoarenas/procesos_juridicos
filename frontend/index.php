<?php
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

include 'layouts/main_layout.php';
?>