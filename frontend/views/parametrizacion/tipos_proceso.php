<script>
let paginaActual = 1;
let totalPaginas = 1;
</script>

<div class="page-header">
    <h2>Especialidades</h2>
    <button class="btn btn-primary" onclick="abrirModalTipo()">Nueva Especialidad</button>
</div>

<table id="tablaTipos">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

<div id="paginacionTipos" class="pagination-container"></div>

<div id="modalTipo" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModalTipo()">&times;</span>
        <h3 id="modalTipoTitle">Nueva Especialidad</h3>
        <form id="formTipo" onsubmit="guardarTipo(event)">
            <input type="hidden" id="tipoId" name="id">
            
            <div class="form-group">
                <label>Nombre:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            
            <div class="form-group">
                <label>Descripción:</label>
                <textarea id="descripcion" name="descripcion" rows="3"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</div>

<div id="modalVerTipo" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModalVer()">&times;</span>
        <h3>Detalles del Tipo</h3>
        <div id="detalleTipo"></div>
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
    
function cargarTipos(pagina = 1) {
    fetchWithAuth('/procesos_juridicos/backend/controllers/TipoProcesoController.php?action=list')
        .then(response => response.json())
        .then(data => {
            let tbody = document.querySelector('#tablaTipos tbody');
            tbody.innerHTML = '';
            
            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" style="padding:30px;text-align:center;color:#aaa">No hay tipos registrados</td></tr>';
                return;
            }
            
            data.forEach(t => {
                tbody.innerHTML += `
                    <tr>
                        <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${t.nombre}</td>
                        <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${t.descripcion || ''}</td>
                        <td style="padding:8px 12px;white-space:nowrap">
                            <button class="btn-icon" onclick="verTipo(${t.id})"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon" onclick="editarTipo(${t.id})"><i class="fas fa-edit"></i></button>
                            <button class="btn-icon" onclick="eliminarTipo(${t.id})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
        });
}

function abrirModalTipo() {
    document.getElementById('formTipo').reset();
    document.getElementById('tipoId').value = '';
    document.getElementById('modalTipoTitle').textContent = 'Nueva Especialidad';
    document.getElementById('modalTipo').style.display = 'block';
}

function cerrarModalTipo() {
    document.getElementById('modalTipo').style.display = 'none';
}

function guardarTipo(event) {
    event.preventDefault();
    
    let formData = new FormData(document.getElementById('formTipo'));
    let id = document.getElementById('tipoId').value;
    formData.append('action', id ? 'update' : 'create');
    
    fetchWithAuth('/procesos_juridicos/backend/controllers/TipoProcesoController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) { cerrarModalTipo(); cargarTipos(); toast('Tipo guardado correctamente'); }
    });
}

function editarTipo(id) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/TipoProcesoController.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(t => {
            document.getElementById('tipoId').value = t.id;
            document.getElementById('nombre').value = t.nombre;
            document.getElementById('descripcion').value = t.descripcion || '';
            document.getElementById('modalTipoTitle').textContent = 'Editar Especialidad';
            document.getElementById('modalTipo').style.display = 'block';
        });
}

function verTipo(id) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/TipoProcesoController.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(t => {
            let html = `
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; padding: 10px;">
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; grid-column: span 2;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase; margin-bottom: 5px;">ID</strong>
                        <span style="font-size: 24px; font-weight: bold; color: #3498db;">${t.id}</span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; grid-column: span 2;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase; margin-bottom: 5px;">Nombre</strong>
                        <span style="font-size: 18px;">${t.nombre}</span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; grid-column: span 2;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase; margin-bottom: 5px;">Descripción</strong>
                        <span style="font-size: 14px; line-height: 1.6;">${t.descripcion || 'Sin descripción'}</span>
                    </div>
                    
                    <div style="grid-column: span 2; text-align: right; margin-top: 10px; color: #7f8c8d; font-size: 12px; border-top: 1px solid #eee; padding-top: 15px;">
                        <i class="fas fa-calendar-alt"></i> Creado: ${new Date(t.created_at).toLocaleDateString('es-CO')}
                    </div>
                </div>
            `;
            document.getElementById('detalleTipo').innerHTML = html;
            document.getElementById('modalVerTipo').style.display = 'block';
        });
}

function eliminarTipo(id) {
    if(confirm('¿Está seguro de eliminar este tipo?')) {
        let formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        fetchWithAuth('/procesos_juridicos/backend/controllers/TipoProcesoController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) { cargarTipos(); toast('Especialidad eliminada','info'); }
        });
    }
}

function cerrarModalVer() {
    document.getElementById('modalVerTipo').style.display = 'none';
}

cargarTipos();
</script>