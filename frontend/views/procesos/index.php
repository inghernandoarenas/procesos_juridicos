<style>
/* ══════════════════════════════════════════
   TIMELINE DE ACTUACIONES
══════════════════════════════════════════ */
.timeline-wrap {
    padding: 10px 5px 10px 10px;
    max-height: 520px;
    overflow-y: auto;
}

.timeline {
    position: relative;
    padding-left: 36px;
}

/* Línea vertical central */
.timeline::before {
    content: '';
    position: absolute;
    left: 11px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #3498db, #e0e0e0);
    border-radius: 2px;
}

.tl-item {
    position: relative;
    margin-bottom: 28px;
    animation: fadeInUp .3s ease both;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Punto en la línea */
.tl-dot {
    position: absolute;
    left: -29px;
    top: 6px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #3498db;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #3498db;
    transition: transform .2s;
}

.tl-item:hover .tl-dot {
    transform: scale(1.3);
    background: #2980b9;
}

/* Primera actuación: punto dorado */
.tl-item:first-child .tl-dot {
    background: #f39c12;
    box-shadow: 0 0 0 2px #f39c12;
}

/* Tarjeta de cada actuación */
.tl-card {
    background: #fff;
    border: 1px solid #e8ecef;
    border-radius: 10px;
    padding: 14px 16px;
    box-shadow: 0 2px 6px rgba(0,0,0,.06);
    transition: box-shadow .2s, transform .2s;
    cursor: default;
}

.tl-card:hover {
    box-shadow: 0 6px 18px rgba(52,152,219,.15);
    transform: translateX(3px);
}

.tl-fecha {
    font-size: 11px;
    font-weight: 700;
    color: #3498db;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.tl-titulo {
    font-size: 14px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
    line-height: 1.4;
}

.tl-obs {
    font-size: 12px;
    color: #7f8c8d;
    line-height: 1.5;
    padding-top: 6px;
    border-top: 1px dashed #eee;
    margin-top: 6px;
}

.tl-badge-nueva {
    display: inline-block;
    background: #27ae60;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: .5px;
    vertical-align: middle;
}

.tl-empty {
    text-align: center;
    padding: 50px 20px;
    color: #aaa;
}

.tl-empty i { font-size: 40px; margin-bottom: 10px; display: block; }

/* Header del modal de actuaciones */
.tl-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.tl-contador {
    font-size: 12px;
    color: #7f8c8d;
    background: #f0f4f8;
    padding: 4px 12px;
    border-radius: 20px;
}
</style>

<script>
let paginaActualProcesos = 1;
let totalPaginasProcesos = 1;
let terminoBusqueda = '';
let procesoActual = 0;

function fetchWithAuth(url, options = {}) {
    const token = localStorage.getItem('token');
    if (!token) { window.location.href = '/procesos_juridicos/frontend/login.php'; return Promise.reject(); }
    options.headers = { ...options.headers, 'Authorization': 'Bearer ' + token };
    return fetch(url, options).then(r => {
        if (r.status === 401) { localStorage.clear(); window.location.href = '/procesos_juridicos/frontend/login.php'; }
        return r;
    });
}
</script>

<!-- ══ CABECERA ══════════════════════════════════════════════ -->
<div class="page-header">
    <h2>Gestión de Procesos</h2>
    <button class="btn btn-primary" onclick="abrirModalProceso()">Nuevo Proceso</button>
</div>

<!-- ══ BUSCADOR ══════════════════════════════════════════════ -->
<div style="margin-bottom:20px;display:flex;gap:10px;max-width:500px">
    <div style="flex:1;position:relative">
        <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#95a5a6"></i>
        <input type="text" id="buscarProcesos" placeholder="Buscar por radicado, cliente, tipo o descripción..."
               style="width:100%;padding:12px 12px 12px 40px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px">
    </div>
    <button class="btn btn-primary" onclick="buscarProcesos()" style="padding:0 25px">
        <i class="fas fa-search"></i> Buscar
    </button>
    <button class="btn btn-secondary" onclick="limpiarBusqueda()" style="padding:0 20px;background:#95a5a6">
        <i class="fas fa-times"></i> Limpiar
    </button>
</div>

<!-- ══ TABLA ═════════════════════════════════════════════════ -->
<table id="tablaProcesos">
    <thead>
        <tr>
            <th>ID</th><th>Radicado</th><th>Cliente</th>
            <th>Tipo</th><th>Estado</th><th>Vencimiento</th><th>Acciones</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>
<div id="paginacionProcesos" class="pagination-container"
     style="margin-top:20px;display:flex;justify-content:center;align-items:center;gap:10px"></div>

<!-- ══ MODAL CREAR / EDITAR PROCESO ══════════════════════════ -->
<div id="modalProceso" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModalProceso()">&times;</span>
        <h3 id="modalProcesoTitle">Nuevo Proceso</h3>
        <form id="formProceso" onsubmit="guardarProceso(event)">
            <input type="hidden" id="procesoId" name="id">
            <div class="form-group">
                <label>Cliente:</label>
                <div style="display:flex;gap:10px;align-items:center">
                    <select id="cliente_id" name="cliente_id" required style="flex:1">
                        <option value="">Seleccione un cliente</option>
                    </select>
                    <button type="button" class="btn-icon" onclick="abrirModalClienteRapido()" data-tooltip="Nuevo cliente">
                        <i class="fas fa-plus" style="color:#3752e8"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label>Número de Radicado:</label>
                <input type="text" id="numero_radicado" name="numero_radicado" required>
            </div>
            <div class="form-group">
                <label>Tipo de Proceso:</label>
                <select id="tipo_proceso_id" name="tipo_proceso_id" required>
                    <option value="">Seleccione tipo</option>
                </select>
            </div>
            <div class="form-group">
                <label>Descripción:</label>
                <textarea id="descripcion" name="descripcion" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Estado:</label>
                <select id="estado_proceso_id" name="estado_proceso_id" required>
                    <option value="">Seleccione estado</option>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha de Inicio:</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" required>
            </div>
            <div class="form-group">
                <label>Fecha de Vencimiento:</label>
                <input type="date" id="fecha_vencimiento" name="fecha_vencimiento">
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</div>

<!-- ══ MODAL VER PROCESO ══════════════════════════════════════ -->
<div id="modalVerProceso" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModalVer()">&times;</span>
        <h3>Detalles del Proceso</h3>
        <div id="detalleProceso"></div>
    </div>
</div>

<!-- ══ MODAL ANEXOS ══════════════════════════════════════════ -->
<div id="modalAnexos" class="modal">
    <div class="modal-content" style="width:80%;max-width:900px">
        <span class="close" onclick="cerrarModalAnexos()">&times;</span>
        <div style="margin-top:30px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
                <h3 style="margin:0">Administrar Anexos</h3>
            </div>
        </div>
        <div style="background:#f8f9fa;padding:20px;border-radius:8px;margin-bottom:25px">
            <h4 style="margin-top:0;margin-bottom:15px;color:#2c3e50">Subir nuevo archivo</h4>
            <form id="formAnexo" onsubmit="subirAnexo(event)" enctype="multipart/form-data"
                  style="display:flex;gap:15px;align-items:flex-end">
                <input type="hidden" id="anexoProcesoId" name="proceso_id">
                <div style="flex:1">
                    <label style="display:block;margin-bottom:5px;font-weight:bold;font-size:13px;color:#555">Seleccionar archivo:</label>
                    <input type="file" id="archivo" name="archivo" required
                           style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                </div>
                <button type="submit" class="btn btn-primary" style="padding:10px 20px">Subir Archivo</button>
            </form>
        </div>
        <h4 style="margin-bottom:15px;color:#2c3e50">Archivos subidos</h4>
        <table id="tablaAnexos" style="width:100%;border-collapse:collapse">
            <thead>
                <tr>
                    <th style="text-align:left;padding:12px;background:#3498db;color:white">Nombre del archivo</th>
                    <th style="text-align:left;padding:12px;background:#3498db;color:white">Tipo</th>
                    <th style="text-align:left;padding:12px;background:#3498db;color:white">Fecha subida</th>
                    <th style="text-align:center;padding:12px;background:#3498db;color:white">Acciones</th>
                </tr>
            </thead>
            <tbody id="listaAnexos"></tbody>
        </table>
    </div>
</div>

<!-- ══ MODAL ACTUACIONES (con Timeline) ══════════════════════ -->
<div id="modalActuaciones" class="modal">
    <div class="modal-content" style="width:80%;max-width:800px">
        <span class="close" onclick="cerrarModalActuaciones()">&times;</span>

        <div style="margin-top:30px">
            <div class="tl-header">
                <div>
                    <h3 style="margin:0">Actuaciones del Proceso</h3>
                    <div id="procesoInfo" style="margin-top:6px;font-size:13px;color:#7f8c8d"></div>
                </div>
                <div style="display:flex;align-items:center;gap:10px">
                    <span id="tlContador" class="tl-contador"></span>
                    <button class="btn btn-primary" onclick="sincronizarRama()" id="btnSincronizar">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                </div>
            </div>
        </div>

        <div class="timeline-wrap">
            <div id="timelineActuaciones"></div>
        </div>
    </div>
</div>

<!-- ══ MODAL CLIENTE RÁPIDO ══════════════════════════════════ -->
<div id="modalClienteRapido" class="modal">
    <div class="modal-content" style="max-width:500px">
        <span class="close" onclick="cerrarModalClienteRapido()">&times;</span>
        <h3>Nuevo Cliente</h3>
        <form id="formClienteRapido" onsubmit="guardarClienteRapido(event)">
            <div class="form-group"><label>Nombre:</label><input type="text" id="nombre_rapido" name="nombre" required></div>
            <div class="form-group"><label>Apellido:</label><input type="text" id="apellido_rapido" name="apellido" required></div>
            <div class="form-group"><label>Email:</label><input type="email" id="email_rapido" name="email"></div>
            <div class="form-group"><label>Teléfono:</label><input type="text" id="telefono_rapido" name="telefono" pattern="[0-9]{7,10}" maxlength="10"></div>
            <div class="form-group"><label>Dirección:</label><textarea id="direccion_rapido" name="direccion" rows="2"></textarea></div>
            <button type="submit" class="btn btn-primary">Guardar Cliente</button>
        </form>
    </div>
</div>

<!-- ══ SCRIPTS ════════════════════════════════════════════════ -->
<script>

// ── Actuaciones / Timeline ────────────────────────────────────
function verActuaciones(procesoId) {
    procesoActual = procesoId;
    fetchWithAuth(`/procesos_juridicos/backend/controllers/ProcesoController.php?action=get&id=${procesoId}`)
        .then(r => r.json())
        .then(p => {
            document.getElementById('procesoInfo').innerHTML =
                `<i class="fas fa-gavel"></i> <strong>${p.numero_radicado}</strong>
                 &nbsp;·&nbsp; ${p.nombre} ${p.apellido}`;
        });
    cargarTimeline(procesoId);
    document.getElementById('modalActuaciones').style.display = 'block';
}

function cargarTimeline(procesoId) {
    const contenedor = document.getElementById('timelineActuaciones');
    contenedor.innerHTML = '<div class="tl-empty"><i class="fas fa-spinner fa-spin"></i>Cargando...</div>';

    fetchWithAuth(`/procesos_juridicos/backend/controllers/ActuacionController.php?action=list&proceso_id=${procesoId}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('tlContador').textContent =
                data.length === 0 ? 'Sin actuaciones' : `${data.length} actuación${data.length !== 1 ? 'es' : ''}`;

            if (data.length === 0) {
                contenedor.innerHTML = `
                    <div class="tl-empty">
                        <i class="fas fa-folder-open"></i>
                        No hay actuaciones registradas para este proceso
                    </div>`;
                return;
            }

            // Ordenar de más reciente a más antigua
            data.sort((a, b) => new Date(b.fecha) - new Date(a.fecha));

            const hoy   = new Date();
            hoy.setHours(0,0,0,0);
            const ayer  = new Date(hoy); ayer.setDate(ayer.getDate() - 1);
            const items = data.map((a, idx) => {
                const fechaAct  = new Date(a.fecha);
                const esNueva   = fechaAct >= ayer;
                const fechaFmt  = fechaAct.toLocaleDateString('es-CO', {
                    weekday: 'short', year: 'numeric', month: 'short', day: 'numeric'
                });

                return `
                <div class="tl-item" style="animation-delay:${idx * 0.05}s">
                    <div class="tl-dot"></div>
                    <div class="tl-card">
                        <div class="tl-fecha">
                            <i class="fas fa-calendar-day"></i>
                            ${fechaFmt}
                            ${esNueva ? '<span class="tl-badge-nueva">nueva</span>' : ''}
                        </div>
                        <div class="tl-titulo">${a.actuacion}</div>
                        ${a.observaciones
                            ? `<div class="tl-obs"><i class="fas fa-comment-alt" style="margin-right:5px;color:#bdc3c7"></i>${a.observaciones}</div>`
                            : ''}
                    </div>
                </div>`;
            }).join('');

            contenedor.innerHTML = `<div class="timeline">${items}</div>`;
        });
}

function sincronizarRama() {
    const btn = document.getElementById('btnSincronizar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sincronizando...';

    const fd = new FormData();
    fd.append('action', 'sincronizar');
    fd.append('proceso_id', procesoActual);

    fetchWithAuth('/procesos_juridicos/backend/controllers/SincronizarRamaController.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            alert(data.success ? data.message : 'Error: ' + data.message);
            cargarTimeline(procesoActual);
        })
        .catch(() => {
            alert('Sincronización completada');
            cargarTimeline(procesoActual);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Actualizar';
        });
}

function cerrarModalActuaciones() {
    document.getElementById('modalActuaciones').style.display = 'none';
}

// ── Procesos ──────────────────────────────────────────────────
function buscarProcesos() {
    terminoBusqueda = document.getElementById('buscarProcesos').value.trim();
    paginaActualProcesos = 1;
    cargarProcesos(1, terminoBusqueda);
}

function limpiarBusqueda() {
    document.getElementById('buscarProcesos').value = '';
    terminoBusqueda = '';
    cargarProcesos(1, '');
}

function cargarProcesos(pagina = 1, buscar = '') {
    let url = `/procesos_juridicos/backend/controllers/ProcesoController.php?action=list&pagina=${pagina}`;
    if (buscar) url += `&buscar=${encodeURIComponent(buscar)}`;

    fetchWithAuth(url).then(r => r.json()).then(result => {
        paginaActualProcesos = result.pagina;
        totalPaginasProcesos = result.total_paginas;

        const tbody = document.querySelector('#tablaProcesos tbody');
        tbody.innerHTML = '';

        if (result.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:30px">No se encontraron procesos</td></tr>';
            document.getElementById('paginacionProcesos').innerHTML = '';
            return;
        }

        result.data.forEach(p => {
            tbody.innerHTML += `
                <tr>
                    <td>${p.id}</td>
                    <td>${p.numero_radicado}</td>
                    <td>${p.nombre} ${p.apellido}</td>
                    <td>${p.tipo_proceso_nombre || p.tipo_proceso || '—'}</td>
                    <td>${p.estado_proceso_nombre || p.estado || '—'}</td>
                    <td>${p.fecha_vencimiento || 'N/A'}</td>
                    <td>
                        <button class="btn-icon" onclick="verProceso(${p.id})" data-tooltip="Ver detalles"><i class="fas fa-eye"></i></button>
                        <button class="btn-icon" onclick="verActuaciones(${p.id})" data-tooltip="Timeline"><i class="fas fa-stream"></i></button>
                        <button class="btn-icon" onclick="editarProceso(${p.id})" data-tooltip="Editar"><i class="fas fa-edit"></i></button>
                        <button class="btn-icon" onclick="abrirModalAnexos(${p.id})" data-tooltip="Anexos"><i class="fas fa-paperclip"></i></button>
                        <button class="btn-icon" onclick="eliminarProceso(${p.id})" data-tooltip="Eliminar"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
        });
        renderPaginacionProcesos();
    });
}

