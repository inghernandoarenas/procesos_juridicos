<script>
let paginaActual = 1;
let totalPaginas = 1;
</script>

<div class="page-header">
    <h2>Estados de Proceso</h2>
    <button class="btn btn-primary" onclick="abrirModalEstado()">Nuevo Estado</button>
</div>

<table id="tablaEstados">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Color</th>
            <th>Vista Previa</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

<div id="paginacionEstados" class="pagination-container"></div>

<div id="modalEstado" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModalEstado()">&times;</span>
        <h3 id="modalEstadoTitle">Nuevo Estado de Proceso</h3>
        <form id="formEstado" onsubmit="guardarEstado(event)">
            <input type="hidden" id="estadoId" name="id">
            
            <div class="form-group">
                <label>Nombre:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            
            <div class="form-group">
                <label>Color:</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="color" id="color" name="color" value="#3498db" style="width: 60px; height: 40px;">
                    <input type="text" id="color_text" value="#3498db" placeholder="#3498db" style="flex: 1;">
                </div>
            </div>
            
            <div class="form-group">
                <label>Vista Previa:</label>
                <div id="vistaPrevia" style="padding: 10px; border-radius: 4px; background: #f0f0f0; text-align: center;">
                    <span id="previewText" style="padding: 5px 15px; border-radius: 4px; color: white; background: #3498db;">Ejemplo de estado</span>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</div>

<div id="modalVerEstado" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModalVer()">&times;</span>
        <h3>Detalles del Estado</h3>
        <div id="detalleEstado"></div>
    </div>
</div>

<script>
function getHeaders() {
    const token = localStorage.getItem('token');
    return {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/x-www-form-urlencoded'
    };
}

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

document.getElementById('color').addEventListener('input', function(e) {
    document.getElementById('color_text').value = e.target.value;
    document.getElementById('previewText').style.backgroundColor = e.target.value;
});

document.getElementById('color_text').addEventListener('input', function(e) {
    document.getElementById('color').value = e.target.value;
    document.getElementById('previewText').style.backgroundColor = e.target.value;
});

function cargarEstados(pagina = 1) {
    fetchWithAuth('/procesos_juridicos/backend/controllers/EstadoProcesoController.php?action=list')
        .then(response => response.json())
        .then(data => {
            let tbody = document.querySelector('#tablaEstados tbody');
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 30px;">No hay estados registrados</td></tr>';
                return;
            }
            
            data.forEach(e => {
                tbody.innerHTML += `
                    <tr>
                        <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${e.nombre}</td>
                        <td style="padding:8px 12px;font-size:12px;color:#2c3e50"><code>${e.color || '#3498db'}</code></td>
                        <td style="padding:8px 12px"><span style="background: ${e.color || '#3498db'}; color: white; padding: 4px 10px; border-radius: 4px; font-size:12px">${e.nombre}</span></td>
                        <td style="padding:8px 12px;white-space:nowrap">
                            <button class="btn-icon" onclick="verEstado(${e.id})"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon" onclick="editarEstado(${e.id})"><i class="fas fa-edit"></i></button>
                            <button class="btn-icon" onclick="eliminarEstado(${e.id})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
        });
}

function abrirModalEstado() {
    document.getElementById('formEstado').reset();
    document.getElementById('estadoId').value = '';
    document.getElementById('modalEstadoTitle').textContent = 'Nuevo Estado de Proceso';
    document.getElementById('color').value = '#3498db';
    document.getElementById('color_text').value = '#3498db';
    document.getElementById('previewText').style.backgroundColor = '#3498db';
    document.getElementById('modalEstado').style.display = 'block';
}

function cerrarModalEstado() {
    document.getElementById('modalEstado').style.display = 'none';
}

function guardarEstado(event) {
    event.preventDefault();
    
    let formData = new FormData(document.getElementById('formEstado'));
    let id = document.getElementById('estadoId').value;
    formData.append('action', id ? 'update' : 'create');
    
    fetchWithAuth('/procesos_juridicos/backend/controllers/EstadoProcesoController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) { cerrarModalEstado(); cargarEstados(); toast('Estado guardado'); }
        else { toast('Error al guardar el estado','error'); }
    });
}

function editarEstado(id) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/EstadoProcesoController.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(e => {
            document.getElementById('estadoId').value = e.id;
            document.getElementById('nombre').value = e.nombre;
            document.getElementById('color').value = e.color || '#3498db';
            document.getElementById('color_text').value = e.color || '#3498db';
            document.getElementById('previewText').style.backgroundColor = e.color || '#3498db';
            document.getElementById('modalEstadoTitle').textContent = 'Editar Estado de Proceso';
            document.getElementById('modalEstado').style.display = 'block';
        });
}

function verEstado(id) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/EstadoProcesoController.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(e => {
            let html = `
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; padding: 10px;">
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; grid-column: span 2;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase; margin-bottom: 5px;">ID</strong>
                        <span style="font-size: 24px; font-weight: bold; color: #3498db;">${e.id}</span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase; margin-bottom: 5px;">Nombre</strong>
                        <span style="font-size: 18px;">${e.nombre}</span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase; margin-bottom: 5px;">Color</strong>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 30px; height: 30px; background: ${e.color || '#3498db'}; border-radius: 4px;"></div>
                            <code>${e.color || '#3498db'}</code>
                        </div>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; grid-column: span 2;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase; margin-bottom: 5px;">Vista Previa</strong>
                        <div style="text-align: center; padding: 20px; background: #f0f0f0; border-radius: 8px;">
                            <span style="background: ${e.color || '#3498db'}; color: white; padding: 10px 25px; border-radius: 20px; font-size: 16px; display: inline-block;">
                                ${e.nombre}
                            </span>
                        </div>
                    </div>
                    
                    <div style="grid-column: span 2; text-align: right; margin-top: 10px; color: #7f8c8d; font-size: 12px; border-top: 1px solid #eee; padding-top: 15px;">
                        <i class="fas fa-calendar-alt"></i> Creado: ${new Date(e.created_at).toLocaleDateString('es-CO')}
                    </div>
                </div>
            `;
            document.getElementById('detalleEstado').innerHTML = html;
            document.getElementById('modalVerEstado').style.display = 'block';
        });
}

function eliminarEstado(id) {
    if(confirm('¿Eliminar este estado?')) {
        let formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        fetchWithAuth('/procesos_juridicos/backend/controllers/EstadoProcesoController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) { cargarEstados(); toast('Estado eliminado','info'); }
        });
    }
}

function cerrarModalVer() {
    document.getElementById('modalVerEstado').style.display = 'none';
}

cargarEstados();
</script>