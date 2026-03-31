<!-- Tooltip flotante global -->
<div id="dashTooltip" style="
    display:none; position:fixed;
    background:#2c3e50; color:white;
    padding:12px 15px; border-radius:8px;
    font-size:12px; line-height:1.8;
    min-width:280px; max-width:350px;
    z-index:99999; box-shadow:0 5px 15px rgba(0,0,0,.35);
    pointer-events:none; white-space:pre-line; word-wrap:break-word;
"></div>

<style>
/* Semáforo */
.semaforo-dot {
    display: inline-block;
    width: 12px; height: 12px;
    border-radius: 50%;
    margin-right: 8px;
    flex-shrink: 0;
    box-shadow: 0 0 0 3px rgba(0,0,0,.08);
}
.dot-rojo    { background: #e74c3c; box-shadow: 0 0 6px rgba(231,76,60,.5);  }
.dot-amarillo{ background: #f39c12; box-shadow: 0 0 6px rgba(243,156,18,.5); }

.sem-item {
    padding: 12px 15px;
    border-left: 4px solid transparent;
    margin-bottom: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    cursor: help;
    transition: transform .2s;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.sem-item:hover { transform: translateX(5px); }
.sem-item.rojo    { border-left-color: #e74c3c; }
.sem-item.amarillo{ border-left-color: #f39c12; }

.sem-info { flex: 1; }
.sem-dias {
    font-size: 11px; font-weight: 700;
    padding: 2px 8px; border-radius: 20px;
    white-space: nowrap;
    flex-shrink: 0;
}
.sem-dias.rojo    { background: #fdecea; color: #e74c3c; }
.sem-dias.amarillo{ background: #fef9ec; color: #f39c12; }
.sem-dias.sin-act { background: #eaf0fb; color: #5d6d7e; }

.sem-leyenda {
    display: flex; gap: 15px;
    font-size: 11px; color: #7f8c8d;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    flex-wrap: wrap;
}
.sem-leyenda span { display: flex; align-items: center; }
</style>

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

// Tooltip flotante
const tooltip = document.getElementById('dashTooltip');
document.addEventListener('mouseover', e => {
    const t = e.target.closest('[data-dash-tooltip]');
    if (!t) return;
    tooltip.textContent = t.getAttribute('data-dash-tooltip');
    tooltip.style.display = 'block';
});
document.addEventListener('mousemove', e => {
    if (tooltip.style.display === 'none') return;
    tooltip.style.left = Math.min(e.clientX + 15, window.innerWidth  - tooltip.offsetWidth  - 10) + 'px';
    tooltip.style.top  = Math.min(e.clientY - 10, window.innerHeight - tooltip.offsetHeight - 10) + 'px';
});
document.addEventListener('mouseout', e => {
    if (e.target.closest('[data-dash-tooltip]')) tooltip.style.display = 'none';
});
</script>

<div class="dashboard">
    <h2>Dashboard</h2>

    <div class="stats-container" style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px">

        <!-- ── Próximos a vencer ──────────────────────────── -->
        <div class="stat-card">
            <h3>⚠️ Próximos a Vencer</h3>
            <div id="proximosVencer" class="stat-list"></div>
        </div>

        <!-- ── En espera ──────────────────────────────────── -->
        <div class="stat-card">
            <h3>⏳ En Espera de Respuesta</h3>
            <div id="enEspera" class="stat-list"></div>
        </div>

        <!-- ── Semáforo de silencio judicial ─────────────── -->
        <div class="stat-card">
            <h3>🚦 Silencio Judicial</h3>
            <div id="sinMovimiento" class="stat-list"></div>
        </div>

    </div>
</div>

<script>
// ── Próximos a vencer ─────────────────────────────────────────
function cargarProximosVencer() {
    fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php?action=proximosVencer')
        .then(r => r.json())
        .then(data => {
            const div = document.getElementById('proximosVencer');
            if (data.length === 0) {
                div.innerHTML = '<p class="sin-datos">No hay procesos próximos a vencer</p>';
                return;
            }
            div.innerHTML = data.map(p => {
                const diffDays = Math.ceil((new Date(p.fecha_vencimiento) - new Date()) / 86400000);
                let clase = 'normal', claseDias = 'dias-verde';
                if      (diffDays <= 3) { clase = 'urgente';  claseDias = 'dias-rojo';    }
                else if (diffDays <= 7) { clase = 'atencion'; claseDias = 'dias-naranja'; }

                const tip = [
                    '📋 Radicado: ' + p.numero_radicado,
                    '👤 Cliente: '  + p.nombre + ' ' + p.apellido,
                    '📝 Tipo: '     + (p.tipo_proceso || '—'),
                    '📅 Inicio: '   + p.fecha_inicio,
                    '⚠️ Vence: '    + p.fecha_vencimiento,
                    '📊 Estado: '   + (p.estado || '—'),
                    p.descripcion ? '📄 ' + p.descripcion.substring(0,100) : ''
                ].filter(Boolean).join('\n');

                return `
                <div class="stat-item ${clase}"
                     data-dash-tooltip="${tip.replace(/"/g,'&quot;')}" style="cursor:help">
                    <div style="display:flex;justify-content:space-between;align-items:start">
                        <strong>${p.numero_radicado}</strong>
                        <span class="dias-badge ${claseDias}">${diffDays} días</span>
                    </div>
                    <div>${p.nombre} ${p.apellido}</div>
                    <div><small>Vence: ${p.fecha_vencimiento}</small></div>
                    <div><span class="${p.estado==='Activo'?'estado-activo':'estado-espera'}">${p.estado||'—'}</span></div>
                </div>`;
            }).join('');
        });
}

// ── En espera ─────────────────────────────────────────────────
function cargarEnEspera() {
    fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php?action=enEspera')
        .then(r => r.json())
        .then(data => {
            const div = document.getElementById('enEspera');
            if (data.length === 0) {
                div.innerHTML = '<p class="sin-datos">No hay procesos en espera</p>';
                return;
            }
            div.innerHTML = data.map(p => {
                const tip = [
                    '📋 Radicado: ' + p.numero_radicado,
                    '👤 Cliente: '  + p.nombre + ' ' + p.apellido,
                    '📝 Tipo: '     + (p.tipo_proceso || '—'),
                    '📅 Inicio: '   + p.fecha_inicio,
                    '📅 Vence: '    + (p.fecha_vencimiento || 'No definida'),
                    '⏳ Estado: '   + (p.estado || '—'),
                    p.descripcion ? '📄 ' + p.descripcion.substring(0,100) : ''
                ].filter(Boolean).join('\n');

                return `
                <div class="stat-item"
                     data-dash-tooltip="${tip.replace(/"/g,'&quot;')}" style="cursor:help">
                    <strong>${p.numero_radicado}</strong>
                    <div>${p.nombre} ${p.apellido}</div>
                    <div><small>${p.tipo_proceso || '—'}</small></div>
                    <div><span class="estado-espera">⏳ ${p.estado||'—'}</span></div>
                </div>`;
            }).join('');
        });
}

// ── Semáforo de silencio judicial ─────────────────────────────
function cargarSinMovimiento() {
    fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php?action=sinMovimiento')
        .then(r => r.json())
        .then(data => {
            const div = document.getElementById('sinMovimiento');
            if (data.length === 0) {
                div.innerHTML = '<p class="sin-datos">✅ Todos los procesos tienen actividad reciente</p>';
                return;
            }

            const rojos    = data.filter(p => p.dias_sin_movimiento === null || p.dias_sin_movimiento > 60);
            const amarillos = data.filter(p => p.dias_sin_movimiento !== null && p.dias_sin_movimiento <= 60);

            let html = `
            <div class="sem-leyenda">
                <span><span class="semaforo-dot dot-rojo"></span> Más de 60 días (${rojos.length})</span>
                <span><span class="semaforo-dot dot-amarillo"></span> 30–60 días (${amarillos.length})</span>
            </div>`;

            html += data.map(p => {
                const dias  = p.dias_sin_movimiento;
                const esRojo = dias === null || dias > 60;
                const clase  = esRojo ? 'rojo' : 'amarillo';

                const diasTexto = dias === null
                    ? 'Sin actuaciones'
                    : `${dias} días sin movimiento`;

                const ultimaAct = p.ultima_actuacion
                    ? new Date(p.ultima_actuacion).toLocaleDateString('es-CO')
                    : 'Ninguna registrada';

                const tip = [
                    '📋 Radicado: ' + p.numero_radicado,
                    '👤 Cliente: '  + p.nombre + ' ' + p.apellido,
                    '📝 Tipo: '     + (p.tipo_proceso || '—'),
                    '📅 Inicio: '   + p.fecha_inicio,
                    '🕐 Última actuación: ' + ultimaAct,
                    '⏱ Días sin movimiento: ' + (dias ?? 'N/A'),
                    '📊 Estado: '   + (p.estado || '—'),
                ].join('\n');

                return `
                <div class="sem-item ${clase}"
                     data-dash-tooltip="${tip.replace(/"/g,'&quot;')}">
                    <span class="semaforo-dot dot-${clase}"></span>
                    <div class="sem-info">
                        <strong style="font-size:13px">${p.numero_radicado}</strong>
                        <div style="font-size:12px;color:#555;margin-top:2px">${p.nombre} ${p.apellido}</div>
                        <div style="font-size:11px;color:#888">Última act: ${ultimaAct}</div>
                    </div>
                    <span class="sem-dias ${dias === null ? 'sin-act' : clase}">${diasTexto}</span>
                </div>`;
            }).join('');

            div.innerHTML = html;
        });
}

// ── Init ──────────────────────────────────────────────────────
cargarProximosVencer();
cargarEnEspera();
cargarSinMovimiento();

setInterval(() => {
    cargarProximosVencer();
    cargarEnEspera();
    cargarSinMovimiento();
}, 300000);
</script>