function cargarClientesSelect() {
    const sel = document.getElementById('cliente_id');
    const valorActual = sel.value;
    return fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php?action=getClientes')
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">Seleccione un cliente</option>';
            data.forEach(c => sel.innerHTML += `<option value="${c.id}">${c.nombre} ${c.apellido}</option>`);
            if (valorActual) sel.value = valorActual;
        });
}

function abrirModalProceso() {
    Promise.all([cargarClientesSelect(), cargarTiposProceso(), cargarEstadosProceso()]).then(() => {
        document.getElementById('formProceso').reset();
        document.getElementById('procesoId').value = '';
        document.getElementById('modalProcesoTitle').textContent = 'Nuevo Proceso';
        document.getElementById('modalProceso').style.display = 'block';
    });
}

function cerrarModalProceso() { document.getElementById('modalProceso').style.display = 'none'; }

function guardarProceso(event) {
    event.preventDefault();
    const fi = document.getElementById('fecha_inicio').value;
    const fv = document.getElementById('fecha_vencimiento').value;
    if (fv && new Date(fv) < new Date(fi)) {
        alert('La fecha de vencimiento no puede ser menor a la fecha de inicio');
        return;
    }
    const fd = new FormData(document.getElementById('formProceso'));
    fd.append('action', document.getElementById('procesoId').value ? 'update' : 'create');
    fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => { if (data.success) { cerrarModalProceso(); cargarProcesos(1, terminoBusqueda); } });
}

