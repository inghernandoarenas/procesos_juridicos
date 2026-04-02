<style>
.hon-global-kpis {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.hon-global-kpi {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,.07);
    border-top: 4px solid #e0e0e0;
    text-align: center;
}
.hon-global-kpi.cobrado  { border-top-color: #27ae60; }
.hon-global-kpi.pagado   { border-top-color: #3498db; }
.hon-global-kpi.pendiente{ border-top-color: #f39c12; }
.hon-global-kpi.vencido  { border-top-color: #e74c3c; }
.hon-global-num   { font-size: 26px; font-weight: 700; color: #2c3e50; margin-bottom: 4px; }
.hon-global-label { font-size: 12px; color: #95a5a6; text-transform: uppercase; letter-spacing: .5px; }

.hon-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; }
.hon-badge.pagado   { background:#eafaf1; color:#27ae60; }
.hon-badge.pendiente{ background:#fef9ec; color:#f39c12; }
.hon-badge.vencido  { background:#fdecea; color:#e74c3c; }
.hon-tipo-badge { display:inline-block; padding:2px 8px; border-radius:4px; font-size:10px; background:#eaf4fd; color:#2980b9; font-weight:600; }

.filtros-bar {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
}
.filtros-bar select, .filtros-bar input {
    padding: 8px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 7px;
    font-size: 13px;
    color: #2c3e50;
}
</style>

<!-- Hero -->
<div style="background:linear-gradient(135deg,#1a2a3a,#27ae60 200%);border-radius:12px;padding:22px 28px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 4px 20px rgba(0,0,0,.15)">
    <div>
        <h2 style="color:white;margin:0;font-size:20px;display:flex;align-items:center;gap:10px">
            <i class="fas fa-dollar-sign"></i> Honorarios
        </h2>
        <p style="color:rgba(255,255,255,.6);font-size:13px;margin:4px 0 0">Control de cobros y pagos por proceso</p>
    </div>
</div>

<!-- KPIs globales -->
<div class="hon-global-kpis" id="globalKpis">
    <div class="hon-global-kpi cobrado"><div class="hon-global-num" id="gkCobrado">—</div><div class="hon-global-label">Cobrado este mes</div></div>
    <div class="hon-global-kpi pendiente"><div class="hon-global-num" id="gkPendiente">—</div><div class="hon-global-label">Pendiente total</div></div>
    <div class="hon-global-kpi vencido"><div class="hon-global-num" id="gkVencido">—</div><div class="hon-global-label">Vencido sin cobrar</div></div>
    <div class="hon-global-kpi pagado"><div class="hon-global-num" id="gkProcesos">—</div><div class="hon-global-label">Procesos con pendiente</div></div>
</div>

<!-- Filtros -->
<div class="filtros-bar">
    <select id="filtroEstadoHon" onchange="filtrarHon()">
        <option value="">Todos los estados</option>
        <option value="pendiente">Pendiente</option>
        <option value="pagado">Pagado</option>
        <option value="vencido">Vencido</option>
    </select>
    <select id="filtroTipoHon" onchange="filtrarHon()">
        <option value="">Todos los tipos</option>
        <option value="pago_puntual">Pago puntual</option>
        <option value="cuota_periodica">Cuota periódica</option>
        <option value="honorario_exito">Honorario de éxito</option>
        <option value="anticipo">Anticipo</option>
        <option value="gasto_reembolsable">Gasto reembolsable</option>
    </select>
    <input type="text" id="filtroBuscarHon" placeholder="Buscar por concepto o radicado..." oninput="filtrarHon()" style="min-width:260px">
    <span id="honCount" style="font-size:12px;color:#95a5a6;margin-left:auto"></span>
</div>

<!-- Tabla global -->
<table id="tablaHonGlobal" style="width:100%;border-collapse:collapse">
    <thead>
        <tr style="background:linear-gradient(90deg,#2c3e50,#34495e)">
            <th style="padding:11px 14px;color:white;font-size:11px;text-align:left;text-transform:uppercase;letter-spacing:.4px">Proceso</th>
            <th style="padding:11px 14px;color:white;font-size:11px;text-align:left;text-transform:uppercase;letter-spacing:.4px">Cliente</th>
            <th style="padding:11px 14px;color:white;font-size:11px;text-align:left;text-transform:uppercase;letter-spacing:.4px">Concepto</th>
            <th style="padding:11px 14px;color:white;font-size:11px;text-align:left;text-transform:uppercase;letter-spacing:.4px">Tipo</th>
            <th style="padding:11px 14px;color:white;font-size:11px;text-align:left;text-transform:uppercase;letter-spacing:.4px">Valor</th>
            <th style="padding:11px 14px;color:white;font-size:11px;text-align:left;text-transform:uppercase;letter-spacing:.4px">Causación</th>
            <th style="padding:11px 14px;color:white;font-size:11px;text-align:left;text-transform:uppercase;letter-spacing:.4px">Pago</th>
            <th style="padding:11px 14px;color:white;font-size:11px;text-align:left;text-transform:uppercase;letter-spacing:.4px">Estado</th>
        </tr>
    </thead>
    <tbody id="honGlobalTbody"></tbody>
</table>
<p id="honGlobalVacio" style="display:none;text-align:center;padding:40px;color:#bdc3c7;font-style:italic">
    <i class="fas fa-file-invoice-dollar" style="font-size:40px;display:block;margin-bottom:10px"></i>
    No hay honorarios registrados
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

const tiposHon = { pago_puntual:'Pago puntual', cuota_periodica:'Cuota periódica', honorario_exito:'Honorario éxito', anticipo:'Anticipo', gasto_reembolsable:'Gasto reembolsable' };
const fmt = v => new Intl.NumberFormat('es-CO',{style:'currency',currency:'COP',minimumFractionDigits:0}).format(v||0);
const fmtF = f => f ? new Date(f+'T00:00:00').toLocaleDateString('es-CO',{day:'2-digit',month:'short',year:'numeric'}) : '—';

let honGlobalData = [];

function cargarHonGlobal() {
    Promise.all([
        fetchWithAuth('/procesos_juridicos/backend/controllers/HonorarioController.php?action=resumen_global').then(r=>r.json()),
        fetchWithAuth('/procesos_juridicos/backend/controllers/HonorarioController.php?action=list_global').then(r=>r.json())
    ]).then(([resumen, lista]) => {
        document.getElementById('gkCobrado').textContent  = fmt(resumen.cobrado_mes);
        document.getElementById('gkPendiente').textContent = fmt(resumen.pendiente_total);
        document.getElementById('gkVencido').textContent  = fmt(resumen.vencido_total);
        document.getElementById('gkProcesos').textContent = resumen.procesos_con_pendiente || '0';
        honGlobalData = lista;
        filtrarHon();
    });
}

function filtrarHon() {
    const estado = document.getElementById('filtroEstadoHon').value;
    const tipo   = document.getElementById('filtroTipoHon').value;
    const buscar = document.getElementById('filtroBuscarHon').value.toLowerCase();

    const filtrado = honGlobalData.filter(h => {
        if (estado && h.estado    !== estado) return false;
        if (tipo   && h.tipo      !== tipo)   return false;
        if (buscar && !h.concepto?.toLowerCase().includes(buscar)
                   && !h.numero_radicado?.toLowerCase().includes(buscar)
                   && !h.cliente?.toLowerCase().includes(buscar)) return false;
        return true;
    });

    document.getElementById('honCount').textContent =
        filtrado.length !== honGlobalData.length
            ? `Mostrando ${filtrado.length} de ${honGlobalData.length}`
            : `${honGlobalData.length} registros`;

    const tbody = document.getElementById('honGlobalTbody');
    const vacio = document.getElementById('honGlobalVacio');
    const tabla = document.getElementById('tablaHonGlobal');

    if (filtrado.length === 0) {
        tabla.style.display = 'none';
        vacio.style.display = 'block';
        return;
    }
    tabla.style.display = '';
    vacio.style.display = 'none';

    tbody.innerHTML = filtrado.map(h => `
        <tr style="border-bottom:1px solid #f0f0f0;transition:background .15s" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background=''">
            <td style="padding:10px 14px"><strong style="color:#3498db">${h.numero_radicado||'—'}</strong></td>
            <td style="padding:10px 14px;font-size:13px">${h.cliente||'—'}</td>
            <td style="padding:10px 14px">
                <div style="font-weight:600;color:#2c3e50">${h.concepto}</div>
                ${h.observaciones ? '<div style="font-size:11px;color:#95a5a6">'+h.observaciones+'</div>' : ''}
            </td>
            <td style="padding:10px 14px"><span class="hon-tipo-badge">${tiposHon[h.tipo]||h.tipo}</span></td>
            <td style="padding:10px 14px"><strong>${fmt(h.valor)}</strong></td>
            <td style="padding:10px 14px;font-size:12px;color:#7f8c8d">${fmtF(h.fecha_causacion)}</td>
            <td style="padding:10px 14px;font-size:12px;color:#7f8c8d">${fmtF(h.fecha_pago)}</td>
            <td style="padding:10px 14px"><span class="hon-badge ${h.estado}">${h.estado}</span></td>
        </tr>`).join('');
}

cargarHonGlobal();
</script>