<style>
/* ── Mi Despacho ─────────────────────────────────────────── */
.dsp-hero {
    position: relative;
    border-radius: 14px;
    padding: 18px 24px;
    margin-bottom: 16px;
    color: #fff;
    background: linear-gradient(120deg, #2c3e50 0%, #34495e 55%, #3d566e 100%);
    box-shadow: 0 4px 16px rgba(44,62,80,.25);
    overflow: hidden;
}
.dsp-hero::after {            /* "ventanal" decorativo */
    content: '';
    position: absolute; right: -40px; top: -60px;
    width: 260px; height: 260px; border-radius: 50%;
    background: radial-gradient(circle, rgba(241,196,15,.22) 0%, rgba(241,196,15,0) 70%);
}
.dsp-hero h2 { margin: 0 0 4px; font-size: 19px !important; color: #fff !important; font-weight: 700; }
.dsp-hero p  { margin: 0; font-size: 13px; color: #cfd8dc !important; }
.dsp-hero .dsp-frase { margin-top: 10px; font-style: italic; font-size: 12px; color: #aebdc7 !important; max-width: 560px; }

.dsp-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; }
.dsp-tile { grid-column: span 2; }
.dsp-tile.ancho { grid-column: span 3; }

.dsp-tile {
    position: relative;
    display: flex; flex-direction: column; align-items: center; justify-content: space-between;
    text-align: center;
    background: #fff;
    border-radius: 14px;
    padding: 16px 16px 14px;
    min-height: 176px;
    text-decoration: none;
    color: #2c3e50 !important;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    border-top: 5px solid var(--tc);
    cursor: pointer;
    transition: transform .22s ease, box-shadow .22s ease;
    overflow: hidden;
}
.dsp-tile::before {            /* tinte de fondo suave */
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(180deg, var(--tc-bg) 0%, #fff 62%);
    opacity: .9; pointer-events: none;
}
.dsp-tile > * { position: relative; }
.dsp-tile:hover { transform: translateY(-6px); box-shadow: 0 14px 28px rgba(0,0,0,.14); }
.dsp-tile:focus-visible { outline: 3px solid var(--tc); outline-offset: 2px; }

.dsp-ilustra { width: 84px; height: 70px; }
.dsp-ilustra svg { width: 100%; height: 100%; overflow: visible; }
.dsp-titulo { font-size: 15px; font-weight: 700; margin: 4px 0 1px; color: #2c3e50; }
.dsp-sub    { font-size: 11px; color: #7f8c8d; min-height: 14px; }
.dsp-dato   { font-size: 22px; font-weight: 800; color: var(--tc); line-height: 1.1; margin-top: 4px; }
.dsp-dato small { font-size: 11px; font-weight: 600; color: #7f8c8d; margin-left: 4px; }
.dsp-entrar { margin-top: 8px; font-size: 10px; font-weight: 700; color: var(--tc); letter-spacing: .4px; text-transform: uppercase; opacity: .85; }
.dsp-entrar i { margin-left: 4px; transition: transform .2s; }
.dsp-tile:hover .dsp-entrar i { transform: translateX(4px); }

.dsp-globo {                   /* globito de alerta */
    position: absolute; top: 10px; right: 10px;
    min-width: 24px; height: 24px; padding: 0 7px;
    border-radius: 12px; background: var(--tc); color: #fff;
    font-size: 12px; font-weight: 800;
    display: none; align-items: center; justify-content: center;
    box-shadow: 0 2px 6px rgba(0,0,0,.2);
}
.dsp-globo.visible { display: flex; }

.dsp-cinta {                   /* cinta "En construcción" */
    position: absolute; top: 20px; right: -42px; transform: rotate(45deg);
    width: 150px; background: #f39c12; color: #fff;
    font-size: 9px; font-weight: 800; letter-spacing: .4px;
    text-transform: uppercase; padding: 4px 0; text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,.15);
}
.dsp-tile.construccion { cursor: default; }
.dsp-tile.construccion .dsp-ilustra { filter: saturate(.75); }

/* ── Animaciones de los objetos ─────────────────────────── */
.dsp-tile .tapa    { transform-origin: 60px 38px; transition: transform .3s ease; }
.dsp-tile:hover .tapa { transform: translateY(-7px) rotate(-3deg); }
.dsp-tile .campana { transform-origin: 60px 22px; }
.dsp-tile:hover .campana { animation: dspMecer .7s ease-in-out infinite; }
.dsp-tile .platillos { transform-origin: 60px 26px; transition: transform .35s ease; }
.dsp-tile:hover .platillos { animation: dspBalanza 1.6s ease-in-out infinite; }
.dsp-tile .hoja { transition: transform .3s ease; transform-origin: 60px 60px; }
.dsp-tile:hover .hoja { transform: translateY(-6px); }
@keyframes dspMecer   { 0%,100% { transform: rotate(-9deg); } 50% { transform: rotate(9deg); } }
@keyframes dspBalanza { 0%,100% { transform: rotate(-4deg); } 50% { transform: rotate(4deg); } }
@media (prefers-reduced-motion: reduce) {
    .dsp-tile, .dsp-tile * { animation: none !important; transition: none !important; }
}
@media (max-width: 1100px) {
    .dsp-grid { grid-template-columns: repeat(2, 1fr); }
    .dsp-tile, .dsp-tile.ancho { grid-column: span 1; }
}
@media (max-width: 640px) {
    .dsp-grid { grid-template-columns: 1fr; }
}
</style>

<div class="dsp-hero">
    <h2 id="dspSaludo">Bienvenido a tu despacho</h2>
    <p class="dsp-fecha" id="dspFecha"></p>
    <p class="dsp-frase" id="dspFrase"></p>
</div>

<div class="dsp-grid">

    <!-- ══ PROCESOS (maletín) ═══════════════════════════════ -->
    <a class="dsp-tile" href="/procesos_juridicos/frontend/index.php?view=procesos"
       style="--tc:#2980b9;--tc-bg:#eaf4fd" aria-label="Ir a Procesos">
        <div class="dsp-ilustra">
            <svg viewBox="0 0 120 100" aria-hidden="true">
                <ellipse cx="60" cy="92" rx="38" ry="5" fill="#000" opacity=".08"/>
                <!-- asa -->
                <path d="M44 34 V24 a6 6 0 0 1 6-6 h20 a6 6 0 0 1 6 6 V34" fill="none" stroke="#1f618d" stroke-width="6" stroke-linecap="round"/>
                <!-- cuerpo -->
                <rect x="14" y="34" width="92" height="52" rx="9" fill="#2980b9"/>
                <rect x="14" y="58" width="92" height="28" rx="9" fill="#2471a3"/>
                <!-- tapa -->
                <g class="tapa">
                    <rect x="14" y="34" width="92" height="26" rx="9" fill="#3498db"/>
                    <rect x="14" y="52" width="92" height="8" fill="#2e86c1"/>
                </g>
                <!-- cierre -->
                <rect x="52" y="52" width="16" height="14" rx="3" fill="#f1c40f"/>
                <rect x="57" y="57" width="6" height="5" rx="1.5" fill="#b7950b"/>
                <!-- esquineros -->
                <circle cx="22" cy="78" r="2.5" fill="#f1c40f"/><circle cx="98" cy="78" r="2.5" fill="#f1c40f"/>
            </svg>
        </div>
        <div>
            <div class="dsp-titulo">Procesos</div>
            <div class="dsp-sub">Tu maletín con todos los expedientes</div>
            <div class="dsp-dato"><span id="dspActivos" data-cuenta>—</span><small>activos</small></div>
        </div>
        <div class="dsp-entrar">Abrir maletín <i class="fas fa-arrow-right"></i></div>
    </a>

    <!-- ══ ALERTAS (campana) ════════════════════════════════ -->
    <a class="dsp-tile" id="dspTileAlertas" href="/procesos_juridicos/frontend/index.php?view=dashboard"
       style="--tc:#27ae60;--tc-bg:#eafaf1" aria-label="Ir a Alertas">
        <span class="dsp-globo" id="dspGloboAlertas">0</span>
        <div class="dsp-ilustra">
            <svg viewBox="0 0 120 100" aria-hidden="true">
                <ellipse cx="60" cy="92" rx="30" ry="4" fill="#000" opacity=".08"/>
                <g class="campana">
                    <circle cx="60" cy="16" r="5" fill="#f1c40f"/>
                    <path d="M60 20 C38 20 32 40 32 56 C32 66 26 70 22 76 H98 C94 70 88 66 88 56 C88 40 82 20 60 20 Z" fill="var(--tc)"/>
                    <path d="M60 20 C74 22 82 36 83 52 C84 64 90 69 94 74 H98 C94 70 88 66 88 56 C88 40 82 20 60 20 Z" fill="#000" opacity=".12"/>
                    <rect x="20" y="74" width="80" height="7" rx="3.5" fill="var(--tc)"/>
                    <path d="M50 84 a10 10 0 0 0 20 0 Z" fill="#f1c40f"/>
                    <path d="M44 34 C40 40 39 48 39 54" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".5"/>
                </g>
            </svg>
        </div>
        <div>
            <div class="dsp-titulo">Alertas</div>
            <div class="dsp-sub" id="dspSubAlertas">Revisando tu despacho…</div>
            <div class="dsp-dato"><span id="dspAlertas" data-cuenta>—</span><small>pendientes</small></div>
        </div>
        <div class="dsp-entrar">Ver alertas <i class="fas fa-arrow-right"></i></div>
    </a>

    <!-- ══ CALENDARIO (en construcción) ═════════════════════ -->
    <div class="dsp-tile construccion" role="button" tabindex="0"
         style="--tc:#8e44ad;--tc-bg:#f5eef8" aria-label="Calendario (en construcción)"
         onclick="dspProximamente()" onkeydown="if(event.key==='Enter')dspProximamente()">
        <span class="dsp-cinta">Construcción</span>
        <div class="dsp-ilustra">
            <svg viewBox="0 0 120 100" aria-hidden="true">
                <ellipse cx="60" cy="92" rx="36" ry="4" fill="#000" opacity=".08"/>
                <rect x="18" y="20" width="84" height="68" rx="9" fill="#fff" stroke="#d7bde2" stroke-width="2"/>
                <path d="M18 29 a9 9 0 0 1 9-9 h66 a9 9 0 0 1 9 9 V42 H18 Z" fill="#8e44ad"/>
                <rect x="36" y="12" width="6" height="16" rx="3" fill="#5b2c6f"/>
                <rect x="78" y="12" width="6" height="16" rx="3" fill="#5b2c6f"/>
                <g fill="#d7bde2">
                    <rect x="28" y="50" width="12" height="9" rx="2"/><rect x="45" y="50" width="12" height="9" rx="2"/>
                    <rect x="62" y="50" width="12" height="9" rx="2"/><rect x="79" y="50" width="12" height="9" rx="2"/>
                    <rect x="28" y="64" width="12" height="9" rx="2"/><rect x="45" y="64" width="12" height="9" rx="2"/>
                    <rect x="79" y="64" width="12" height="9" rx="2"/>
                </g>
                <rect x="62" y="64" width="12" height="9" rx="2" fill="#8e44ad"/>
            </svg>
        </div>
        <div>
            <div class="dsp-titulo">Calendario</div>
            <div class="dsp-sub">Audiencias, términos y citas en un solo lugar</div>
            <div class="dsp-dato" style="font-size:13px">Próximamente</div>
        </div>
        <div class="dsp-entrar" style="color:#95a5a6">Estamos trabajando en esto</div>
    </div>

    <!-- ══ HONORARIOS (balanza) ═════════════════════════════ -->
    <a class="dsp-tile" href="/procesos_juridicos/frontend/index.php?view=honorarios"
       style="--tc:#f39c12;--tc-bg:#fef9ec" aria-label="Ir a Honorarios">
        <div class="dsp-ilustra">
            <svg viewBox="0 0 120 100" aria-hidden="true">
                <ellipse cx="60" cy="93" rx="30" ry="4" fill="#000" opacity=".08"/>
                <rect x="56" y="22" width="8" height="62" rx="3" fill="#b9770e"/>
                <rect x="38" y="82" width="44" height="8" rx="4" fill="#9c640c"/>
                <circle cx="60" cy="20" r="6" fill="#f1c40f"/>
                <g class="platillos">
                    <rect x="14" y="26" width="92" height="5" rx="2.5" fill="#d68910"/>
                    <path d="M22 31 L12 56 M22 31 L32 56" stroke="#b9770e" stroke-width="2" fill="none"/>
                    <path d="M98 31 L88 56 M98 31 L108 56" stroke="#b9770e" stroke-width="2" fill="none"/>
                    <path d="M8 56 H36 a14 10 0 0 1 -28 0 Z" fill="#f39c12"/>
                    <path d="M84 56 H112 a14 10 0 0 1 -28 0 Z" fill="#f39c12"/>
                </g>
            </svg>
        </div>
        <div>
            <div class="dsp-titulo">Honorarios</div>
            <div class="dsp-sub">Lo que tienes por cobrar</div>
            <div class="dsp-dato"><span id="dspPendiente">—</span><small>pendiente</small></div>
        </div>
        <div class="dsp-entrar">Ver honorarios <i class="fas fa-arrow-right"></i></div>
    </a>

    <!-- ══ CLIENTES (carpeta) ═══════════════════════════════ -->
    <a class="dsp-tile" href="/procesos_juridicos/frontend/index.php?view=clientes"
       style="--tc:#16a085;--tc-bg:#e8f8f5" aria-label="Ir a Clientes">
        <div class="dsp-ilustra">
            <svg viewBox="0 0 120 100" aria-hidden="true">
                <ellipse cx="60" cy="92" rx="38" ry="4" fill="#000" opacity=".08"/>
                <path d="M14 30 a7 7 0 0 1 7-7 h26 l9 10 h43 a7 7 0 0 1 7 7 V80 a7 7 0 0 1 -7 7 H21 a7 7 0 0 1 -7-7 Z" fill="#0e8a74"/>
                <g class="hoja">
                    <rect x="24" y="36" width="72" height="44" rx="3" fill="#fff"/>
                    <rect x="32" y="44" width="40" height="4" rx="2" fill="#a2d9ce"/>
                    <rect x="32" y="53" width="54" height="4" rx="2" fill="#d0ece7"/>
                    <rect x="32" y="62" width="48" height="4" rx="2" fill="#d0ece7"/>
                </g>
                <path d="M14 48 H106 V80 a7 7 0 0 1 -7 7 H21 a7 7 0 0 1 -7-7 Z" fill="#16a085"/>
                <circle cx="60" cy="67" r="9" fill="#fff" opacity=".9"/>
                <circle cx="60" cy="64" r="3.5" fill="#16a085"/>
                <path d="M53 73 a7 6 0 0 1 14 0 Z" fill="#16a085"/>
            </svg>
        </div>
        <div>
            <div class="dsp-titulo">Clientes</div>
            <div class="dsp-sub">Las personas a quienes representas</div>
            <div class="dsp-dato"><span id="dspClientes" data-cuenta>—</span><small>registrados</small></div>
        </div>
        <div class="dsp-entrar">Ver clientes <i class="fas fa-arrow-right"></i></div>
    </a>

    <!-- ══ PLANTILLAS (documento y pluma) ═══════════════════ -->
    <a class="dsp-tile" href="/procesos_juridicos/frontend/index.php?view=plantillas"
       style="--tc:#e67e22;--tc-bg:#fdf2e9" aria-label="Ir a Plantillas">
        <div class="dsp-ilustra">
            <svg viewBox="0 0 120 100" aria-hidden="true">
                <ellipse cx="60" cy="92" rx="34" ry="4" fill="#000" opacity=".08"/>
                <rect x="30" y="12" width="56" height="74" rx="6" fill="#fff" stroke="#f0b27a" stroke-width="2"/>
                <path d="M30 18 a6 6 0 0 1 6-6 h30 l20 20 V80 a6 6 0 0 1 -6 6 H36 a6 6 0 0 1 -6-6 Z" fill="#fff" stroke="#f0b27a" stroke-width="2"/>
                <path d="M66 12 V26 a6 6 0 0 0 6 6 H86 Z" fill="#fad7b5"/>
                <rect x="40" y="38" width="32" height="4" rx="2" fill="#e67e22"/>
                <rect x="40" y="48" width="38" height="3" rx="1.5" fill="#f5cba7"/>
                <rect x="40" y="56" width="38" height="3" rx="1.5" fill="#f5cba7"/>
                <rect x="40" y="64" width="24" height="3" rx="1.5" fill="#f5cba7"/>
                <g class="hoja" style="transform-origin:90px 70px">
                    <path d="M84 84 L88 66 L104 50 L112 58 L96 74 Z" fill="#e67e22"/>
                    <path d="M88 66 L96 74 L84 84 Z" fill="#f1c40f"/>
                    <path d="M104 50 L108 46 a3 3 0 0 1 4 0 l4 4 a3 3 0 0 1 0 4 L112 58 Z" fill="#2c3e50"/>
                </g>
            </svg>
        </div>
        <div>
            <div class="dsp-titulo">Plantillas</div>
            <div class="dsp-sub">Poderes, demandas, memoriales y más</div>
            <div class="dsp-dato"><span id="dspPlantillas" data-cuenta>—</span><small>disponibles</small></div>
        </div>
        <div class="dsp-entrar">Crear documento <i class="fas fa-arrow-right"></i></div>
    </a>

</div>

<script>
const DSP_API = '/procesos_juridicos/backend/controllers/';

function dspFetch(url) {
    const token = localStorage.getItem('token');
    if (!token) { window.location.href = '/procesos_juridicos/frontend/login.php'; return Promise.reject(); }
    return fetch(url, { headers: { 'Authorization': 'Bearer ' + token } }).then(r => {
        if (r.status === 401) { localStorage.clear(); window.location.href = '/procesos_juridicos/frontend/login.php'; }
        return r.json();
    });
}

// Cuenta animada de 0 al valor
function dspContar(id, valor) {
    const el = document.getElementById(id);
    if (!el) return;
    valor = parseInt(valor, 10) || 0;
    if (valor === 0 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { el.textContent = valor; return; }
    const dur = 700, t0 = performance.now();
    (function paso(t) {
        const p = Math.min(1, (t - t0) / dur);
        el.textContent = Math.round(valor * (1 - Math.pow(1 - p, 3)));
        if (p < 1) requestAnimationFrame(paso);
    })(t0);
}

function dspProximamente() {
    if (typeof toast === 'function') toast('El Calendario está en construcción. ¡Muy pronto!', 'info');
}

// ── Saludo ──────────────────────────────────────────────────
(function () {
    let nombre = '';
    try { nombre = (JSON.parse(localStorage.getItem('user')) || {}).nombre || ''; } catch (e) {}
    const h = new Date().getHours();
    const saludo = h < 12 ? 'Buenos días' : (h < 19 ? 'Buenas tardes' : 'Buenas noches');
    document.getElementById('dspSaludo').textContent = saludo + (nombre ? ', ' + nombre.split(' ')[0] : '');
    const fecha = new Date().toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    document.getElementById('dspFecha').textContent = fecha.charAt(0).toUpperCase() + fecha.slice(1);
    const frases = [
        'La justicia es la constante y perpetua voluntad de dar a cada uno su derecho.',
        'El derecho es el arte de lo bueno y de lo justo.',
        'Donde hay una necesidad, nace un derecho.',
        'La paciencia y la constancia ganan los procesos largos.',
        'Un buen expediente al día es media audiencia ganada.'
    ];
    document.getElementById('dspFrase').textContent = '“' + frases[new Date().getDate() % frases.length] + '”';
})();

// ── Datos en vivo ───────────────────────────────────────────
dspFetch(DSP_API + 'ProcesoController.php?action=stats')
    .then(d => dspContar('dspActivos', d.activos)).catch(() => {});

dspFetch(DSP_API + 'PlantillaController.php?action=list')
    .then(d => dspContar('dspPlantillas', Array.isArray(d) ? d.length : 0)).catch(() => {});

dspFetch(DSP_API + 'ClienteController.php?action=list&pagina=1')
    .then(d => dspContar('dspClientes', d.total)).catch(() => {});

dspFetch(DSP_API + 'HonorarioController.php?action=resumen_global')
    .then(d => {
        document.getElementById('dspPendiente').textContent =
            new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(d.pendiente_total || 0);
    }).catch(() => {});

// Alertas = próximos a vencer + silencio judicial + riesgo de desistimiento tácito
Promise.all([
    dspFetch(DSP_API + 'ProcesoController.php?action=proximosVencer').catch(() => []),
    dspFetch(DSP_API + 'ProcesoController.php?action=sinMovimiento').catch(() => []),
    dspFetch(DSP_API + 'ProcesoController.php?action=desistimientoTacito').catch(() => [])
]).then(([vencer, silencio, tacito]) => {
    vencer  = Array.isArray(vencer)  ? vencer  : [];
    silencio = Array.isArray(silencio) ? silencio : [];
    tacito  = Array.isArray(tacito)  ? tacito  : [];

    const hoy = new Date(); hoy.setHours(0, 0, 0, 0);
    const urgentesVencer = vencer.filter(p => {
        const dias = Math.ceil((new Date(p.fecha_vencimiento + 'T00:00:00') - hoy) / 86400000);
        return dias <= 3;
    }).length;
    const silencioRojo = silencio.filter(p => p.dias_sin_movimiento === null || p.dias_sin_movimiento > 60).length;

    const total = vencer.length + silencio.length + tacito.length;
    const rojo  = urgentesVencer > 0 || tacito.length > 0 || silencioRojo > 0;

    const tile  = document.getElementById('dspTileAlertas');
    const globo = document.getElementById('dspGloboAlertas');
    const color = total === 0 ? ['#27ae60', '#eafaf1'] : (rojo ? ['#e74c3c', '#fdecea'] : ['#f39c12', '#fef9ec']);
    tile.style.setProperty('--tc', color[0]);
    tile.style.setProperty('--tc-bg', color[1]);

    dspContar('dspAlertas', total);
    if (total > 0) { globo.textContent = total; globo.classList.add('visible'); }

    const partes = [];
    if (vencer.length)  partes.push(vencer.length + ' por vencer');
    if (silencio.length) partes.push(silencio.length + ' en silencio');
    if (tacito.length)  partes.push(tacito.length + ' en riesgo tácito');
    document.getElementById('dspSubAlertas').textContent = partes.length ? partes.join(' · ') : 'Todo en orden, sin pendientes';
});
</script>