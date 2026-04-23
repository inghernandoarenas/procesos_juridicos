<script>
// Para procesos
let paginaActualProcesos = 1;
let totalPaginasProcesos = 1;

// Para clientes
let paginaActualClientes = 1;
let totalPaginasClientes = 1;

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
</script>

<div class="page-header">
    <h2>Gestión de Clientes</h2>
    <button class="btn btn-primary" onclick="abrirModalCliente()">Nuevo Cliente</button>
</div>

<table id="tablaClientes">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Identificación</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

<div id="paginacionClientes" class="pagination-container" style="margin-top: 20px; display: flex; justify-content: center; align-items: center; gap: 10px;"></div>

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

            <div style="display:grid;grid-template-columns:1fr 2fr;gap:12px">
                <div class="form-group">
                    <label>Tipo ID:</label>
                    <select id="tipo_identificacion" name="tipo_identificacion">
                        <option value="">-- Seleccione --</option>
                        <option value="CC">CC - Cédula</option>
                        <option value="NIT">NIT</option>
                        <option value="CE">CE - Cédula extranjería</option>
                        <option value="PP">PP - Pasaporte</option>
                        <option value="TI">TI - Tarjeta identidad</option>
                        <option value="RC">RC - Registro civil</option>
                        <option value="PEP">PEP</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Número de identificación:</label>
                    <input type="text" id="numero_identificacion" name="numero_identificacion" placeholder="Ej: 1.234.567.890">
                </div>
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" id="email" name="email">
            </div>

            <div class="form-group">
                <label>Teléfono:</label>
                <input type="text" id="telefono" name="telefono" 
                    pattern="[0-9]{7,10}" 
                    title="Ingrese un número de 7 a 10 dígitos (solo números)"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    maxlength="10">
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
function cargarClientes(pagina = 1, buscar = '') {
    let url = `/procesos_juridicos/backend/controllers/ClienteController.php?action=list&por_pagina=10&pagina=${pagina}`;
    if (buscar) url += `&buscar=${encodeURIComponent(buscar)}`;

    fetchWithAuth(url)
        .then(response => response.json())
        .then(result => {
            paginaActualClientes = result.pagina;
            totalPaginasClientes = result.total_paginas;
            
            let tbody = document.querySelector('#tablaClientes tbody');
            tbody.innerHTML = '';

            result.data.forEach(cliente => {
                tbody.innerHTML += `
                    <tr>
                        <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${cliente.nombre || '—'}</td>
                        <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${cliente.apellido || '—'}</td>
                        <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${cliente.tipo_identificacion ? cliente.tipo_identificacion + ' ' + cliente.numero_identificacion : '—'}</td>
                        <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${cliente.email || '—'}</td>
                        <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${cliente.telefono || '—'}</td>
                        <td style="padding:8px 12px;white-space:nowrap">
                            <button class="btn-icon" onclick="verCliente(${cliente.id})"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon" onclick="editarCliente(${cliente.id})"><i class="fas fa-edit"></i></button>
                            <button class="btn-icon" onclick="eliminarCliente(${cliente.id})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });

            renderPaginacionClientes();
        });
}

function abrirModalCliente() {
    document.getElementById('formCliente').reset();
    document.getElementById('clienteId').value = '';
    document.getElementById('tipo_identificacion').value = '';
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
        if(data.success) { cerrarModalCliente(); cargarClientes(1); toast('Cliente guardado'); }
        else { toast('Error al guardar el cliente','error'); }
    });
}

function editarCliente(id) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/ClienteController.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(cliente => {
            document.getElementById('clienteId').value = cliente.id;
            document.getElementById('nombre').value = cliente.nombre;
            document.getElementById('apellido').value = cliente.apellido;
            document.getElementById('tipo_identificacion').value  = cliente.tipo_identificacion   || '';
            document.getElementById('numero_identificacion').value = cliente.numero_identificacion || '';
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
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; grid-column: span 2;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase; margin-bottom: 5px;">ID</strong>
                        <span style="font-size: 24px; font-weight: bold; color: #3498db;">${cliente.id}</span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase; margin-bottom: 5px;">Nombre</strong>
                        <span style="font-size: 18px;">${cliente.nombre} ${cliente.apellido}</span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase; margin-bottom: 5px;">Email</strong>
                        <span style="font-size: 14px;">${cliente.email || 'No registrado'}</span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase; margin-bottom: 5px;">Teléfono</strong>
                        <span style="font-size: 14px;">${cliente.telefono || 'No registrado'}</span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase; margin-bottom: 5px;">Identificación</strong>
                        <span style="font-size: 14px;">${cliente.tipo_identificacion ? cliente.tipo_identificacion + ' ' + cliente.numero_identificacion : 'No registrada'}</span>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; grid-column: span 2;">
                        <strong style="color: #2c3e50; display: block; font-size: 12px; text-transform: uppercase; margin-bottom: 5px;">Dirección</strong>
                        <span style="font-size: 14px;">${cliente.direccion || 'No registrada'}</span>
                    </div>
                    
                    <div style="grid-column: span 2; text-align: right; margin-top: 10px; color: #7f8c8d; font-size: 12px; border-top: 1px solid #eee; padding-top: 15px;">
                        <i class="fas fa-calendar-alt"></i> Creado: ${new Date(cliente.created_at).toLocaleDateString('es-CO')}
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
            if(data.success) { cargarClientes(1); toast('Cliente eliminado','info'); }
        });
    }
}

function cerrarModalVer() {
    document.getElementById('modalVerCliente').style.display = 'none';
}

function renderPaginacionClientes() {
    let container = document.getElementById('paginacionClientes');
    if (!container) return;
    
    let html = '';

    html += `<button onclick="cambiarPaginaClientes(${paginaActualClientes - 1})" ${paginaActualClientes <= 1 ? 'disabled' : ''}>Anterior</button>`;
    html += `<span>Página ${paginaActualClientes} de ${totalPaginasClientes}</span>`;
    html += `<button onclick="cambiarPaginaClientes(${paginaActualClientes + 1})" ${paginaActualClientes >= totalPaginasClientes ? 'disabled' : ''}>Siguiente</button>`;
    
    container.innerHTML = html;
}

function cambiarPaginaClientes(pagina) {
    if (pagina >= 1 && pagina <= totalPaginasClientes) {
        cargarClientes(pagina);
    }
}

cargarClientes(1);
</script>