function editarProceso(id) {
    Promise.all([cargarClientesSelect(), cargarTiposProceso(), cargarEstadosProceso()])
        .then(() => fetchWithAuth(`/procesos_juridicos/backend/controllers/ProcesoController.php?action=get&id=${id}`))
        .then(r => r.json())
        .then(p => {
            document.getElementById('procesoId').value          = p.id;
            document.getElementById('cliente_id').value         = p.cliente_id;
            document.getElementById('numero_radicado').value    = p.numero_radicado;
            document.getElementById('tipo_proceso_id').value    = p.tipo_proceso_id;
            document.getElementById('estado_proceso_id').value  = p.estado_proceso_id;
            document.getElementById('descripcion').value        = p.descripcion || '';
            document.getElementById('fecha_inicio').value       = p.fecha_inicio;
            document.getElementById('fecha_vencimiento').value  = p.fecha_vencimiento || '';
            document.getElementById('modalProcesoTitle').textContent = 'Editar Proceso';
            document.getElementById('modalProceso').style.display = 'block';
        });
}

function verProceso(id) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/ProcesoController.php?action=get&id=${id}`)
        .then(r => r.json())
        .then(p => {
            document.getElementById('detalleProceso').innerHTML = `
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:15px;padding:10px">
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">ID Proceso</strong>
                        <span style="font-size:16px">${p.id}</span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Radicado</strong>
                        <span style="font-size:16px;font-weight:bold;color:#3498db">${p.numero_radicado}</span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px;grid-column:span 2">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Cliente</strong>
                        <span style="font-size:16px">${p.nombre} ${p.apellido}</span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Tipo de Proceso</strong>
                        <span style="font-size:16px">${p.tipo_proceso_nombre || '—'}</span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Estado</strong>
                        <span style="font-size:14px;padding:4px 10px;border-radius:4px;background:${p.estado_color||'#3498db'};color:white;display:inline-block">
                            ${p.estado_proceso_nombre || '—'}
                        </span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Fecha Inicio</strong>
                        <span style="font-size:16px">${p.fecha_inicio}</span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Fecha Vencimiento</strong>
                        <span style="font-size:16px;${p.fecha_vencimiento && new Date(p.fecha_vencimiento)<new Date()?'color:#e74c3c;font-weight:bold':''}">
                            ${p.fecha_vencimiento || 'N/A'}
                        </span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px;grid-column:span 2">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Descripción</strong>
                        <span style="font-size:14px;line-height:1.5">${p.descripcion || 'Sin descripción'}</span>
                    </div>
                </div>`;
            document.getElementById('modalVerProceso').style.display = 'block';
        });
}

function cerrarModalVer() { document.getElementById('modalVerProceso').style.display = 'none'; }

function eliminarProceso(id) {
    if (confirm('¿Está seguro de eliminar este proceso?')) {
        const fd = new FormData();
        fd.append('action','delete'); fd.append('id', id);
        fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php', { method:'POST', body:fd })
            .then(r => r.json())
            .then(data => { if (data.success) cargarProcesos(1, terminoBusqueda); });
    }
}

// ── Anexos ────────────────────────────────────────────────────
function abrirModalAnexos(id) {
    document.getElementById('anexoProcesoId').value = id;
    document.getElementById('modalAnexos').style.display = 'block';
    cargarAnexos(id);
}

function cerrarModalAnexos() { document.getElementById('modalAnexos').style.display = 'none'; }

function cargarAnexos(procesoId) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/AnexoController.php?action=list&proceso_id=${procesoId}`)
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('listaAnexos');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:30px;color:#7f8c8d">No hay archivos subidos</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(a => {
                const fecha = new Date(a.fecha_subida).toLocaleDateString('es-CO',
                    {year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hour12:false}).replace(',',' ');
                return `<tr style="border-bottom:1px solid #eee">
                    <td style="padding:12px"><i class="fas fa-file" style="color:#3498db;margin-right:8px"></i>${a.nombre_archivo}</td>
                    <td style="padding:12px">${a.tipo_archivo||'Desconocido'}</td>
                    <td style="padding:12px">${fecha}</td>
                    <td style="padding:12px;text-align:center">
                        <button class="btn-icon" onclick="eliminarAnexo(${a.id},${procesoId})" data-tooltip="Eliminar">
                            <i class="fas fa-trash" style="color:#e74c3c"></i>
                        </button>
                    </td>
                </tr>`;
            }).join('');
        });
}

