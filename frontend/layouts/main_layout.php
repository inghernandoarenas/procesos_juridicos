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
                <li>
                    <a href="/procesos_juridicos/frontend/index.php?view=dashboard">
                        <i class="fas fa-tachometer-alt" style="margin-right: 10px; width: 20px;"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="/procesos_juridicos/frontend/index.php?view=procesos">
                        <i class="fas fa-gavel" style="margin-right: 10px; width: 20px;"></i> Procesos
                    </a>
                </li>
                <li class="menu-item has-submenu">
                    <a href="#" onclick="toggleSubmenu(event)">
                        <i class="fas fa-cog" style="margin-right: 10px; width: 20px;"></i> Parametrización 
                        <i class="fas fa-chevron-down" style="float: right;"></i>
                    </a>
                    <ul class="submenu" style="display: none; padding-left: 15px;">
                        <li>
                            <a href="/procesos_juridicos/frontend/index.php?view=clientes">
                                <i class="fas fa-users" style="margin-right: 10px; width: 20px;"></i> Clientes
                            </a>
                        </li>
                        <li>
                            <a href="/procesos_juridicos/frontend/index.php?view=tipos_proceso">
                                <i class="fas fa-tags" style="margin-right: 10px; width: 20px;"></i> Tipos de Proceso
                            </a>
                        </li>
                        <li>
                            <a href="/procesos_juridicos/frontend/index.php?view=estados_proceso">
                                <i class="fas fa-chart-pie" style="margin-right: 10px; width: 20px;"></i> Estados de Proceso
                            </a>
                        </li>
                    </ul>
                </li>
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

<script>
function toggleSubmenu(event) {
    event.preventDefault();
    const submenu = event.currentTarget.nextElementSibling;
    const icon = event.currentTarget.querySelector('i');
    
    if (submenu.style.display === 'none') {
        submenu.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        submenu.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

// Mantener submenu abierto si estamos en una página de parametrización
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    
    if (view === 'clientes' || view === 'tipos_proceso' || view === 'estados_proceso') {
        const submenu = document.querySelector('.submenu');
        const icon = document.querySelector('.has-submenu i');
        if (submenu) {
            submenu.style.display = 'block';
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        }
    }
});
</script>

<style>
/* Alinear submenús a la izquierda */
.sidebar .submenu {
    padding-left: 25px !important;
    list-style: none;
}

.sidebar .submenu li a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
}

.sidebar .submenu li a i {
    width: 20px;
    text-align: center;
}
</style>