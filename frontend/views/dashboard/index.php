<!-- Tooltip flotante global, fuera de cualquier contenedor -->
<div id="dashTooltip" style="
    display: none;
    position: fixed;
    background: #2c3e50;
    color: white;
    padding: 12px 15px;
    border-radius: 8px;
    font-size: 12px;
    line-height: 1.8;
    min-width: 280px;
    max-width: 350px;
    z-index: 99999;
    box-shadow: 0 5px 15px rgba(0,0,0,0.35);
    pointer-events: none;
    white-space: pre-line;
    word-wrap: break-word;
"></div>

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

const tooltip = document.getElementById('dashTooltip');

document.addEventListener('mouseover', function(e) {
    const target = e.target.closest('[data-dash-tooltip]');
    if (!target) return;
    tooltip.textContent = target.getAttribute('data-dash-tooltip');
    tooltip.style.display = 'block';
});

document.addEventListener('mousemove', function(e) {
    if (tooltip.style.display === 'none') return;
    const x    = e.clientX + 15;
    const y    = e.clientY - 10;
    const maxX = window.innerWidth  - tooltip.offsetWidth  - 10;
    const maxY = window.innerHeight - tooltip.offsetHeight - 10;
    tooltip.style.left = Math.min(x, maxX) + 'px';
    tooltip.style.top  = Math.min(y, maxY) + 'px';
});

document.addEventListener('mouseout', function(e) {
    const target = e.target.closest('[data-dash-tooltip]');
    if (target) tooltip.style.display = 'none';
});
</script>

<div class="dashboard">
    <h2>Dashboard</h2>

    <div class="stats-container">
        <div class="stat-card">
            <h3>⚠️ Próximos a Vencer</h3>
            <div id="proximosVencer" class="stat-list"></div>
        </div>

        <div class="stat-card">
            <h3>⏳ En Espera de Respuesta</h3>
            <div id="enEspera" class="stat-list"></div>
        </div>
    </div>
</div>

<script>
function cargarProximosVencer() {
    fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php?action=proximosVencer')
        .then(r => r.json())
        .then(data => {
            const div = document.getElementById('proximosVencer');
            if (data.length === 0) {
                div.innerHTML = '<p class="sin-datos">No hay procesos próximos a vencer</p>';
                return;
            }
            let html = '';
            data.forEach(p => {
                const diffDays = Math.ceil((new Date(p.fecha_vencimiento) - new Date()) / (1000 * 60 * 60 * 24));
                let claseUrgencia = 'normal', claseDias = 'dias-verde';
                if (diffDays <= 3)      { claseUrgencia = 'urgente';  claseDias = 'dias-rojo';    }
                else if (diffDays <= 7) { claseUrgencia = 'atencion'; claseDias = 'dias-naranja'; }

                const tip = [
                    '📋 Radicado: ' + p.numero_radicado,
                    '👤 Cliente: '  + p.nombre + ' ' + p.apellido,
                    '📝 Tipo: '     + p.tipo_proceso,
                    '📅 Inicio: '   + p.fecha_inicio,
                    '⚠️ Vence: '    + p.fecha_vencimiento,
                    '📊 Estado: '   + p.estado,
                    p.descripcion   ? '📄 ' + p.descripcion.substring(0, 100) : ''
                ].filter(Boolean).join('\n');

                html += `
                    <div class="stat-item ${claseUrgencia}" data-dash-tooltip="${tip.replace(/"/g, '&quot;')}" style="cursor:help">
                        <div style="display:flex;justify-content:space-between;align-items:start">
                            <strong>${p.numero_radicado}</strong>
                            <span class="dias-badge ${claseDias}">${diffDays} días</span>
                        </div>
                        <div>${p.nombre} ${p.apellido}</div>
                        <div><small>Vence: ${p.fecha_vencimiento}</small></div>
                        <div><span class="${p.estado === 'Activo' ? 'estado-activo' : 'estado-espera'}">${p.estado}</span></div>
                    </div>`;
            });
            div.innerHTML = html;
        });
}

function cargarEnEspera() {
    fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php?action=enEspera')
        .then(r => r.json())
        .then(data => {
            const div = document.getElementById('enEspera');
            if (data.length === 0) {
                div.innerHTML = '<p class="sin-datos">No hay procesos en espera</p>';
                return;
            }
            let html = '';
            data.forEach(p => {
                const tip = [
                    '📋 Radicado: ' + p.numero_radicado,
                    '👤 Cliente: '  + p.nombre + ' ' + p.apellido,
                    '📝 Tipo: '     + p.tipo_proceso,
                    '📅 Inicio: '   + p.fecha_inicio,
                    '📅 Vence: '    + (p.fecha_vencimiento || 'No definida'),
                    '⏳ Estado: '   + p.estado,
                    p.descripcion   ? '📄 ' + p.descripcion.substring(0, 100) : ''
                ].filter(Boolean).join('\n');

                html += `
                    <div class="stat-item" data-dash-tooltip="${tip.replace(/"/g, '&quot;')}" style="cursor:help">
                        <strong>${p.numero_radicado}</strong>
                        <div>${p.nombre} ${p.apellido}</div>
                        <div><small>${p.tipo_proceso}</small></div>
                        <div><span class="estado-espera">⏳ ${p.estado}</span></div>
                    </div>`;
            });
            div.innerHTML = html;
        });
}

cargarProximosVencer();
cargarEnEspera();
setInterval(() => { cargarProximosVencer(); cargarEnEspera(); }, 300000);
</script>