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
    <div class="modal-content">
        <span class="close" onclick="cerrarModalVer()">&times;</span>
        <h3>Detalles del Cliente</h3>
        <div id="detalleCliente"></div>
    </div>
</div>

<script>
function cargarClientes() {
    fetch('/procesos_juridicos/backend/controllers/ClienteController.php?action=list')
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
    
    fetch('/procesos_juridicos/backend/controllers/ClienteController.php', {
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
    fetch(`/procesos_juridicos/backend/controllers/ClienteController.php?action=get&id=${id}`)
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
    fetch(`/procesos_juridicos/backend/controllers/ClienteController.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(cliente => {
            let html = `
                <p><strong>ID:</strong> ${cliente.id}</p>
                <p><strong>Nombre:</strong> ${cliente.nombre} ${cliente.apellido}</p>
                <p><strong>Email:</strong> ${cliente.email || 'N/A'}</p>
                <p><strong>Teléfono:</strong> ${cliente.telefono || 'N/A'}</p>
                <p><strong>Dirección:</strong> ${cliente.direccion || 'N/A'}</p>
                <p><strong>Fecha registro:</strong> ${cliente.created_at}</p>
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
        
        fetch('/procesos_juridicos/backend/controllers/ClienteController.php', {
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