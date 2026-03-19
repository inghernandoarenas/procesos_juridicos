<script>
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
    <h2>Gestión de Clientes</h2>
    <button class="btn btn-primary" onclick="abrirModalCliente()">Nuevo Cliente</button>
</div>

<table id="tablaClientes">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

<div id="modalCliente" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModalCliente()">&times;</span>
        <h3 id="modalClienteTitle">Nuevo Cliente</h3>
        <form id="formCliente" onsubmit="guardarCliente(event)">
            <input type="hidden" id="clienteId" name="id">
            <div class="form-group">
                <label>Nombre:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            <div class="form-group">
                <label>Apellido:</label>
                <input type="text" id="apellido" name="apellido" required>
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" id="email" name="email">
            </div>
            <div class="form-group">
                <label>Teléfono:</label>
                <input type="text" id="telefono" name="telefono">
            </div>
            <div class="form-group">
                <label>Dirección:</label>
                <textarea id="direccion" name="direccion" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</div>

<div id="modalVerCliente" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close" onclick="cerrarModalVer()">&times;</span>
        <h3 style="margin-bottom: 20px;">Detalles del Cliente</h3>
        <div id="detalleCliente"></div>
    </div>
</div>

<script>
function cargarClientes() {
    fetchWithAuth('/procesos_juridicos/backend/controllers/ClienteController.php?action=list')
        .then(response => response.json())
        .then(data => {
            let tbody = document.querySelector('#tablaClientes tbody');
            tbody.innerHTML = '';
            data.forEach(cliente => {
                tbody.innerHTML += `
                    <tr>
                        <td>${cliente.id}</td>
                        <td>${cliente.nombre}</td>
                        <td>${cliente.apellido}</td>
                        <td>${cliente.email || ''}</td>
                        <td>${cliente.telefono || ''}</td>
                        <td>
                            <button class="btn-icon" onclick="verCliente(${cliente.id})" data-tooltip="Ver"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon" onclick="editarCliente(${cliente.id})" data-tooltip="Editar"><i class="fas fa-edit"></i></button>
                            <button class="btn-icon" onclick="eliminarCliente(${cliente.id})" data-tooltip="Eliminar"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
        });
}

function abrirModalCliente() {
    document.getElementById('formCliente').reset();
    document.getElementById('clienteId').value = '';
    document.getElementById('modalClienteTitle').textContent = 'Nuevo Cliente';
    document.getElementById('modalCliente').style.display = 'block';
}

function cerrarModalCliente() {
    document.getElementById('modalCliente').style.display = 'none';
}

function guardarCliente(event) {
    event.preventDefault();
    
    let formData = new FormData(document.getElementById('formCliente'));
    let id = document.getElementById('clienteId').value;
    formData.append('action', id ? 'update' : 'create');
    
    fetchWithAuth('/procesos_juridicos/backend/controllers/ClienteController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            cerrarModalCliente();
            cargarClientes();
        }
    });
}

function editarCliente(id) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/ClienteController.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(cliente => {
            document.getElementById('clienteId').value = cliente.id;
            document.getElementById('nombre').value = cliente.nombre;
            document.getElementById('apellido').value = cliente.apellido;
            document.getElementById('email').value = cliente.email || '';
            document.getElementById('telefono').value = cliente.telefono || '';
            document.getElementById('direccion').value = cliente.direccion || '';
            document.getElementById('modalClienteTitle').textContent = 'Editar Cliente';
            document.getElementById('modalCliente').style.display = 'block';
        });
}

function verCliente(id) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/ClienteController.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(cliente => {
            let html = `
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; padding: 10px;">
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase;">ID Cliente</strong>
                        <span style="font-size: 16px;">${cliente.id}</span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; grid-column: span 2;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase;">Nombre Completo</strong>
                        <span style="font-size: 18px; font-weight: bold; color: #3498db;">${cliente.nombre} ${cliente.apellido}</span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase;">Email</strong>
                        <span style="font-size: 14px;">
                            <i class="fas fa-envelope" style="color: #3498db; margin-right: 5px;"></i>
                            ${cliente.email || 'N/A'}
                        </span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase;">Teléfono</strong>
                        <span style="font-size: 14px;">
                            <i class="fas fa-phone" style="color: #27ae60; margin-right: 5px;"></i>
                            ${cliente.telefono || 'N/A'}
                        </span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; grid-column: span 2;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase;">Dirección</strong>
                        <span style="font-size: 14px;">
                            <i class="fas fa-map-marker-alt" style="color: #e74c3c; margin-right: 5px;"></i>
                            ${cliente.direccion || 'N/A'}
                        </span>
                    </div>
                    
                    <div style="grid-column: span 2; text-align: right; margin-top: 10px; color: #7f8c8d; font-size: 12px;">
                        <i class="fas fa-calendar-alt"></i> Cliente desde: ${new Date(cliente.created_at).toLocaleDateString('es-CO')}
                    </div>
                </div>
            `;
            document.getElementById('detalleCliente').innerHTML = html;
            document.getElementById('modalVerCliente').style.display = 'block';
        });
}

function eliminarCliente(id) {
    if(confirm('¿Está seguro de eliminar este cliente?')) {
        let formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        fetchWithAuth('/procesos_juridicos/backend/controllers/ClienteController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) cargarClientes();
        });
    }
}

function cerrarModalVer() {
    document.getElementById('modalVerCliente').style.display = 'none';
}

// Cargar clientes al entrar a la página
cargarClientes();
</script>