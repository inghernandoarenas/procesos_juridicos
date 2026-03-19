<script>

// Para procesos
let paginaActualProcesos = 1;
let totalPaginasProcesos = 1;

// Para clientes
let paginaActualClientes = 1;
let totalPaginasClientes = 1;

// Función para obtener headers con token
function getHeaders() {
    const token = localStorage.getItem('token');
    return {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/x-www-form-urlencoded'
    };
}

// Función para hacer fetch con token
function fetchWithAuth(url, options = {}) {
    const token = localStorage.getItem('token');
    
    if (!token) {
        window.location.href = '/procesos_juridicos/frontend/login.php';
        return Promise.reject('No token');
    }
    
    options.headers = {
        ...options.headers,
        'Authorization': 'Bearer ' + token
    };
    
    return fetch(url, options)
        .then(response => {
            if (response.status === 401) {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                window.location.href = '/procesos_juridicos/frontend/login.php';
                return Promise.reject('Unauthorized');
            }
            return response;
        });
}
</script>

<div class="page-header">
    <h2>Gestión de Procesos</h2>
    <button class="btn btn-primary" onclick="abrirModalProceso()">Nuevo Proceso</button>
</div>

<!-- Campo de búsqueda -->
<div style="margin-bottom: 20px; display: flex; gap: 10px; max-width: 500px;">
    <div style="flex: 1; position: relative;">
        <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #95a5a6;"></i>
        <input type="text" id="buscarProcesos" placeholder="Buscar por radicado, cliente, tipo o descripción..." 
               style="width: 100%; padding: 12px 12px 12px 40px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
    </div>
    <button class="btn btn-primary" onclick="buscarProcesos()" style="padding: 0 25px;">
        <i class="fas fa-search"></i> Buscar
    </button>
    <button class="btn btn-secondary" onclick="limpiarBusqueda()" style="padding: 0 20px; background: #95a5a6;">
        <i class="fas fa-times"></i> Limpiar
    </button>
</div>

<table id="tablaProcesos">
    <thead>
        <tr>
            <th>ID</th>
            <th>Radicado</th>
            <th>Cliente</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Vencimiento</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>
<!-- Paginación Procesos -->
<div id="paginacionProcesos" class="pagination-container" style="margin-top: 20px; display: flex; justify-content: center; align-items: center; gap: 10px;"></div>

<div id="modalProceso" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModalProceso()">&times;</span>
        <h3 id="modalProcesoTitle">Nuevo Proceso</h3>
        <form id="formProceso" onsubmit="guardarProceso(event)">
            <input type="hidden" id="procesoId" name="id">
            
            <div class="form-group">
                <label>Cliente:</label>
                <select id="cliente_id" name="cliente_id" required>
                    <option value="">Seleccione un cliente</option>
                </select>
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

<div id="modalVerProceso" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModalVer()">&times;</span>
        <h3>Detalles del Proceso</h3>
        <div id="detalleProceso"></div>
    </div>
</div>

<!-- Modal Anexos -->
<div id="modalAnexos" class="modal">
    <div class="modal-content" style="width: 80%; max-width: 900px;">
        <span class="close" onclick="cerrarModalAnexos()">&times;</span>
        
        <div style="margin-top: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Administrar Anexos</h3>
            </div>
        </div>
        
        <!-- Formulario de subida -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
            <h4 style="margin-top: 0; margin-bottom: 15px; color: #2c3e50;">Subir nuevo archivo</h4>
            <form id="formAnexo" onsubmit="subirAnexo(event)" enctype="multipart/form-data" style="display: flex; gap: 15px; align-items: flex-end;">
                <input type="hidden" id="anexoProcesoId" name="proceso_id">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 13px; color: #555;">Seleccionar archivo:</label>
                    <input type="file" id="archivo" name="archivo" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Subir Archivo</button>
            </form>
        </div>
        
        <!-- Lista de archivos en tabla -->
        <h4 style="margin-bottom: 15px; color: #2c3e50;">Archivos subidos</h4>
        <table id="tablaAnexos" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="text-align: left; padding: 12px; background: #3498db; color: white;">Nombre del archivo</th>
                    <th style="text-align: left; padding: 12px; background: #3498db; color: white;">Tipo</th>
                    <th style="text-align: left; padding: 12px; background: #3498db; color: white;">Fecha subida</th>
                    <th style="text-align: center; padding: 12px; background: #3498db; color: white;">Acciones</th>
                </tr>
            </thead>
            <tbody id="listaAnexos">
                <!-- Se llena vía JavaScript -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Actuaciones -->
