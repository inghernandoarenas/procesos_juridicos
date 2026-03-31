<style>
/* ── Resumen ejecutivo ─────────────────────────── */
.resumen-kpis {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
    margin-bottom: 24px;
}
.kpi-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 16px 12px;
    text-align: center;
    border-top: 4px solid #3498db;
    transition: transform .2s;
}
.kpi-card:hover { transform: translateY(-3px); }
.kpi-card.verde  { border-top-color: #27ae60; }
.kpi-card.naranja{ border-top-color: #f39c12; }
.kpi-card.gris   { border-top-color: #95a5a6; }
.kpi-card.rojo   { border-top-color: #e74c3c; }
.kpi-card.azul   { border-top-color: #3498db; }

.kpi-numero {
    font-size: 32px;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1;
    margin-bottom: 6px;
}
.kpi-label {
    font-size: 11px;
    color: #7f8c8d;
    text-transform: uppercase;
    letter-spacing: .5px;
}

/* ── Mini timeline en resumen ──────────────────── */
.mini-tl { padding: 0; }
.mini-tl-item {
    display: flex;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
    align-items: flex-start;
}
.mini-tl-item:last-child { border-bottom: none; }
.mini-tl-fecha {
    font-size: 11px;
    color: #3498db;
    font-weight: 600;
    white-space: nowrap;
    min-width: 80px;
}
.mini-tl-body { flex: 1; }
.mini-tl-radicado {
    font-size: 10px;
    background: #eaf4fd;
    color: #2980b9;
    padding: 1px 6px;
    border-radius: 10px;
    display: inline-block;
    margin-bottom: 3px;
}
.mini-tl-act { font-size: 13px; color: #2c3e50; font-weight: 500; }
.mini-tl-obs { font-size: 11px; color: #95a5a6; margin-top: 2px; }

/* ── Lista procesos en resumen ─────────────────── */
.proc-lista { list-style: none; padding: 0; margin: 0; }
.proc-lista li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 9px 12px;
    margin-bottom: 6px;
    background: #f8f9fa;
    border-radius: 6px;
    font-size: 13px;
}
.proc-lista li:hover { background: #eef2f7; }
.proc-estado {
    font-size: 11px;
    padding: 2px 10px;
    border-radius: 20px;
    color: white;
    font-weight: 600;
}
.proc-vence {
    font-size: 11px;
    color: #95a5a6;
}
</style>

<script>
let paginaActualClientes = 1;
let totalPaginasClientes  = 1;

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

<!-- ── Cabecera ─────────────────────────────────────────────── -->
<div class="page-header">
    <h2>Gestión de Clientes</h2>
    <button class="btn btn-primary" onclick="abrirModalCliente()">Nuevo Cliente</button>
</div>

<!-- ── Tabla ───────────────────────────────────────────────── -->
<table id="tablaClientes">
    <thead>
        <tr>
            <th>ID</th><th>Nombre</th><th>Apellido</th>
            <th>Email</th><th>Teléfono</th><th>Acciones</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>
<div id="paginacionClientes" class="pagination-container"
     style="margin-top:20px;display:flex;justify-content:center;align-items:center;gap:10px"></div>

<!-- ── Modal Crear / Editar ────────────────────────────────── -->
<div id="modalCliente" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModalCliente()">&times;</span>
        <h3 id="modalClienteTitle">Nuevo Cliente</h3>
        <form id="formCliente" onsubmit="guardarCliente(event)">
            <input type="hidden" id="clienteId" name="id">
            <div class="form-group"><label>Nombre:</label><input type="text" id="nombre" name="nombre" required></div>
            <div class="form-group"><label>Apellido:</label><input type="text" id="apellido" name="apellido" required></div>
            <div class="form-group"><label>Email:</label><input type="email" id="email" name="email"></div>
            <div class="form-group">
                <label>Teléfono:</label>
                <input type="text" id="telefono" name="telefono"
                       pattern="[0-9]{7,10}" title="Ingrese un número de 7 a 10 dígitos"
                       oninput="this.value=this.value.replace(/[^0-9]/g,'')" maxlength="10">
            </div>
            <div class="form-group"><label>Dirección:</label><textarea id="direccion" name="direccion" rows="3"></textarea></div>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</div>

<!-- ── Modal Ver cliente ────────────────────────────────────── -->
<div id="modalVerCliente" class="modal">
    <div class="modal-content" style="max-width:600px">
        <span class="close" onclick="cerrarModalVer()">&times;</span>
        <h3 style="margin-bottom:20px">Detalles del Cliente</h3>
        <div id="detalleCliente"></div>
    </div>
</div>

<!-- ── Modal Resumen Ejecutivo ──────────────────────────────── -->
<div id="modalResumen" class="modal">
    <div class="modal-content" style="width:85%;max-width:900px">
        <span class="close" onclick="cerrarModalResumen()">&times;</span>

        <div style="margin-top:30px">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
                <div style="width:44px;height:44px;background:#3498db;border-radius:50%;
                            display:flex;align-items:center;justify-content:center">
                    <i class="fas fa-user" style="color:white;font-size:18px"></i>
                </div>
                <div>
                    <h3 id="resumenNombre" style="margin:0;font-size:20px"></h3>
                    <p id="resumenSub" style="margin:0;color:#7f8c8d;font-size:13px"></p>
                </div>
            </div>

            <!-- KPIs -->
            <div class="resumen-kpis" id="resumenKpis"></div>

            <!-- Dos columnas: procesos + actuaciones -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

                <div>
                    <h4 style="color:#2c3e50;margin-bottom:12px;font-size:14px;
                               text-transform:uppercase;letter-spacing:.5px">
                        <i class="fas fa-gavel" style="color:#3498db;margin-right:6px"></i>
                        Procesos
                    </h4>
                    <ul class="proc-lista" id="resumenProcesos"></ul>
                </div>

                <div>
                    <h4 style="color:#2c3e50;margin-bottom:12px;font-size:14px;
                               text-transform:uppercase;letter-spacing:.5px">
                        <i class="fas fa-stream" style="color:#3498db;margin-right:6px"></i>
                        Últimas Actuaciones
                    </h4>
                    <div class="mini-tl" id="resumenActuaciones"></div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ── Scripts ──────────────────────────────────────────────── -->
<script>

// ── Tabla clientes ────────────────────────────────────────────
function cargarClientes(pagina = 1) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/ClienteController.php?action=list&pagina=${pagina}`)
        .then(r => r.json())
        .then(result => {
            paginaActualClientes = result.pagina;
            totalPaginasClientes = result.total_paginas;

            const tbody = document.querySelector('#tablaClientes tbody');
            tbody.innerHTML = '';
            result.data.forEach(c => {
                tbody.innerHTML += `
                    <tr>
                        <td>${c.id}</td>
                        <td>${c.nombre}</td>
                        <td>${c.apellido}</td>
                        <td>${c.email || ''}</td>
                        <td>${c.telefono || ''}</td>
                        <td>
                            <button class="btn-icon" onclick="verCliente(${c.id})" data-tooltip="Ver"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon" onclick="verResumen(${c.id},'${c.nombre} ${c.apellido}')" data-tooltip="Resumen ejecutivo"><i class="fas fa-chart-bar"></i></button>
                            <button class="btn-icon" onclick="editarCliente(${c.id})" data-tooltip="Editar"><i class="fas fa-edit"></i></button>
                            <button class="btn-icon" onclick="eliminarCliente(${c.id})" data-tooltip="Eliminar"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>`;
            });
            renderPaginacionClientes();
        });
}

// ── CRUD clientes ─────────────────────────────────────────────
function abrirModalCliente() {
    document.getElementById('formCliente').reset();
    document.getElementById('clienteId').value = '';
    document.getElementById('modalClienteTitle').textContent = 'Nuevo Cliente';
    document.getElementById('modalCliente').style.display = 'block';
}
function cerrarModalCliente() { document.getElementById('modalCliente').style.display = 'none'; }

function guardarCliente(event) {
    event.preventDefault();
    const fd = new FormData(document.getElementById('formCliente'));
    fd.append('action', document.getElementById('clienteId').value ? 'update' : 'create');
    fetchWithAuth('/procesos_juridicos/backend/controllers/ClienteController.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => { if (data.success) { cerrarModalCliente(); cargarClientes(1); } });
}

function editarCliente(id) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/ClienteController.php?action=get&id=${id}`)
        .then(r => r.json())
        .then(c => {
            document.getElementById('clienteId').value  = c.id;
            document.getElementById('nombre').value     = c.nombre;
            document.getElementById('apellido').value   = c.apellido;
            document.getElementById('email').value      = c.email     || '';
            document.getElementById('telefono').value   = c.telefono  || '';
            document.getElementById('direccion').value  = c.direccion || '';
            document.getElementById('modalClienteTitle').textContent = 'Editar Cliente';
            document.getElementById('modalCliente').style.display = 'block';
        });
}

function verCliente(id) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/ClienteController.php?action=get&id=${id}`)
        .then(r => r.json())
        .then(c => {
            document.getElementById('detalleCliente').innerHTML = `
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:15px;padding:10px">
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">ID Cliente</strong>
                        <span style="font-size:16px">${c.id}</span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px;grid-column:span 2">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Nombre Completo</strong>
                        <span style="font-size:18px;font-weight:bold;color:#3498db">${c.nombre} ${c.apellido}</span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Email</strong>
                        <span style="font-size:14px"><i class="fas fa-envelope" style="color:#3498db;margin-right:5px"></i>${c.email||'N/A'}</span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Teléfono</strong>
                        <span style="font-size:14px"><i class="fas fa-phone" style="color:#27ae60;margin-right:5px"></i>${c.telefono||'N/A'}</span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px;grid-column:span 2">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Dirección</strong>
                        <span style="font-size:14px"><i class="fas fa-map-marker-alt" style="color:#e74c3c;margin-right:5px"></i>${c.direccion||'N/A'}</span>
                    </div>
                    <div style="grid-column:span 2;text-align:right;color:#7f8c8d;font-size:12px">
                        <i class="fas fa-calendar-alt"></i> Cliente desde: ${new Date(c.created_at).toLocaleDateString('es-CO')}
                    </div>
                </div>`;
            document.getElementById('modalVerCliente').style.display = 'block';
        });
}

function cerrarModalVer() { document.getElementById('modalVerCliente').style.display = 'none'; }

function eliminarCliente(id) {
    if (confirm('¿Está seguro de eliminar este cliente?')) {
        const fd = new FormData();
        fd.append('action','delete'); fd.append('id', id);
        fetchWithAuth('/procesos_juridicos/backend/controllers/ClienteController.php', { method:'POST', body:fd })
            .then(r => r.json())
            .then(data => { if (data.success) cargarClientes(1); });
    }
}

// ── Resumen Ejecutivo ─────────────────────────────────────────
function verResumen(id, nombre) {
    // Mostrar modal con spinner mientras carga
    document.getElementById('resumenNombre').textContent = nombre;
    document.getElementById('resumenSub').textContent    = 'Cargando información...';
    document.getElementById('resumenKpis').innerHTML     = '<p style="color:#aaa;text-align:center;padding:20px"><i class="fas fa-spinner fa-spin"></i> Cargando...</p>';
    document.getElementById('resumenProcesos').innerHTML  = '';
    document.getElementById('resumenActuaciones').innerHTML = '';
    document.getElementById('modalResumen').style.display = 'block';

    fetchWithAuth(`/procesos_juridicos/backend/controllers/ClienteController.php?action=resumen&id=${id}`)
        .then(r => r.json())
        .then(data => {
            const c = data.conteos;

            // Subtítulo
            document.getElementById('resumenSub').textContent =
                `${c.total_procesos} proceso${c.total_procesos != 1 ? 's' : ''} en total`;

            // KPIs
            document.getElementById('resumenKpis').innerHTML = `
                <div class="kpi-card azul">
                    <div class="kpi-numero">${c.total_procesos || 0}</div>
                    <div class="kpi-label">Total</div>
                </div>
                <div class="kpi-card verde">
                    <div class="kpi-numero">${c.activos || 0}</div>
                    <div class="kpi-label">Activos</div>
                </div>
                <div class="kpi-card naranja">
                    <div class="kpi-numero">${c.en_espera || 0}</div>
                    <div class="kpi-label">En espera</div>
                </div>
                <div class="kpi-card gris">
                    <div class="kpi-numero">${c.finalizados || 0}</div>
                    <div class="kpi-label">Finalizados</div>
                </div>
                <div class="kpi-card rojo">
                    <div class="kpi-numero">${c.proximos_vencer || 0}</div>
                    <div class="kpi-label">Próx. a vencer</div>
                </div>`;

            // Lista de procesos
            if (data.procesos.length === 0) {
                document.getElementById('resumenProcesos').innerHTML =
                    '<li style="color:#aaa;font-size:13px">Sin procesos registrados</li>';
            } else {
                document.getElementById('resumenProcesos').innerHTML = data.procesos.map(p => {
                    const color = p.estado_color || '#95a5a6';
                    const vence = p.fecha_vencimiento
                        ? `<span class="proc-vence">Vence: ${p.fecha_vencimiento}</span>`
                        : '';
                    return `<li>
                        <div>
                            <div style="font-weight:600;color:#2c3e50">${p.numero_radicado}</div>
                            <div style="font-size:11px;color:#95a5a6">${p.tipo_proceso || '—'}</div>
                            ${vence}
                        </div>
                        <span class="proc-estado" style="background:${color}">${p.estado || '—'}</span>
                    </li>`;
                }).join('');
            }

            // Últimas actuaciones
            if (data.actuaciones.length === 0) {
                document.getElementById('resumenActuaciones').innerHTML =
                    '<p style="color:#aaa;font-size:13px">Sin actuaciones registradas</p>';
            } else {
                document.getElementById('resumenActuaciones').innerHTML = data.actuaciones.map(a => {
                    const fecha = new Date(a.fecha).toLocaleDateString('es-CO',
                        {day:'2-digit', month:'short', year:'numeric'});
                    return `<div class="mini-tl-item">
                        <div class="mini-tl-fecha">${fecha}</div>
                        <div class="mini-tl-body">
                            <span class="mini-tl-radicado">${a.numero_radicado}</span>
                            <div class="mini-tl-act">${a.actuacion}</div>
                            ${a.observaciones ? `<div class="mini-tl-obs">${a.observaciones}</div>` : ''}
                        </div>
                    </div>`;
                }).join('');
            }
        });
}

function cerrarModalResumen() { document.getElementById('modalResumen').style.display = 'none'; }

// ── Paginación ────────────────────────────────────────────────
function renderPaginacionClientes() {
    document.getElementById('paginacionClientes').innerHTML = `
        <button class="pagination-btn" onclick="cambiarPaginaClientes(${paginaActualClientes-1})" ${paginaActualClientes<=1?'disabled':''}>
            <i class="fas fa-chevron-left"></i>
        </button>
        <span class="pagination-info">Página ${paginaActualClientes} de ${totalPaginasClientes}</span>
        <button class="pagination-btn" onclick="cambiarPaginaClientes(${paginaActualClientes+1})" ${paginaActualClientes>=totalPaginasClientes?'disabled':''}>
            <i class="fas fa-chevron-right"></i>
        </button>`;
}

function cambiarPaginaClientes(p) {
    if (p >= 1 && p <= totalPaginasClientes) cargarClientes(p);
}

cargarClientes(1);
</script>