<?php
require_once __DIR__ . '/../../backend/models/Configuracion.php';
$_cfg          = (new Configuracion())->getMap();
$_nombreEmp    = $_cfg['nombre_empresa']  ?? 'Oficina Jurídica';
$_subtituloEmp = $_cfg['subtitulo']       ?? 'Sistema de Gestión de Procesos Judiciales';
$_anioEmp      = $_cfg['anio_copyright']  ?? date('Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_nombreEmp) ?></title>
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
                        <i class="fas fa-tachometer-alt" style="margin-right:10px;width:20px"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="/procesos_juridicos/frontend/index.php?view=honorarios">
                        <i class="fas fa-dollar-sign" style="margin-right:10px;width:20px"></i> Honorarios
                    </a>
                </li>
                <li>
                    <a href="/procesos_juridicos/frontend/index.php?view=procesos">
                        <i class="fas fa-gavel" style="margin-right:10px;width:20px"></i> Procesos
                    </a>
                </li>
                <li class="menu-item has-submenu">
                    <a href="#" onclick="toggleSubmenu(event)">
                        <i class="fas fa-cog" style="margin-right:10px;width:20px"></i> Parametrización
                        <i class="fas fa-chevron-down" style="float:right"></i>
                    </a>
                    <ul class="submenu" style="display:none;padding-left:15px">
                        <li>
                            <a href="/procesos_juridicos/frontend/index.php?view=clientes">
                                <i class="fas fa-users" style="margin-right:10px;width:20px"></i> Clientes
                            </a>
                        </li>
                        <li>
                            <a href="/procesos_juridicos/frontend/index.php?view=tipos_proceso">
                                <i class="fas fa-tags" style="margin-right:10px;width:20px"></i> Tipos de Proceso
                            </a>
                        </li>
                        <li>
                            <a href="/procesos_juridicos/frontend/index.php?view=estados_proceso">
                                <i class="fas fa-chart-pie" style="margin-right:10px;width:20px"></i> Estados de Proceso
                            </a>
                        </li>
                        <li>
                            <a href="/procesos_juridicos/frontend/index.php?view=usuarios">
                                <i class="fas fa-user-cog" style="margin-right:10px;width:20px"></i> Usuarios
                            </a>
                        </li>
                        <li>
                            <a href="/procesos_juridicos/frontend/index.php?view=notificaciones">
                                <i class="fas fa-bell" style="margin-right:10px;width:20px"></i> Notificaciones
                            </a>
                        </li>
                        <li>
                            <a href="/procesos_juridicos/frontend/index.php?view=log_notificaciones">
                                <i class="fas fa-list-alt" style="margin-right:10px;width:20px"></i> Log Notificaciones
                            </a>
                        </li>
                        <li>
                            <a href="/procesos_juridicos/frontend/index.php?view=configuracion">
                                <i class="fas fa-sliders-h" style="margin-right:10px;width:20px"></i> Mi Empresa
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <h1><?= htmlspecialchars($_nombreEmp) ?></h1>
                <div class="user-info" style="display:flex;align-items:center;gap:15px">
                    <span style="display:flex;align-items:center;gap:8px">
                        <i class="fas fa-user-circle" style="font-size:24px;color:#3498db"></i>
                        <span id="userName">Cargando...</span>
                    </span>
                    <button onclick="logout()" class="btn-icon" data-tooltip="Cerrar sesión" style="background:#f8f9fa">
                        <i class="fas fa-sign-out-alt" style="color:#e74c3c"></i>
                    </button>
                </div>
            </div>

            <script>
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
                <?php if (isset($content)) include $content; ?>
            </div>

            <div class="footer">
                <p>&copy; <?= $_anioEmp ?> - <?= htmlspecialchars($_nombreEmp) ?></p>
            </div>
        </div>
    </div>
    <!-- Toast container global -->
    <div id="toast-container"></div>

    <script>
    // ── Toast global ──────────────────────────────────────────
    function toast(mensaje, tipo = 'success', duracion = 3500) {
        const iconos = { success: '✅', error: '❌', info: 'ℹ️' };
        const contenedor = document.getElementById('toast-container');
        const el = document.createElement('div');
        el.className = `toast ${tipo}`;
        el.innerHTML = `<span>${iconos[tipo] || '✅'}</span><span>${mensaje}</span>`;
        contenedor.appendChild(el);
        setTimeout(() => {
            el.style.animation = 'toastOut .3s ease forwards';
            setTimeout(() => el.remove(), 300);
        }, duracion);
    }
    </script>
</body>
</html>

<script>
function toggleSubmenu(event) {
    event.preventDefault();
    const submenu = event.currentTarget.nextElementSibling;
    const icon    = event.currentTarget.querySelector('i:last-child');
    const abierto = submenu.style.display !== 'none';
    submenu.style.display = abierto ? 'none' : 'block';
    icon.classList.toggle('fa-chevron-down', abierto);
    icon.classList.toggle('fa-chevron-up', !abierto);
}

// Mantener submenú abierto si estamos en parametrización
document.addEventListener('DOMContentLoaded', function() {
    const view = new URLSearchParams(window.location.search).get('view');
    const vistasParametrizacion = ['clientes','tipos_proceso','estados_proceso','usuarios','notificaciones','log_notificaciones','configuracion'];
    const todasVistas = [...vistasParametrizacion, 'honorarios'];

    if (vistasParametrizacion.includes(view)) {
        const submenu = document.querySelector('.submenu');
        const icon    = document.querySelector('.has-submenu a i:last-child');
        if (submenu) {
            submenu.style.display = 'block';
            icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
        }
    }
});
</script>

<style>
.sidebar .submenu { padding-left: 25px !important; list-style: none; }
.sidebar .submenu li a { display: flex; align-items: center; gap: 8px; padding: 8px 12px; }
.sidebar .submenu li a i { width: 20px; text-align: center; }
</style>