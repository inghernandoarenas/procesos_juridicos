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
    <style>
    /* ── Sidebar colapsable ─────────────────────────────────── */
    .sidebar {
        width: 250px;
        min-width: 250px;
        background: #2c3e50;
        color: white;
        padding: 20px;
        transition: width .25s ease, min-width .25s ease, padding .25s ease;
        overflow: hidden;
        position: relative;
        flex-shrink: 0;
    }
    .sidebar.collapsed {
        width: 56px;
        min-width: 56px;
        padding: 20px 10px;
    }
    .sidebar.collapsed .sidebar-label,
    .sidebar.collapsed h2 .sidebar-title,
    .sidebar.collapsed .submenu,
    .sidebar.collapsed .has-submenu > a > i.fa-chevron-down,
    .sidebar.collapsed .has-submenu > a > i.fa-chevron-up {
        display: none !important;
    }
    .sidebar.collapsed ul li a {
        justify-content: center;
        padding: 10px 0;
    }
    .sidebar.collapsed ul li a i {
        margin-right: 0 !important;
        width: auto !important;
        font-size: 17px;
    }
    /* Tooltip cuando está colapsado */
    .sidebar.collapsed ul li {
        position: relative;
    }
    .sidebar.collapsed ul li a::after {
        content: attr(data-label);
        position: absolute;
        left: 62px;
        top: 50%;
        transform: translateY(-50%);
        background: #1a252f;
        color: white;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 12px;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity .15s;
        z-index: 1000;
    }
    .sidebar.collapsed ul li a:hover::after { opacity: 1; }

    /* Botón toggle */
    .sidebar-toggle {
        position: absolute;
        top: 18px;
        right: -14px;
        width: 28px;
        height: 28px;
        background: #3498db;
        border: none;
        border-radius: 50%;
        color: white;
        font-size: 11px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
        box-shadow: 0 2px 8px rgba(0,0,0,.3);
        transition: background .15s, transform .25s;
    }
    .sidebar-toggle:hover { background: #2980b9; }
    .sidebar.collapsed .sidebar-toggle { transform: rotate(180deg); right: -14px; }

    /* Header del sidebar */
    .sidebar h2 {
        margin-bottom: 20px;
        font-size: 18px;
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        overflow: hidden;
    }
    .sidebar-icon-main { font-size: 20px; flex-shrink: 0; }

    /* Links con label span para ocultar al colapsar */
    .sidebar ul li a { white-space: nowrap; overflow: hidden; }
    .sidebar-label { transition: opacity .2s; }
    .sidebar.collapsed .sidebar-label { opacity: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar" id="mainSidebar">
            <button class="sidebar-toggle" onclick="toggleSidebar()" title="Colapsar menú">
                <i class="fas fa-chevron-left" id="toggleIcon"></i>
            </button>

            <h2>
                <i class="fas fa-gavel sidebar-icon-main"></i>
                <span class="sidebar-title">Menú</span>
            </h2>
            <ul>
                <li>
                    <a href="/procesos_juridicos/frontend/index.php?view=dashboard" data-label="Dashboard">
                        <i class="fas fa-tachometer-alt" style="margin-right:10px;width:20px;flex-shrink:0"></i>
                        <span class="sidebar-label">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="/procesos_juridicos/frontend/index.php?view=procesos" data-label="Procesos">
                        <i class="fas fa-gavel" style="margin-right:10px;width:20px;flex-shrink:0"></i>
                        <span class="sidebar-label">Procesos</span>
                    </a>
                </li>
                <li>
                    <a href="/procesos_juridicos/frontend/index.php?view=honorarios" data-label="Honorarios">
                        <i class="fas fa-dollar-sign" style="margin-right:10px;width:20px;flex-shrink:0"></i>
                        <span class="sidebar-label">Honorarios</span>
                    </a>
                </li>

                <!-- Parametrización -->
                <li class="menu-item has-submenu">
                    <a href="#" onclick="toggleSubmenu(event)" data-label="Parametrización">
                        <i class="fas fa-cog" style="margin-right:10px;width:20px;flex-shrink:0"></i>
                        <span class="sidebar-label">Parametrización</span>
                        <i class="fas fa-chevron-down" style="float:right;margin-left:auto"></i>
                    </a>
                    <ul class="submenu" style="display:none;padding-left:15px">
                        <li><a href="/procesos_juridicos/frontend/index.php?view=clientes">
                            <i class="fas fa-users" style="margin-right:10px;width:20px"></i> Clientes
                        </a></li>
                        <li><a href="/procesos_juridicos/frontend/index.php?view=tipos_proceso">
                            <i class="fas fa-tags" style="margin-right:10px;width:20px"></i> Tipos de Proceso
                        </a></li>
                        <li><a href="/procesos_juridicos/frontend/index.php?view=estados_proceso">
                            <i class="fas fa-chart-pie" style="margin-right:10px;width:20px"></i> Estados de Proceso
                        </a></li>
                        <li><a href="/procesos_juridicos/frontend/index.php?view=usuarios">
                            <i class="fas fa-user-cog" style="margin-right:10px;width:20px"></i> Usuarios
                        </a></li>
                        <li><a href="/procesos_juridicos/frontend/index.php?view=notificaciones">
                            <i class="fas fa-bell" style="margin-right:10px;width:20px"></i> Notificaciones
                        </a></li>
                    </ul>
                </li>

                <!-- Log Notificaciones -->
                <li>
                    <a href="/procesos_juridicos/frontend/index.php?view=log_notificaciones" data-label="Log Notificaciones">
                        <i class="fas fa-list-alt" style="margin-right:10px;width:20px;flex-shrink:0"></i>
                        <span class="sidebar-label">Log Notificaciones</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <h1><?= htmlspecialchars($_nombreEmp) ?></h1>
                <div class="user-info" style="display:flex;align-items:center;gap:16px">
                    <!-- Mi Empresa -->
                    <a href="/procesos_juridicos/frontend/index.php?view=configuracion"
                       style="display:flex;align-items:center;gap:8px;text-decoration:none;
                              background:#f0f4f8;border-radius:8px;padding:6px 12px;
                              border:1px solid #dce8f0;transition:background .15s"
                       onmouseover="this.style.background='#e2ecf5'"
                       onmouseout="this.style.background='#f0f4f8'"
                       title="Configurar empresa">
                        <div style="width:30px;height:30px;background:linear-gradient(135deg,#2c3e50,#3498db);
                                    border-radius:7px;display:flex;align-items:center;justify-content:center">
                            <i class="fas fa-building" style="color:white;font-size:14px"></i>
                        </div>
                        <span style="font-size:13px;font-weight:600;color:#2c3e50">
                            <?= htmlspecialchars($_nombreEmp) ?>
                        </span>
                    </a>
                    <!-- Usuario -->
                    <span style="display:flex;align-items:center;gap:8px">
                        <i class="fas fa-user-circle" style="font-size:24px;color:#3498db"></i>
                        <span id="userName" style="font-size:13px;color:#2c3e50;font-weight:500">Cargando...</span>
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

                // Restaurar estado del sidebar
                if (localStorage.getItem('sidebarCollapsed') === '1') {
                    document.getElementById('mainSidebar').classList.add('collapsed');
                }
            });

            function logout() {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                window.location.href = '/procesos_juridicos/frontend/login.php';
            }

            function toggleSidebar() {
                const sb = document.getElementById('mainSidebar');
                sb.classList.toggle('collapsed');
                localStorage.setItem('sidebarCollapsed', sb.classList.contains('collapsed') ? '1' : '0');
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

    <div id="toast-container"></div>

    <script>
    function toast(mensaje, tipo = 'success', duracion = 3500) {
        const iconos = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
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
    const sidebar = document.getElementById('mainSidebar');
    if (sidebar.classList.contains('collapsed')) {
        sidebar.classList.remove('collapsed');
        localStorage.setItem('sidebarCollapsed', '0');
    }
    const submenu = event.currentTarget.nextElementSibling;
    const icon    = event.currentTarget.querySelector('i:last-child');
    const abierto = submenu.style.display !== 'none';
    submenu.style.display = abierto ? 'none' : 'block';
    icon.classList.toggle('fa-chevron-down', abierto);
    icon.classList.toggle('fa-chevron-up', !abierto);
}

document.addEventListener('DOMContentLoaded', function() {
    const view = new URLSearchParams(window.location.search).get('view');
    const vistasParametrizacion = ['clientes','tipos_proceso','estados_proceso','usuarios','notificaciones'];
    if (vistasParametrizacion.includes(view)) {
        const submenu = document.querySelector('.submenu');
        const icon    = document.querySelector('.has-submenu a i:last-child');
        if (submenu) {
            submenu.style.display = 'block';
            if (icon) icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
        }
    }
});
</script>

<style>
.sidebar .submenu { padding-left: 25px !important; list-style: none; }
.sidebar .submenu li a { display: flex; align-items: center; gap: 8px; padding: 8px 12px; }
.sidebar .submenu li a i { width: 20px; text-align: center; }
</style>