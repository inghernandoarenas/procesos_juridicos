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
/* ── Cards generales del dashboard ──────────────── */
.dash-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.07);
    overflow: hidden;
}
.dash-card-header {
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #f0f0f0;
}
.dash-card-title {
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 8px;
}
.dash-card-count {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
    background: #f0f4f8;
    color: #5d6d7e;
}
.dash-card-body {
    padding: 12px;
    max-height: 380px;
    overflow-y: auto;
}

/* ── Items de próximos a vencer y en espera ──── */
.dash-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    margin-bottom: 8px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #e0e0e0;
    cursor: help;
    transition: transform .15s, box-shadow .15s;
}
.dash-item:hover {
    transform: translateX(4px);
    box-shadow: 0 3px 10px rgba(0,0,0,.08);
}
.dash-item:last-child { margin-bottom: 0; }
.dash-item.urgente  { border-left-color: #e74c3c; background: #fef5f5; }
.dash-item.atencion { border-left-color: #f39c12; background: #fff8e7; }
.dash-item.normal   { border-left-color: #27ae60; background: #f0fff4; }

.dash-item-icon {
    width: 36px; height: 36px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
}
.dash-item-icon.urgente  { background: #fdecea; color: #e74c3c; }
.dash-item-icon.atencion { background: #fef9ec; color: #f39c12; }
.dash-item-icon.normal   { background: #eafaf1; color: #27ae60; }
.dash-item-icon.espera   { background: #eaf4fd; color: #3498db; }

.dash-item-info { flex: 1; min-width: 0; }
.dash-item-radicado {
    font-size: 13px; font-weight: 700;
    color: #2c3e50; white-space: nowrap;
    overflow: hidden; text-overflow: ellipsis;
}
.dash-item-cliente {
    font-size: 11px; color: #7f8c8d; margin-top: 1px;
}
.dash-item-meta {
    font-size: 10px; color: #95a5a6; margin-top: 2px;
}
.dash-item-badge {
    font-size: 10px; font-weight: 700;
    padding: 3px 8px; border-radius: 20px;
    white-space: nowrap; flex-shrink: 0;
}
.dash-item-badge.rojo    { background: #fdecea; color: #e74c3c; }
.dash-item-badge.naranja { background: #fef9ec; color: #f39c12; }
.dash-item-badge.verde   { background: #eafaf1; color: #27ae60; }
.dash-item-badge.azul    { background: #eaf4fd; color: #2980b9; }

.dash-empty {
    text-align: center; padding: 30px 20px;
    color: #bdc3c7; font-size: 13px; font-style: italic;
}
.dash-empty i { font-size: 32px; display: block; margin-bottom: 8px; }

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

    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px">

        <!-- ── Próximos a vencer ─────────────────────────── -->
        <div class="dash-card">
            <div class="dash-card-header">
                <div class="dash-card-title">
                    <i class="fas fa-exclamation-triangle" style="color:#f39c12"></i>
                    Próximos a Vencer
                </div>
                <span id="countVencer" class="dash-card-count">—</span>
            </div>
            <div class="dash-card-body">
                <div id="proximosVencer"></div>
            </div>
        </div>

        <!-- ── En espera ─────────────────────────────────── -->
        <div class="dash-card">
            <div class="dash-card-header">
                <div class="dash-card-title">
                    <i class="fas fa-hourglass-half" style="color:#3498db"></i>
                    En Espera de Respuesta
                </div>
                <span id="countEspera" class="dash-card-count">—</span>
            </div>
            <div class="dash-card-body">
                <div id="enEspera"></div>
            </div>
        </div>

        <!-- ── Semáforo ───────────────────────────────────── -->
        <div class="dash-card">
            <div class="dash-card-header">
                <div class="dash-card-title">
                    <i class="fas fa-traffic-light" style="color:#e74c3c"></i>
                    Silencio Judicial
                </div>
                <span id="countSemaforo" class="dash-card-count">—</span>
            </div>
            <div class="dash-card-body">
                <div id="sinMovimiento"></div>
            </div>
        </div>

        <!-- ── Widget financiero ───────────────────────────────── -->
        <div class="dash-card" style="grid-column:span 3;margin-top:4px">
            <div class="dash-card-header">
                <div class="dash-card-title">
                    <i class="fas fa-chart-line" style="color:#27ae60"></i>
                    Resumen Financiero
                </div>
                <span style="font-size:11px;color:#95a5a6">Honorarios globales</span>
            </div>
            <div class="dash-card-body" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;padding:16px">
                <div style="text-align:center;background:#eafaf1;border-radius:10px;padding:16px">
                    <div style="font-size:11px;color:#27ae60;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Cobrado este mes</div>
                    <div id="finCobradoMes" style="font-size:22px;font-weight:700;color:#27ae60">—</div>
                </div>
                <div style="text-align:center;background:#fef9ec;border-radius:10px;padding:16px">
                    <div style="font-size:11px;color:#f39c12;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Pendiente por cobrar</div>
                    <div id="finPendiente" style="font-size:22px;font-weight:700;color:#f39c12">—</div>
                </div>
                <div style="text-align:center;background:#fdecea;border-radius:10px;padding:16px">
                    <div style="font-size:11px;color:#e74c3c;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Vencido sin cobrar</div>
                    <div id="finVencido" style="font-size:22px;font-weight:700;color:#e74c3c">—</div>
                </div>
                <div style="text-align:center;background:#eaf4fd;border-radius:10px;padding:16px">
                    <div style="font-size:11px;color:#2980b9;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Procesos con pendiente</div>
                    <div id="finProcesos" style="font-size:22px;font-weight:700;color:#2980b9">—</div>
                </div>
            </div>
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
                div.innerHTML = '<div class="dash-empty"><i class="fas fa-check-circle" style="color:#27ae60"></i>Sin vencimientos próximos</div>';
                return;
            }
            document.getElementById('countVencer').textContent = data.length;
            div.innerHTML = data.map(p => {
                const diffDays = Math.ceil((new Date(p.fecha_vencimiento) - new Date()) / 86400000);
                let clase = 'normal', claseBadge = 'verde', claseIcon = 'normal';
                if      (diffDays <= 3) { clase = 'urgente';  claseBadge = 'rojo';    claseIcon = 'urgente';  }
                else if (diffDays <= 7) { clase = 'atencion'; claseBadge = 'naranja'; claseIcon = 'atencion'; }

                const tip = [
                    '📋 Radicado: ' + p.numero_radicado,
                    '👤 Cliente: '  + p.nombre + ' ' + p.apellido,
                    '📝 Tipo: '     + (p.tipo_proceso || '—'),
                    '📅 Inicio: '   + p.fecha_inicio,
                    '⚠️ Vence: '    + p.fecha_vencimiento,
                    '📊 Estado: '   + (p.estado || '—'),
                    p.descripcion ? '📄 ' + p.descripcion.substring(0,100) : ''
                ].filter(Boolean).join('\n');

                const icono = diffDays <= 3 ? 'fa-fire' : diffDays <= 7 ? 'fa-clock' : 'fa-calendar-check';

                return `
                <div class="dash-item ${clase}"
                     data-dash-tooltip="${tip.replace(/"/g,'&quot;')}">
                    <div class="dash-item-icon ${claseIcon}">
                        <i class="fas ${icono}"></i>
                    </div>
                    <div class="dash-item-info">
                        <div class="dash-item-radicado">${p.numero_radicado}</div>
                        <div class="dash-item-cliente">${p.nombre} ${p.apellido}</div>
                        <div class="dash-item-meta">Vence: ${p.fecha_vencimiento}</div>
                    </div>
                    <span class="dash-item-badge ${claseBadge}">${diffDays}d</span>
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
                div.innerHTML = '<div class="dash-empty"><i class="fas fa-inbox"></i>Sin procesos en espera</div>';
                return;
            }
            document.getElementById('countEspera').textContent = data.length;
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
                <div class="dash-item"
                     data-dash-tooltip="${tip.replace(/"/g,'&quot;')}">
                    <div class="dash-item-icon espera">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="dash-item-info">
                        <div class="dash-item-radicado">${p.numero_radicado}</div>
                        <div class="dash-item-cliente">${p.nombre} ${p.apellido}</div>
                        <div class="dash-item-meta">${p.tipo_proceso || '—'}</div>
                    </div>
                    <span class="dash-item-badge azul">En espera</span>
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
                div.innerHTML = '<div class="dash-empty"><i class="fas fa-check-double" style="color:#27ae60"></i>Todos activos recientemente</div>';
                document.getElementById('countSemaforo').textContent = '0';
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
    cargarFinanzas();
}, 300000);

function cargarFinanzas() {
    fetchWithAuth('/procesos_juridicos/backend/controllers/HonorarioController.php?action=resumen_global')
        .then(r => r.json())
        .then(d => {
            const fmt = v => new Intl.NumberFormat('es-CO',{style:'currency',currency:'COP',minimumFractionDigits:0}).format(v||0);
            document.getElementById('finCobradoMes').textContent = fmt(d.cobrado_mes);
            document.getElementById('finPendiente').textContent  = fmt(d.pendiente_total);
            document.getElementById('finVencido').textContent    = fmt(d.vencido_total);
            document.getElementById('finProcesos').textContent   = d.procesos_con_pendiente || '0';
        })
        .catch(() => {});
}

cargarFinanzas();
</script>