function subirAnexo(event) {
    event.preventDefault();
    const pid = document.getElementById('anexoProcesoId').value;
    const fd  = new FormData(document.getElementById('formAnexo'));
    fd.append('action','upload');
    fetchWithAuth('/procesos_juridicos/backend/controllers/AnexoController.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => { if (data.success) { document.getElementById('formAnexo').reset(); cargarAnexos(pid); } });
}

function eliminarAnexo(id, procesoId) {
    if (confirm('¿Eliminar este archivo?')) {
        const fd = new FormData();
        fd.append('action','delete'); fd.append('id',id); fd.append('proceso_id',procesoId);
        fetchWithAuth('/procesos_juridicos/backend/controllers/AnexoController.php', { method:'POST', body:fd })
            .then(r => r.json())
            .then(data => { if (data.success) cargarAnexos(procesoId); });
    }
}

// ── Cliente rápido ────────────────────────────────────────────
function abrirModalClienteRapido() {
    document.getElementById('formClienteRapido').reset();
    document.getElementById('modalClienteRapido').style.display = 'block';
}

function cerrarModalClienteRapido() { document.getElementById('modalClienteRapido').style.display = 'none'; }

function guardarClienteRapido(event) {
    event.preventDefault();
    const fd = new FormData(document.getElementById('formClienteRapido'));
    fd.append('action','create');
    fetchWithAuth('/procesos_juridicos/backend/controllers/ClienteController.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) { cerrarModalClienteRapido(); cargarClientesSelect(); alert('Cliente creado exitosamente'); }
            else alert('Error al crear el cliente');
        });
}

