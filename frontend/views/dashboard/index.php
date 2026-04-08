<style>
/* ── Cards ───────────────────────────────────────────── */
.dash-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    overflow: hidden;
    transition: box-shadow .2s;
}
.dash-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 18px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    user-select: none;
}
.dash-card-header:hover { background: #fafafa; }
.dash-card-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    font-size: 13px;
    color: #2c3e50;
}
.dash-card-meta {
    display: flex;
    align-items: center;
    gap: 10px;
}
.dash-card-count {
    background: #eaf4fd;
    color: #2980b9;
    font-size: 12px;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 20px;
}
.dash-card-count.rojo   { background:#fdecea; color:#e74c3c; }
.dash-card-count.verde  { background:#eafaf1; color:#27ae60; }
.dash-card-count.naranja{ background:#fef9ec; color:#f39c12; }
.dash-toggle {
    font-size: 11px;
    color: #bdc3c7;
    transition: transform .25s;
}
.dash-toggle.abierto { transform: rotate(180deg); }
.dash-card-body {
    padding: 0;
    overflow: hidden;
    max-height: 1000px;
    transition: max-height .3s ease, padding .3s ease;
}
.dash-card-body.colapsado { max-height: 0 !important; }

/* ── Items ───────────────────────────────────────────── */
.dash-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 16px;
    border-bottom: 1px solid #f5f5f5;
    transition: background .15s;
    cursor: default;
    position: relative;
}
.dash-item:last-child { border-bottom: none; }
.dash-item:hover { background: #f8f9fa; }
.dash-item.urgente  { border-left: 3px solid #e74c3c; }
.dash-item.atencion { border-left: 3px solid #f39c12; }
.dash-item-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; flex-shrink: 0;
    background: #eaf4fd; color: #2980b9;
}
.dash-item-icon.urgente  { background: #fdecea; color: #e74c3c; }
.dash-item-icon.atencion { background: #fef9ec; color: #f39c12; }
.dash-item-icon.espera   { background: #eaf4fd; color: #2980b9; }
.dash-item-icon.tacito   { background: #f3e8ff; color: #7c3aed; }
.dash-item-info { flex: 1; min-width: 0; }
.dash-item-radicado { font-size: 12px; font-weight: 700; color: #2c3e50; }
.dash-item-cliente  { font-size: 11px; color: #7f8c8d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dash-item-meta     { font-size: 11px; color: #95a5a6; margin-top: 1px; }
.dash-item-badge {
    font-size: 10px; font-weight: 700; padding: 2px 8px;
    border-radius: 12px; white-space: nowrap; flex-shrink: 0;
}
.dash-item-badge.rojo    { background: #fdecea; color: #e74c3c; }
.dash-item-badge.naranja { background: #fef9ec; color: #f39c12; }
.dash-item-badge.verde   { background: #eafaf1; color: #27ae60; }
.dash-item-badge.azul    { background: #eaf4fd; color: #2980b9; }
.dash-item-badge.violeta { background: #f3e8ff; color: #7c3aed; }
.dash-empty {
    padding: 18px 16px; text-align: center;
    color: #bdc3c7; font-size: 13px;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}

/* ── Semáforo ────────────────────────────────────────── */
.sem-leyenda { display:flex; gap:16px; padding:8px 16px; font-size:11px; color:#7f8c8d; background:#fafafa; border-bottom:1px solid #f0f0f0; }
.sem-item    { display:flex; align-items:center; gap:10px; padding:8px 16px; border-bottom:1px solid #f5f5f5; }
.sem-item:hover { background:#f8f9fa; }
.sem-info    { flex:1; min-width:0; }
.sem-dias    { font-size:10px; font-weight:700; padding:2px 8px; border-radius:10px; white-space:nowrap; }
.sem-dias.rojo     { background:#fdecea; color:#e74c3c; }
.sem-dias.amarillo { background:#fef9ec; color:#f39c12; }
.sem-dias.sin-act  { background:#f3e8ff; color:#7c3aed; }
.sem-dias.tacito   { background:#f3e8ff; color:#7c3aed; }
.semaforo-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; display:inline-block; }
.dot-rojo     { background:#e74c3c; }
.dot-amarillo { background:#f39c12; }
.dot-violeta  { background:#7c3aed; }

/* ── Finanzas ────────────────────────────────────────── */
.fin-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    padding: 10px 14px;
}
.fin-kpi {
    text-align: center;
    border-radius: 8px;
    padding: 9px 8px;
}
.fin-kpi-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
.fin-kpi-val   { font-size: 17px; font-weight: 700; }

/* ── Tooltip ─────────────────────────────────────────── */
#dashTooltip {
    position: fixed; background: rgba(30,40,50,.92); color: white;
    padding: 10px 14px; border-radius: 8px; font-size: 12px; line-height: 1.5;
    max-width: 280px; white-space: pre-line; pointer-events: none;
    z-index: 9999; opacity: 0; transition: opacity .15s;
    box-shadow: 0 4px 16px rgba(0,0,0,.2);
}
</style>

<div id="dashTooltip"></div>

<div class="dashboard" style="padding-top:4px">
    <h2 style="margin-bottom:14px;font-size:17px">Dashboard</h2>

    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">

        <!-- ══ FINANZAS (span 3, no colapsable por defecto) ═══ -->
        <div class="dash-card" style="grid-column:span 3">
            <div class="dash-card-header" onclick="toggleCard('finBody','finToggle')">
                <div class="dash-card-title">
                    <i class="fas fa-chart-line" style="color:#27ae60"></i>
                    Resumen Financiero — Honorarios
                </div>
                <div class="dash-card-meta">
                    <span style="font-size:11px;color:#95a5a6">Globales</span>
                    <i id="finToggle" class="fas fa-chevron-down dash-toggle abierto"></i>
                </div>
            </div>
            <div id="finBody" class="dash-card-body">
                <div class="fin-grid">
                    <div class="fin-kpi" style="background:#eafaf1">
                        <div class="fin-kpi-label" style="color:#27ae60">Cobrado este mes</div>
                        <div class="fin-kpi-val"   id="finCobradoMes"  style="color:#27ae60">—</div>
                    </div>
                    <div class="fin-kpi" style="background:#fef9ec">
                        <div class="fin-kpi-label" style="color:#f39c12">Pendiente por cobrar</div>
                        <div class="fin-kpi-val"   id="finPendiente"   style="color:#f39c12">—</div>
                    </div>
                    <div class="fin-kpi" style="background:#fdecea">
                        <div class="fin-kpi-label" style="color:#e74c3c">Vencido sin cobrar</div>
                        <div class="fin-kpi-val"   id="finVencido"     style="color:#e74c3c">—</div>
                    </div>
                    <div class="fin-kpi" style="background:#eaf4fd">
                        <div class="fin-kpi-label" style="color:#2980b9">Procesos con pendiente</div>
                        <div class="fin-kpi-val"   id="finProcesos"    style="color:#2980b9">—</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ PRÓXIMOS A VENCER ═══════════════════════════════ -->
        <div class="dash-card">
            <div class="dash-card-header" onclick="toggleCard('vencerBody','vencerToggle')">
                <div class="dash-card-title">
                    <i class="fas fa-exclamation-triangle" style="color:#f39c12"></i>
                    Próximos a Vencer
                </div>
                <div class="dash-card-meta">
                    <span id="countVencer" class="dash-card-count">—</span>
                    <i id="vencerToggle" class="fas fa-chevron-down dash-toggle abierto"></i>
                </div>
            </div>
            <div id="vencerBody" class="dash-card-body">
                <div id="proximosVencer"></div>
            </div>
        </div>

        <!-- ══ EN ESPERA ════════════════════════════════════════ -->
        <div class="dash-card">
            <div class="dash-card-header" onclick="toggleCard('esperaBody','esperaToggle')">
                <div class="dash-card-title">
                    <i class="fas fa-hourglass-half" style="color:#3498db"></i>
                    En Espera de Respuesta
                </div>
                <div class="dash-card-meta">
                    <span id="countEspera" class="dash-card-count">—</span>
                    <i id="esperaToggle" class="fas fa-chevron-down dash-toggle abierto"></i>
                </div>
            </div>
            <div id="esperaBody" class="dash-card-body">
                <div id="enEspera"></div>
            </div>
        </div>

        <!-- ══ SILENCIO JUDICIAL ════════════════════════════════ -->
        <div class="dash-card">
            <div class="dash-card-header" onclick="toggleCard('semaforoBody','semaforoToggle')">
                <div class="dash-card-title">
                    <i class="fas fa-traffic-light" style="color:#e74c3c"></i>
                    Silencio Judicial
                </div>
                <div class="dash-card-meta">
                    <span id="countSemaforo" class="dash-card-count">—</span>
                    <i id="semaforoToggle" class="fas fa-chevron-down dash-toggle abierto"></i>
                </div>
            </div>
            <div id="semaforoBody" class="dash-card-body">
                <div id="sinMovimiento"></div>
            </div>
        </div>

        <!-- ══ DESISTIMIENTO TÁCITO (span 3) ════════════════════ -->
        <div class="dash-card" style="grid-column:span 3">
            <div class="dash-card-header" onclick="toggleCard('tacitoBody','tacitoToggle')">
                <div class="dash-card-title">
                    <i class="fas fa-exclamation-circle" style="color:#7c3aed"></i>
                    Riesgo de Desistimiento Tácito
                    <span style="font-size:10px;font-weight:400;color:#95a5a6;margin-left:4px">— sin movimiento ≥ 365 días</span>
                </div>
                <div class="dash-card-meta">
                    <span id="countTacito" class="dash-card-count">—</span>
                    <i id="tacitoToggle" class="fas fa-chevron-down dash-toggle abierto"></i>
                </div>
            </div>
            <div id="tacitoBody" class="dash-card-body">
                <div id="desistimientoTacito"></div>
            </div>
        </div>

    </div>
</div>

<script>
// ── Colapsar / expandir ───────────────────────────────────────
function toggleCard(bodyId, toggleId) {
    const body   = document.getElementById(bodyId);
    const toggle = document.getElementById(toggleId);
    const abierto = !body.classList.contains('colapsado');
    body.classList.toggle('colapsado', abierto);
    toggle.classList.toggle('abierto', !abierto);
}

// ── Tooltip ───────────────────────────────────────────────────
const tip = document.getElementById('dashTooltip');
document.addEventListener('mousemove', e => {
    const el = e.target.closest('[data-dash-tooltip]');
    if (!el) { tip.style.opacity = 0; return; }
    tip.textContent = el.dataset.dashTooltip;
    tip.style.opacity = 1;
    const x = Math.min(e.clientX + 14, window.innerWidth  - tip.offsetWidth  - 10);
    const y = Math.min(e.clientY + 14, window.innerHeight - tip.offsetHeight - 10);
    tip.style.left = x + 'px';
    tip.style.top  = y + 'px';
});

// ── Finanzas ──────────────────────────────────────────────────
function cargarFinanzas() {
    fetchWithAuth('/procesos_juridicos/backend/controllers/HonorarioController.php?action=resumen_global')
        .then(r => r.json())
        .then(d => {
            const fmt = v => new Intl.NumberFormat('es-CO',{style:'currency',currency:'COP',minimumFractionDigits:0}).format(v||0);
            document.getElementById('finCobradoMes').textContent = fmt(d.cobrado_mes);
            document.getElementById('finPendiente').textContent  = fmt(d.pendiente_total);
            document.getElementById('finVencido').textContent    = fmt(d.vencido_total);
            document.getElementById('finProcesos').textContent   = d.procesos_con_pendiente || '0';
        }).catch(() => {});
}

function fetchWithAuth(url, options = {}) {
    const token = localStorage.getItem('token');
    if (!token) { window.location.href = '/procesos_juridicos/frontend/login.php'; return Promise.reject(); }
    options.headers = { ...options.headers, 'Authorization': 'Bearer ' + token };
    return fetch(url, options).then(r => {
        if (r.status === 401) { localStorage.clear(); window.location.href = '/procesos_juridicos/frontend/login.php'; }
        return r;
    });
}

// ── Próximos a vencer ─────────────────────────────────────────
function cargarProximosVencer() {
    fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php?action=proximosVencer')
        .then(r => r.json())
        .then(data => {
            const div = document.getElementById('proximosVencer');
            document.getElementById('countVencer').textContent = data.length || '0';
            if (data.length === 0) {
                div.innerHTML = '<div class="dash-empty"><i class="fas fa-check-circle" style="color:#27ae60"></i>Sin vencimientos próximos</div>';
                return;
            }
            div.innerHTML = data.map(p => {
                const diffDays = Math.ceil((new Date(p.fecha_vencimiento) - new Date()) / 86400000);
                const clase    = diffDays <= 3 ? 'urgente' : diffDays <= 7 ? 'atencion' : 'normal';
                const badge    = diffDays <= 3 ? 'rojo'    : diffDays <= 7 ? 'naranja'   : 'verde';
                const icono    = diffDays <= 3 ? 'fa-fire' : diffDays <= 7 ? 'fa-clock'  : 'fa-calendar-check';
                const tip = `📋 ${p.numero_radicado}\n👤 ${p.nombre} ${p.apellido}\n⚠️ Vence: ${p.fecha_vencimiento}`;
                return `<div class="dash-item ${clase}" data-dash-tooltip="${tip}">
                    <div class="dash-item-icon ${clase}"><i class="fas ${icono}"></i></div>
                    <div class="dash-item-info">
                        <div class="dash-item-radicado">${p.numero_radicado}</div>
                        <div class="dash-item-cliente">${p.nombre} ${p.apellido}</div>
                        <div class="dash-item-meta">Vence: ${p.fecha_vencimiento}</div>
                    </div>
                    <span class="dash-item-badge ${badge}">${diffDays}d</span>
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
            document.getElementById('countEspera').textContent = data.length || '0';
            if (data.length === 0) {
                div.innerHTML = '<div class="dash-empty"><i class="fas fa-inbox"></i>Sin procesos en espera</div>';
                return;
            }
            div.innerHTML = data.map(p => {
                const tip = `📋 ${p.numero_radicado}\n👤 ${p.nombre} ${p.apellido}\n📝 ${p.tipo_proceso||'—'}`;
                return `<div class="dash-item" data-dash-tooltip="${tip}">
                    <div class="dash-item-icon espera"><i class="fas fa-hourglass-half"></i></div>
                    <div class="dash-item-info">
                        <div class="dash-item-radicado">${p.numero_radicado}</div>
                        <div class="dash-item-cliente">${p.nombre} ${p.apellido}</div>
                        <div class="dash-item-meta">${p.tipo_proceso||'—'}</div>
                    </div>
                    <span class="dash-item-badge azul">En espera</span>
                </div>`;
            }).join('');
        });
}

// ── Silencio judicial ─────────────────────────────────────────
function cargarSinMovimiento() {
    fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php?action=sinMovimiento')
        .then(r => r.json())
        .then(data => {
            const div = document.getElementById('sinMovimiento');
            document.getElementById('countSemaforo').textContent = data.length || '0';
            if (data.length === 0) {
                div.innerHTML = '<div class="dash-empty"><i class="fas fa-check-double" style="color:#27ae60"></i>Todos activos recientemente</div>';
                return;
            }
            const rojos    = data.filter(p => !p.dias_sin_movimiento || p.dias_sin_movimiento > 60).length;
            const amarillos = data.filter(p => p.dias_sin_movimiento && p.dias_sin_movimiento <= 60).length;
            let html = `<div class="sem-leyenda">
                <span><span class="semaforo-dot dot-rojo"></span> >60 días (${rojos})</span>
                <span><span class="semaforo-dot dot-amarillo"></span> 30–60 días (${amarillos})</span>
            </div>`;
            html += data.map(p => {
                const dias    = p.dias_sin_movimiento;
                const esRojo  = !dias || dias > 60;
                const clase   = esRojo ? 'rojo' : 'amarillo';
                const ultima  = p.ultima_actuacion ? new Date(p.ultima_actuacion).toLocaleDateString('es-CO') : 'Ninguna';
                const tip     = `📋 ${p.numero_radicado}\n👤 ${p.nombre} ${p.apellido}\n🕐 Última: ${ultima}`;
                return `<div class="sem-item" data-dash-tooltip="${tip}">
                    <span class="semaforo-dot dot-${clase}"></span>
                    <div class="sem-info">
                        <strong style="font-size:12px">${p.numero_radicado}</strong>
                        <div style="font-size:11px;color:#7f8c8d">${p.nombre} ${p.apellido}</div>
                    </div>
                    <span class="sem-dias ${clase}">${dias ? dias+'d' : 'Sin act.'}</span>
                </div>`;
            }).join('');
            div.innerHTML = html;
        });
}

// ── Desistimiento tácito ──────────────────────────────────────
function cargarDesistimiento() {
    fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php?action=desistimientoTacito')
        .then(r => r.json())
        .then(data => {
            const div    = document.getElementById('desistimientoTacito');
            const badge  = document.getElementById('countTacito');
            badge.textContent = data.length || '0';
            if (data.length > 0) badge.classList.add('rojo');

            if (data.length === 0) {
                div.innerHTML = '<div class="dash-empty"><i class="fas fa-shield-alt" style="color:#27ae60"></i>Sin riesgos de desistimiento tácito</div>';
                return;
            }

            div.innerHTML = `
            <div style="background:#fdf4ff;border-bottom:1px solid #e9d8fd;padding:8px 16px;font-size:11px;color:#7c3aed;display:flex;align-items:center;gap:6px">
                <i class="fas fa-info-circle"></i>
                Procesos sin actuaciones por 365+ días — riesgo de desistimiento tácito. Revisar urgentemente.
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0">
            ${data.map(p => {
                const dias   = p.dias_sin_movimiento || '—';
                const ultima = p.ultima_actuacion ? new Date(p.ultima_actuacion).toLocaleDateString('es-CO') : 'Sin actuaciones';
                const anos   = p.dias_sin_movimiento ? (p.dias_sin_movimiento / 365).toFixed(1) : '—';
                const tip    = `📋 ${p.numero_radicado}\n👤 ${p.nombre} ${p.apellido}\n📝 ${p.tipo_proceso||'—'}\n🕐 Última: ${ultima}\n⏱ ${dias} días sin movimiento`;
                return `<div class="dash-item" data-dash-tooltip="${tip}" style="border-right:1px solid #f5f0ff">
                    <div class="dash-item-icon tacito"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="dash-item-info">
                        <div class="dash-item-radicado">${p.numero_radicado}</div>
                        <div class="dash-item-cliente">${p.nombre} ${p.apellido}</div>
                        <div class="dash-item-meta">Última act: ${ultima}</div>
                    </div>
                    <span class="dash-item-badge violeta">${anos} años</span>
                </div>`;
            }).join('')}
            </div>`;
        });
}

// ── Init ──────────────────────────────────────────────────────
cargarFinanzas();
cargarProximosVencer();
cargarEnEspera();
cargarSinMovimiento();
cargarDesistimiento();

setInterval(() => {
    cargarFinanzas();
    cargarProximosVencer();
    cargarEnEspera();
    cargarSinMovimiento();
    cargarDesistimiento();
}, 300000);
</script>