<div id="modalActuaciones" class="modal">
    <div class="modal-content" style="width: 80%; max-width: 1000px;">
        <span class="close" onclick="cerrarModalActuaciones()">&times;</span>
        
        <!-- Título y botón debajo de la X, con margen superior -->
        <div style="margin-top: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0;">Actuaciones del Proceso</h3>
                <button class="btn btn-primary" onclick="sincronizarRama()" id="btnSincronizar">Actualizar</button>
            </div>
        </div>
        
        <div id="procesoInfo" style="margin-bottom: 15px;"></div>
        
        <table id="tablaActuaciones">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Actuación</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script>
// Variable global para el ID del proceso actual
let procesoActual = 0;

// ========== FUNCIONES DE ACTUACIONES ==========
function verActuaciones(procesoId) {
    procesoActual = procesoId;
    
    // Primero obtenemos datos del proceso
    fetchWithAuth(`/procesos_juridicos/backend/controllers/ProcesoController.php?action=get&id=${procesoId}`)
        .then(response => response.json())
        .then(proceso => {
            document.getElementById('procesoInfo').innerHTML = `
                <p><strong>Radicado:</strong> ${proceso.numero_radicado} | 
                <strong>Cliente:</strong> ${proceso.nombre} ${proceso.apellido}</p>
                <hr>
            `;
        });
    
    cargarActuaciones(procesoId);
    document.getElementById('modalActuaciones').style.display = 'block';
}

function cargarActuaciones(procesoId) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/ActuacionController.php?action=list&proceso_id=${procesoId}`)
        .then(response => response.json())
        .then(data => {
            let tbody = document.querySelector('#tablaActuaciones tbody');
            tbody.innerHTML = '';
            
            if(data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" style="text-align: center;">No hay actuaciones registradas</td></tr>';
            } else {
                data.forEach(a => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${a.fecha}</td>
                            <td>${a.actuacion}</td>
                            <td>${a.observaciones || ''}</td>
                        </tr>
                    `;
                });
            }
        });
}

function sincronizarRama() {
    let btn = document.getElementById('btnSincronizar');
    btn.disabled = true;
    btn.innerHTML = '⏳ Sincronizando...';
    
    let formData = new FormData();
    formData.append('action', 'sincronizar');
    formData.append('proceso_id', procesoActual);
    
    fetchWithAuth('/procesos_juridicos/backend/controllers/SincronizarRamaController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert(data.message);
            cargarActuaciones(procesoActual);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error detallado:', error);
        alert('Error: ' + error.message);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Actualizar';
    });
}

function cerrarModalActuaciones() {
    document.getElementById('modalActuaciones').style.display = 'none';
}

// ========== FUNCIONES DE PROCESOS ==========

// Variable para guardar el término de búsqueda actual
let terminoBusqueda = '';

function buscarProcesos() {
    const termino = document.getElementById('buscarProcesos').value.trim();
    terminoBusqueda = termino;
    paginaActualProcesos = 1; // Resetear a primera página
    cargarProcesos(1, termino);
}

function limpiarBusqueda() {
    document.getElementById('buscarProcesos').value = '';
    terminoBusqueda = '';
    paginaActualProcesos = 1;
    cargarProcesos(1, '');
}