// ── Selects ───────────────────────────────────────────────────
function cargarTiposProceso() {
    return fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php?action=getTipos')
        .then(r => r.json())
        .then(data => {
            const sel = document.getElementById('tipo_proceso_id');
            sel.innerHTML = '<option value="">Seleccione tipo</option>';
            data.forEach(t => sel.innerHTML += `<option value="${t.id}">${t.nombre}</option>`);
        });
}

function cargarEstadosProceso() {
    return fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php?action=getEstados')
        .then(r => r.json())
        .then(data => {
            const sel = document.getElementById('estado_proceso_id');
            sel.innerHTML = '<option value="">Seleccione estado</option>';
            data.forEach(e => sel.innerHTML += `<option value="${e.id}">${e.nombre}</option>`);
        });
}

// ── Paginación ────────────────────────────────────────────────
function renderPaginacionProcesos() {
    document.getElementById('paginacionProcesos').innerHTML = `
        <button class="pagination-btn" onclick="cambiarPaginaProcesos(${paginaActualProcesos-1})" ${paginaActualProcesos<=1?'disabled':''}>
            <i class="fas fa-chevron-left"></i>
        </button>
        <span class="pagination-info">Página ${paginaActualProcesos} de ${totalPaginasProcesos}</span>
        <button class="pagination-btn" onclick="cambiarPaginaProcesos(${paginaActualProcesos+1})" ${paginaActualProcesos>=totalPaginasProcesos?'disabled':''}>
            <i class="fas fa-chevron-right"></i>
        </button>`;
}

function cambiarPaginaProcesos(p) {
    if (p >= 1 && p <= totalPaginasProcesos) cargarProcesos(p, terminoBusqueda);
}

// ── Búsqueda con debounce ─────────────────────────────────────
let timeoutBusqueda;
document.getElementById('buscarProcesos').addEventListener('input', function() {
    clearTimeout(timeoutBusqueda);
    timeoutBusqueda = setTimeout(buscarProcesos, 500);
});
document.getElementById('buscarProcesos').addEventListener('keyup', e => {
    if (e.key === 'Enter') buscarProcesos();
});

// ── Init ──────────────────────────────────────────────────────
cargarProcesos(1, '');
</script>