<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Procesos</title>
    <link rel="stylesheet" href="/procesos_juridicos/frontend/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <div class="user-info" style="display: flex; align-items: center; gap: 15px;">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-user-circle" style="font-size: 24px; color: #3498db;"></i>
                        <span id="userName">Cargando...</span>
                    </span>
                    <button onclick="logout()" class="btn-icon" data-tooltip="Cerrar sesión" style="background: #f8f9fa;">
                        <i class="fas fa-sign-out-alt" style="color: #e74c3c;"></i>
                    </button>
                </div>
            </div>

            <script>
            // Mostrar nombre del usuario al cargar
            document.addEventListener('DOMContentLoaded', function() {
                const user = JSON.parse(localStorage.getItem('user') || '{}');
                document.getElementById('userName').textContent = user.nombre || 'Usuario';
            });

            function logout() {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                window.location.href = '/procesos_juridicos/frontend/login.php';
            }
            </script>

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