// funcion cargarProcesos con paginado
function cargarProcesos(pagina = 1, buscar = '') {
    let url = `/procesos_juridicos/backend/controllers/ProcesoController.php?action=list&pagina=${pagina}`;
    if (buscar) {
        url += `&buscar=${encodeURIComponent(buscar)}`;
    }
    
    fetchWithAuth(url)
        .then(response => response.json())
        .then(result => {
            // Guardar datos de paginación
            paginaActualProcesos = result.pagina;
            totalPaginasProcesos = result.total_paginas;
            
            // Renderizar tabla
            let tbody = document.querySelector('#tablaProcesos tbody');
            tbody.innerHTML = '';
            
            if (result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 30px;">No se encontraron procesos</td></tr>';
                document.getElementById('paginacionProcesos').innerHTML = '';
                return;
            }
            
            result.data.forEach(p => {
                tbody.innerHTML += `
                    <tr>
                        <td>${p.id}</td>
                        <td>${p.numero_radicado}</td>
                        <td>${p.nombre} ${p.apellido}</td>
                        <td>${p.tipo_proceso_nombre || p.tipo_proceso}</td>
                        <td>${p.estado_proceso_nombre || p.estado}</td>
                        <td>${p.fecha_vencimiento || 'N/A'}</td>
                        <td>
                            <button class="btn-icon" onclick="verProceso(${p.id})" data-tooltip="Ver detalles"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon" onclick="verActuaciones(${p.id})" data-tooltip="Actuaciones"><i class="fas fa-history"></i></button>
                            <button class="btn-icon" onclick="editarProceso(${p.id})" data-tooltip="Editar"><i class="fas fa-edit"></i></button>
                            <button class="btn-icon" onclick="abrirModalAnexos(${p.id})" data-tooltip="Anexos"><i class="fas fa-paperclip"></i></button>
                            <button class="btn-icon" onclick="eliminarProceso(${p.id})" data-tooltip="Eliminar"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
            
            // Renderizar controles de paginación
            renderPaginacionProcesos();
        });
}

function cargarClientesSelect() {
    return fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php?action=getClientes')
        .then(response => response.json())
        .then(data => {
            let select = document.getElementById('cliente_id');
            select.innerHTML = '<option value="">Seleccione un cliente</option>';
            data.forEach(c => {
                select.innerHTML += `<option value="${c.id}">${c.nombre} ${c.apellido}</option>`;
            });
        });
}

function abrirModalProceso() {
    Promise.all([
        cargarClientesSelect(),
        cargarTiposProceso(),
        cargarEstadosProceso()
    ]).then(() => {
        document.getElementById('formProceso').reset();
        document.getElementById('procesoId').value = '';
        document.getElementById('modalProcesoTitle').textContent = 'Nuevo Proceso';
        document.getElementById('modalProceso').style.display = 'block';
    });
}

function cerrarModalProceso() {
    document.getElementById('modalProceso').style.display = 'none';
}

function guardarProceso(event) {
    event.preventDefault();
    
    let formData = new FormData(document.getElementById('formProceso'));
    let id = document.getElementById('procesoId').value;
    formData.append('action', id ? 'update' : 'create');
    
    fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            cerrarModalProceso();
            cargarProcesos(1, terminoBusqueda);
        }
    });
}


function editarProceso(id) {
    // Primero cargar todos los selects en paralelo
    Promise.all([
        cargarClientesSelect(),
        cargarTiposProceso(),
        cargarEstadosProceso()
    ]).then(() => {
        // Cuando los selects estén listos, obtener datos del proceso
        return fetchWithAuth(`/procesos_juridicos/backend/controllers/ProcesoController.php?action=get&id=${id}`);
    })
    .then(response => response.json())
    .then(p => {
        document.getElementById('procesoId').value = p.id;
        document.getElementById('cliente_id').value = p.cliente_id;
        document.getElementById('numero_radicado').value = p.numero_radicado;
        document.getElementById('tipo_proceso_id').value = p.tipo_proceso_id;
        document.getElementById('estado_proceso_id').value = p.estado_proceso_id;
        document.getElementById('descripcion').value = p.descripcion || '';
        document.getElementById('fecha_inicio').value = p.fecha_inicio;
        document.getElementById('fecha_vencimiento').value = p.fecha_vencimiento || '';
        document.getElementById('modalProcesoTitle').textContent = 'Editar Proceso';
        document.getElementById('modalProceso').style.display = 'block';
    });
}


function verProceso(id) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/ProcesoController.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(p => {
            let html = `
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; padding: 10px;">
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase;">ID Proceso</strong>
                        <span style="font-size: 16px;">${p.id}</span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase;">Radicado</strong>
                        <span style="font-size: 16px; font-weight: bold; color: #3498db;">${p.numero_radicado}</span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; grid-column: span 2;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase;">Cliente</strong>
                        <span style="font-size: 16px;">${p.nombre} ${p.apellido}</span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase;">Tipo de Proceso</strong>
                        <span style="font-size: 16px;">${p.tipo_proceso_nombre || p.tipo_proceso}</span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase;">Estado</strong>
                        <span style="font-size: 16px; padding: 4px 8px; border-radius: 4px; 
                              background: ${p.estado_color || '#3498db'}; color: white; display: inline-block;">
                            ${p.estado_proceso_nombre || p.estado}
                        </span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase;">Fecha Inicio</strong>
                        <span style="font-size: 16px;">${p.fecha_inicio}</span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase;">Fecha Vencimiento</strong>
                        <span style="font-size: 16px; ${p.fecha_vencimiento && new Date(p.fecha_vencimiento) < new Date() ? 'color: #e74c3c; font-weight: bold;' : ''}">
                            ${p.fecha_vencimiento || 'N/A'}
                        </span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; grid-column: span 2;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase;">Descripción</strong>
                        <span style="font-size: 14px; line-height: 1.5;">${p.descripcion || 'Sin descripción'}</span>
                    </div>
                    
                    <div style="grid-column: span 2; text-align: right; margin-top: 10px; color: #7f8c8d; font-size: 12px;">
                        Creado: ${p.created_at || 'N/A'}
                    </div>
                </div>
            `;
            document.getElementById('detalleProceso').innerHTML = html;
            document.getElementById('modalVerProceso').style.display = 'block';
        });
}

function eliminarProceso(id) {
    if(confirm('¿Está seguro de eliminar este proceso?')) {
        let formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) cargarProcesos(1, terminoBusqueda);
        });
    }
}

function cerrarModalVer() {
    document.getElementById('modalVerProceso').style.display = 'none';
}

// ========== FUNCIONES DE ANEXOS ==========
function abrirModalAnexos(procesoId) {
    document.getElementById('anexoProcesoId').value = procesoId;
    document.getElementById('modalAnexos').style.display = 'block';
    cargarAnexos(procesoId);
}

function cerrarModalAnexos() {
    document.getElementById('modalAnexos').style.display = 'none';
}

function cargarAnexos(procesoId) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/AnexoController.php?action=list&proceso_id=${procesoId}`)
        .then(response => response.json())
        .then(data => {
            let tbody = document.getElementById('listaAnexos');
            
            if(data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 30px; color: #7f8c8d; background: #f9f9f9;">
                            No hay archivos subidos
                        </td>
                    </tr>
                `;
                return;
            }
            
            let html = '';
            data.forEach(a => {
                // Formatear fecha
                let fecha = new Date(a.fecha_subida).toLocaleDateString('es-CO', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                html += `
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;">
                            <i class="fas fa-file" style="color: #3498db; margin-right: 8px;"></i>
                            ${a.nombre_archivo}
                        </td>
                        <td style="padding: 12px;">${a.tipo_archivo || 'Desconocido'}</td>
                        <td style="padding: 12px;">${fecha}</td>
                        <td style="padding: 12px; text-align: center;">
                            <button class="btn-icon" onclick="eliminarAnexo(${a.id}, ${procesoId})" data-tooltip="Eliminar archivo">
                                <i class="fas fa-trash" style="color: #e74c3c;"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        });
}

function subirAnexo(event) {
    event.preventDefault();
    
    let procesoId = document.getElementById('anexoProcesoId').value;
    let formData = new FormData(document.getElementById('formAnexo'));
    formData.append('action', 'upload');
    
    fetchWithAuth('/procesos_juridicos/backend/controllers/AnexoController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            document.getElementById('formAnexo').reset();
            cargarAnexos(procesoId);
        }
    });
}

function eliminarAnexo(id, procesoId) {
    if(confirm('¿Eliminar este archivo?')) {
        let formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        formData.append('proceso_id', procesoId);
        
        fetchWithAuth('/procesos_juridicos/backend/controllers/AnexoController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) cargarAnexos(procesoId);
        });
    }
}

function cargarTiposProceso() {
    return fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php?action=getTipos')
        .then(response => response.json())
        .then(data => {
            let select = document.getElementById('tipo_proceso_id');
            select.innerHTML = '<option value="">Seleccione tipo</option>';
            data.forEach(t => {
                select.innerHTML += `<option value="${t.id}">${t.nombre}</option>`;
            });
        });
}

function cargarEstadosProceso() {
    return fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php?action=getEstados')
        .then(response => response.json())
        .then(data => {
            let select = document.getElementById('estado_proceso_id');
            select.innerHTML = '<option value="">Seleccione estado</option>';
            data.forEach(e => {
                select.innerHTML += `<option value="${e.id}">${e.nombre}</option>`;
            });
        });
}


function renderPaginacionProcesos() {
    let container = document.getElementById('paginacionProcesos');
    if (!container) return;
    
    let html = '';
    
    // Botón anterior
    html += `<button class="pagination-btn" onclick="cambiarPaginaProcesos(${paginaActualProcesos - 1})" ${paginaActualProcesos <= 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>`;
    
    // Información de página
    html += `<span class="pagination-info">Página ${paginaActualProcesos} de ${totalPaginasProcesos}</span>`;
    
    // Botón siguiente
    html += `<button class="pagination-btn" onclick="cambiarPaginaProcesos(${paginaActualProcesos + 1})" ${paginaActualProcesos >= totalPaginasProcesos ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>`;
    
    container.innerHTML = html;
}

function cambiarPaginaProcesos(pagina) {
    if (pagina >= 1 && pagina <= totalPaginasProcesos) {
        cargarProcesos(pagina, terminoBusqueda);
    }
}

document.getElementById('buscarProcesos').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') {
        buscarProcesos();
    }
});

// Debounce para búsqueda automática (opcional)
let timeoutId;
document.getElementById('buscarProcesos').addEventListener('input', function() {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        buscarProcesos();
    }, 500); // Busca 500ms después de dejar de escribir
});

// Cargar procesos al entrar
cargarProcesos(1, '');
</script>