<style>
.log-filtros {
    display: flex; gap: 12px; align-items: center;
    margin-bottom: 18px; flex-wrap: wrap;
}
.log-filtros select, .log-filtros input {
    padding: 8px 12px; border: 2px solid #e0e0e0;
    border-radius: 6px; font-size: 13px; color: #2c3e50;
}
.log-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 700; text-transform: uppercase;
}
.log-badge.enviado  { background: #eafaf1; color: #27ae60; }
.log-badge.fallido  { background: #fdecea; color: #e74c3c; }
.log-badge.pendiente{ background: #fef9ec; color: #f39c12; }
.log-badge.email    { background: #eaf4fd; color: #2980b9; }
.log-badge.whatsapp { background: #f0fff4; color: #25d366; }

.log-stats {
    display: grid; grid-template-columns: repeat(4,1fr);
    gap: 12px; margin-bottom: 20px;
}
.log-stat-card {
    background: #f8f9fa; border-radius: 8px;
    padding: 14px 16px; text-align: center;
    border-top: 3px solid #e0e0e0;
}
.log-stat-card.verde  { border-top-color: #27ae60; }
.log-stat-card.rojo   { border-top-color: #e74c3c; }
.log-stat-card.azul   { border-top-color: #3498db; }
.log-stat-card.verde2 { border-top-color: #25d366; }
.log-stat-num  { font-size: 28px; font-weight: 700; color: #2c3e50; }
.log-stat-label{ font-size: 11px; color: #95a5a6; text-transform: uppercase; letter-spacing: .5px; }
</style>

<div class="page-header">
    <h2>Log de Notificaciones</h2>
    <button class="btn btn-primary" onclick="cargarLog()">
        <i class="fas fa-sync-alt"></i> Actualizar
    </button>
</div>

<!-- Estadísticas rápidas -->
<div class="log-stats" id="logStats"></div>

<!-- Filtros -->
<div class="log-filtros">
    <select id="filtroEstado" onchange="filtrarLog()">
        <option value="">Todos los estados</option>
        <option value="enviado">Enviados</option>
        <option value="fallido">Fallidos</option>
        <option value="pendiente">Pendientes</option>
    </select>
    <select id="filtroTipo" onchange="filtrarLog()">
        <option value="">Todos los tipos</option>
        <option value="email">Email</option>
        <option value="whatsapp">WhatsApp</option>
    </select>
    <input type="text" id="filtroBuscar" placeholder="Buscar por radicado o destinatario..."
           oninput="filtrarLog()" style="min-width:260px">
</div>

<!-- Tabla -->
<table id="tablaLog">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Radicado</th>
            <th>Tipo</th>
            <th>Destinatario</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody id="tbodyLog"></tbody>
</table>

<p id="logVacio" style="display:none;text-align:center;padding:40px;color:#bdc3c7;font-style:italic">
    <i class="fas fa-inbox" style="font-size:40px;display:block;margin-bottom:10px"></i>
    No hay notificaciones registradas
</p>

<script>
function fetchWithAuth(url, options = {}) {
    const token = localStorage.getItem('token');
    if (!token) { window.location.href = '/procesos_juridicos/frontend/login.php'; return Promise.reject(); }
    options.headers = { ...options.headers, 'Authorization': 'Bearer ' + token };
    return fetch(url, options).then(r => {
        if (r.status === 401) { localStorage.clear(); window.location.href = '/procesos_juridicos/frontend/login.php'; }
        return r;
    });
}

let logData = [];

function cargarLog() {
    fetchWithAuth('/procesos_juridicos/backend/controllers/NotificacionLogController.php?action=list&limite=200')
        .then(r => r.json())
        .then(data => {
            logData = data;
            renderStats(data);
            filtrarLog();
        });
}

function renderStats(data) {
    const enviados  = data.filter(d => d.estado === 'enviado').length;
    const fallidos  = data.filter(d => d.estado === 'fallido').length;
    const emails    = data.filter(d => d.tipo_envio === 'email').length;
    const whatsapps = data.filter(d => d.tipo_envio === 'whatsapp').length;

    document.getElementById('logStats').innerHTML = `
        <div class="log-stat-card verde">
            <div class="log-stat-num">${enviados}</div>
            <div class="log-stat-label">Enviados</div>
        </div>
        <div class="log-stat-card rojo">
            <div class="log-stat-num">${fallidos}</div>
            <div class="log-stat-label">Fallidos</div>
        </div>
        <div class="log-stat-card azul">
            <div class="log-stat-num">${emails}</div>
            <div class="log-stat-label">Por Email</div>
        </div>
        <div class="log-stat-card verde2">
            <div class="log-stat-num">${whatsapps}</div>
            <div class="log-stat-label">Por WhatsApp</div>
        </div>`;
}

function filtrarLog() {
    const estado  = document.getElementById('filtroEstado').value;
    const tipo    = document.getElementById('filtroTipo').value;
    const buscar  = document.getElementById('filtroBuscar').value.toLowerCase();

    const filtrado = logData.filter(d => {
        if (estado && d.estado    !== estado) return false;
        if (tipo   && d.tipo_envio !== tipo)  return false;
        if (buscar && !d.numero_radicado?.toLowerCase().includes(buscar)
                   && !d.destinatario?.toLowerCase().includes(buscar)) return false;
        return true;
    });

    renderTabla(filtrado);
}

function renderTabla(data) {
    const tbody  = document.getElementById('tbodyLog');
    const vacio  = document.getElementById('logVacio');
    const tabla  = document.getElementById('tablaLog');

    if (data.length === 0) {
        tabla.style.display = 'none';
        vacio.style.display = 'block';
        return;
    }

    tabla.style.display = '';
    vacio.style.display = 'none';

    tbody.innerHTML = data.map(d => {
        const fecha = new Date(d.fecha_envio).toLocaleString('es-CO', {
            day:'2-digit', month:'short', year:'numeric',
            hour:'2-digit', minute:'2-digit', hour12:false
        });

        const badgeEstado = `<span class="log-badge ${d.estado}">${d.estado}</span>`;
        const badgeTipo   = `<span class="log-badge ${d.tipo_envio}">${d.tipo_envio}</span>`;

        return `<tr>
            <td style="font-size:12px;color:#7f8c8d;white-space:nowrap">${fecha}</td>
            <td><strong style="color:#3498db">${d.numero_radicado || '—'}</strong></td>
            <td>${badgeTipo}</td>
            <td style="font-size:13px">${d.destinatario}</td>
            <td>${badgeEstado}</td>
        </tr>`;
    }).join('');
}

cargarLog();
</script>