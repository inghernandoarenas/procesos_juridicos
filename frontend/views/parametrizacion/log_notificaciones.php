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
.log-badge.sms      { background: #f5f0fe; color: #8e44ad; }

.log-stats {
    display: grid; grid-template-columns: repeat(4,1fr);
    gap: 12px; margin-bottom: 20px;
}
.log-stat-card {
    background: #f8f9fa; border-radius: 8px;
    padding: 12px 16px;
    text-align: center;
    border-top: 3px solid #e0e0e0;
}
.log-stat-card.verde  { border-top-color: #27ae60; }
.log-stat-card.rojo   { border-top-color: #e74c3c; }
.log-stat-card.azul   { border-top-color: #3498db; }
.log-stat-card.verde2 { border-top-color: #25d366; }
.log-stat-card.morado { border-top-color: #8e44ad; }
.log-stat-num  { font-size: 20px; font-weight: 600; color: #2c3e50; }
.log-stat-label{ font-size: 10px; color: #95a5a6; text-transform: uppercase; letter-spacing: .5px; }

.log-paginacion {
    display: flex; align-items: center; justify-content: center;
    gap: 6px; margin-top: 16px; flex-wrap: wrap;
}
.log-paginacion button {
    min-width: 32px; padding: 6px 10px;
    border: 1px solid #e0e0e0; background: #fff; color: #2c3e50;
    border-radius: 6px; font-size: 13px; cursor: pointer;
}
.log-paginacion button:hover:not(:disabled) { background: #f5f6fa; }
.log-paginacion button:disabled { opacity: .4; cursor: default; }
.log-paginacion button.activo { background: #3498db; color: #fff; border-color: #3498db; }
.log-paginacion .log-pagina-info {
    font-size: 12px; color: #7f8c8d; margin: 0 8px;
}
</style>

<div class="page-header">
    <h2>Log de Notificaciones</h2>
    <button class="btn btn-primary" onclick="cargarLog()">Actualizar</button>
</div>

<div class="log-stats" id="logStats"></div>

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
        <option value="sms">SMS</option>
    </select>
    <input type="text" id="filtroBuscar" placeholder="Buscar por radicado o destinatario..."
           oninput="filtrarLog()" style="min-width:260px">
</div>

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

<div class="log-paginacion" id="logPaginacion"></div>

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
const LOG_POR_PAGINA = 8;
let logPaginaActual = 1;

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
    const smss      = data.filter(d => d.tipo_envio === 'sms').length;

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
        </div>
        <div class="log-stat-card morado">
            <div class="log-stat-num">${smss}</div>
            <div class="log-stat-label">Por SMS</div>
        </div>`;
}

function obtenerDatosFiltrados() {
    const estado  = document.getElementById('filtroEstado').value;
    const tipo    = document.getElementById('filtroTipo').value;
    const buscar  = document.getElementById('filtroBuscar').value.toLowerCase();

    return logData.filter(d => {
        if (estado && d.estado    !== estado) return false;
        if (tipo   && d.tipo_envio !== tipo)  return false;
        if (buscar && !d.numero_radicado?.toLowerCase().includes(buscar)
                   && !d.destinatario?.toLowerCase().includes(buscar)) return false;
        return true;
    });
}

function filtrarLog() {
    logPaginaActual = 1; // cada vez que cambia el filtro, volvemos a la primera página
    renderTabla(obtenerDatosFiltrados());
}

function renderTabla(data) {
    const tbody  = document.getElementById('tbodyLog');
    const vacio  = document.getElementById('logVacio');
    const tabla  = document.getElementById('tablaLog');

    if (data.length === 0) {
        tabla.style.display = 'none';
        vacio.style.display = 'block';
        document.getElementById('logPaginacion').innerHTML = '';
        return;
    }

    tabla.style.display = '';
    vacio.style.display = 'none';

    // Paginación: 8 registros por página sobre el resultado ya filtrado
    const totalPaginas = Math.max(1, Math.ceil(data.length / LOG_POR_PAGINA));
    if (logPaginaActual > totalPaginas) logPaginaActual = totalPaginas;
    if (logPaginaActual < 1) logPaginaActual = 1;

    const inicio = (logPaginaActual - 1) * LOG_POR_PAGINA;
    const pagina = data.slice(inicio, inicio + LOG_POR_PAGINA);

    tbody.innerHTML = pagina.map(d => {
        const fecha = new Date(d.fecha_envio).toLocaleString('es-CO', {
            day:'2-digit', month:'short', year:'numeric',
            hour:'2-digit', minute:'2-digit', hour12:false
        });

        const badgeEstado = `<span class="log-badge ${d.estado}">${d.estado}</span>`;
        const badgeTipo   = `<span class="log-badge ${d.tipo_envio}">${d.tipo_envio}</span>`;

        return `<tr>
            <td style="padding:8px 12px;font-size:12px;color:#7f8c8d;white-space:nowrap">${fecha}</td>
            <td style="padding:8px 12px;font-size:12px;color:#3498db">${d.numero_radicado || '—'}</td>
            <td style="padding:8px 12px">${badgeTipo}</td>
            <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${d.destinatario}</td>
            <td style="padding:8px 12px">${badgeEstado}</td>
        </tr>`;
    }).join('');

    renderPaginacion(data.length, totalPaginas);
}

function irAPagina(n) {
    logPaginaActual = n;
    renderTabla(obtenerDatosFiltrados());
}

function renderPaginacion(totalRegistros, totalPaginas) {
    const cont = document.getElementById('logPaginacion');

    if (totalPaginas <= 1) {
        cont.innerHTML = totalRegistros > 0
            ? `<span class="log-pagina-info">${totalRegistros} registro${totalRegistros === 1 ? '' : 's'}</span>`
            : '';
        return;
    }

    let botones = '';
    botones += `<button ${logPaginaActual === 1 ? 'disabled' : ''} onclick="irAPagina(${logPaginaActual - 1})">&laquo;</button>`;

    // Ventana de páginas visible alrededor de la actual, para no llenar de botones si hay muchas
    const ventana = 2;
    for (let p = 1; p <= totalPaginas; p++) {
        const esBorde = p === 1 || p === totalPaginas;
        const cercaDeActual = Math.abs(p - logPaginaActual) <= ventana;
        if (esBorde || cercaDeActual) {
            botones += `<button class="${p === logPaginaActual ? 'activo' : ''}" onclick="irAPagina(${p})">${p}</button>`;
        } else if (Math.abs(p - logPaginaActual) === ventana + 1) {
            botones += `<span class="log-pagina-info">…</span>`;
        }
    }

    botones += `<button ${logPaginaActual === totalPaginas ? 'disabled' : ''} onclick="irAPagina(${logPaginaActual + 1})">&raquo;</button>`;
    botones += `<span class="log-pagina-info">${totalRegistros} registros — página ${logPaginaActual} de ${totalPaginas}</span>`;

    cont.innerHTML = botones;
}

cargarLog();
</script>