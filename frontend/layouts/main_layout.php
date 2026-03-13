<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Procesos</title>
    <link rel="stylesheet" href="/procesos_juridicos/frontend/assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>Menú</h2>
            <ul>
                <li><a href="/procesos_juridicos/frontend/index.php?view=dashboard">Dashboard</a></li>
                <li><a href="/procesos_juridicos/frontend/index.php?view=clientes">Clientes</a></li>
                <li><a href="/procesos_juridicos/frontend/index.php?view=procesos">Procesos</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Sistema de Gestión de Procesos Judiciales</h1>
                <div class="user-info">
                    <span>Usuario: Admin</span>
                </div>
            </div>

            <div class="content">
                <?php 
                if (isset($content)) {
                    include $content;
                }
                ?>
            </div>

            <div class="footer">
                <p>&copy; 2024 - Oficina de Abogados</p>
            </div>
        </div>
    </div>
</body>
</html>