<div class="page-header">
    <h2>Gestión de Procesos</h2>
    <button class="btn btn-primary" onclick="abrirModalProceso()">Nuevo Proceso</button>
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
                <select id="tipo_proceso" name="tipo_proceso" required>
                    <option value="">Seleccione</option>
                    <option value="Civil">Civil</option>
                    <option value="Penal">Penal</option>
                    <option value="Laboral">Laboral</option>
                    <option value="Administrativo">Administrativo</option>
                    <option value="Familia">Familia</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Descripción:</label>
                <textarea id="descripcion" name="descripcion" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label>Estado:</label>
                <select id="estado" name="estado" required>
                    <option value="Activo">Activo</option>
                    <option value="En espera">En espera</option>
                    <option value="Vencido">Vencido</option>
                    <option value="Finalizado">Finalizado</option>
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

<div id="modalAnexos" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModalAnexos()">&times;</span>
        <h3>Administrar Anexos</h3>
        <input type="hidden" id="anexoProcesoId">
        
        <form id="formAnexo" onsubmit="subirAnexo(event)" enctype="multipart/form-data">
            <div class="form-group">
                <label>Seleccionar archivo:</label>
                <input type="file" id="archivo" name="archivo" required>
            </div>
            <button type="submit" class="btn btn-primary">Subir Archivo</button>
        </form>
        
        <hr>
        
        <h4>Archivos subidos</h4>
        <div id="listaAnexos"></div>
    </div>
</div>

<!-- Modal Actuaciones -->
<div id="modalActuaciones" class="modal">
    <div class="modal-content" style="width: 80%; max-width: 1000px;">
        <span class="close" onclick="cerrarModalActuaciones()">&times;</span>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3>Actuaciones del Proceso</h3>
            <button class="btn btn-primary" onclick="sincronizarRama()" id="btnSincronizar">Actualizar</button>
        </div>
        <div id="procesoInfo"></div>
        
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
    fetch(`/procesos_juridicos/backend/controllers/ProcesoController.php?action=get&id=${procesoId}`)
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
    fetch(`/procesos_juridicos/backend/controllers/ActuacionController.php?action=list&proceso_id=${procesoId}`)
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
    
    fetch('/procesos_juridicos/backend/controllers/SincronizarRamaController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            //alert(data.message);
            cargarActuaciones(procesoActual);
        } else {
            //alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error detallado:', error);
        //alert('Error: ' + error.message);
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
function cargarProcesos() {
    fetch('/procesos_juridicos/backend/controllers/ProcesoController.php?action=list')
        .then(response => response.json())
        .then(data => {
            let tbody = document.querySelector('#tablaProcesos tbody');
            tbody.innerHTML = '';
            data.forEach(p => {
                tbody.innerHTML += `
                    <tr>
                        <td>${p.id}</td>
                        <td>${p.numero_radicado}</td>
                        <td>${p.nombre} ${p.apellido}</td>
                        <td>${p.tipo_proceso}</td>
                        <td>${p.estado}</td>
                        <td>${p.fecha_vencimiento || 'N/A'}</td>
                        <td>
                            <button class="btn-icon" onclick="verProceso(${p.id})" data-tooltip="Ver detalles"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon" onclick="verActuaciones(${p.id})" data-tooltip="Actuaciones"><i class="fas fa-history"></i></button>
                            <button class="btn-icon" onclick="editarProceso(${p.id})" data-tooltip="Editar"><i class="fas fa-edit"></i></button>
                            <button class="btn-icon" onclick="abrirModalAnexos(${p.id})" data-tooltip="Anexos"><i class="fas fa-paperclip"></i></button>
                            <button class="btn-icon" onclick="eliminarProceso(${p.id})" data-tooltip="Eliminar"><i class="fas fa-trash"></i></button>                        </td>
                    </tr>
                `;
            });
        });
}

function cargarClientesSelect() {
    fetch('/procesos_juridicos/backend/controllers/ProcesoController.php?action=getClientes')
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
    cargarClientesSelect();
    document.getElementById('formProceso').reset();
    document.getElementById('procesoId').value = '';
    document.getElementById('modalProcesoTitle').textContent = 'Nuevo Proceso';
    document.getElementById('modalProceso').style.display = 'block';
}

function cerrarModalProceso() {
    document.getElementById('modalProceso').style.display = 'none';
}

function guardarProceso(event) {
    event.preventDefault();
    
    let formData = new FormData(document.getElementById('formProceso'));
    let id = document.getElementById('procesoId').value;
    formData.append('action', id ? 'update' : 'create');
    
    fetch('/procesos_juridicos/backend/controllers/ProcesoController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            cerrarModalProceso();
            cargarProcesos();
        }
    });
}

function editarProceso(id) {
    cargarClientesSelect();
    
    fetch(`/procesos_juridicos/backend/controllers/ProcesoController.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(p => {
            document.getElementById('procesoId').value = p.id;
            document.getElementById('cliente_id').value = p.cliente_id;
            document.getElementById('numero_radicado').value = p.numero_radicado;
            document.getElementById('tipo_proceso').value = p.tipo_proceso;
            document.getElementById('descripcion').value = p.descripcion || '';
            document.getElementById('estado').value = p.estado;
            document.getElementById('fecha_inicio').value = p.fecha_inicio;
            document.getElementById('fecha_vencimiento').value = p.fecha_vencimiento || '';
            document.getElementById('modalProcesoTitle').textContent = 'Editar Proceso';
            document.getElementById('modalProceso').style.display = 'block';
        });
}

function verProceso(id) {
    fetch(`/procesos_juridicos/backend/controllers/ProcesoController.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(p => {
            let html = `
                <p><strong>ID:</strong> ${p.id}</p>
                <p><strong>Radicado:</strong> ${p.numero_radicado}</p>
                <p><strong>Cliente:</strong> ${p.nombre} ${p.apellido}</p>
                <p><strong>Tipo:</strong> ${p.tipo_proceso}</p>
                <p><strong>Descripción:</strong> ${p.descripcion || 'N/A'}</p>
                <p><strong>Estado:</strong> ${p.estado}</p>
                <p><strong>Fecha inicio:</strong> ${p.fecha_inicio}</p>
                <p><strong>Fecha vencimiento:</strong> ${p.fecha_vencimiento || 'N/A'}</p>
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
        
        fetch('/procesos_juridicos/backend/controllers/ProcesoController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) cargarProcesos();
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
    fetch(`/procesos_juridicos/backend/controllers/AnexoController.php?action=list&proceso_id=${procesoId}`)
        .then(response => response.json())
        .then(data => {
            let div = document.getElementById('listaAnexos');
            if(data.length === 0) {
                div.innerHTML = '<p>No hay archivos subidos</p>';
                return;
            }
            
            let html = '<ul>';
            data.forEach(a => {
                html += `
                    <li>
                        ${a.nombre_archivo} 
                        <button class="btn btn-delete" onclick="eliminarAnexo(${a.id}, ${procesoId})">Eliminar</button>
                    </li>
                `;
            });
            html += '</ul>';
            div.innerHTML = html;
        });
}

function subirAnexo(event) {
    event.preventDefault();
    
    let procesoId = document.getElementById('anexoProcesoId').value;
    let formData = new FormData(document.getElementById('formAnexo'));
    formData.append('action', 'upload');
    formData.append('proceso_id', procesoId);
    
    fetch('/procesos_juridicos/backend/controllers/AnexoController.php', {
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
        
        fetch('/procesos_juridicos/backend/controllers/AnexoController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) cargarAnexos(procesoId);
        });
    }
}

// Cargar procesos al entrar
cargarProcesos();
</script>