<div class="page-header">
    <h2>Usuarios</h2>
    <button class="btn btn-primary" onclick="abrirModalUsuario()">Nuevo Usuario</button>
</div>

<table id="tablaUsuarios">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Usuario</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

<div id="modalUsuario" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModalUsuario()">&times;</span>
        <h3 id="modalUsuarioTitle">Nuevo Usuario</h3>
        <form id="formUsuario" onsubmit="guardarUsuario(event)">
            <input type="hidden" id="usuarioId" name="id">

            <div class="form-group">
                <label>Nombre completo: <span style="color:red">*</span></label>
                <input type="text" id="uNombre" name="nombre" required>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="form-group">
                    <label>Usuario (login): <span style="color:red">*</span></label>
                    <input type="text" id="uUsuario" name="usuario" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Teléfono:</label>
                    <input type="text" id="uTelefono" name="telefono" placeholder="573001234567">
                </div>
            </div>

            <div class="form-group">
                <label>Email: <span style="color:red">*</span></label>
                <input type="email" id="uEmail" name="email" required>
            </div>

            <div class="form-group">
                <label id="labelPassword">Contraseña: <span style="color:red">*</span></label>
                <input type="password" id="uPassword" name="password" autocomplete="new-password">
                <small id="hintPassword" style="color:#888; display:none;">
                    Dejar en blanco para no cambiar la contraseña
                </small>
            </div>

            <div id="msgError" style="color:red; margin-bottom:10px; display:none;"></div>

            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</div>

<div id="modalVerUsuario" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('modalVerUsuario').style.display='none'">&times;</span>
        <h3>Detalles del Usuario</h3>
        <div id="detalleUsuario"></div>
    </div>
</div>

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

function cargarUsuarios() {
    fetchWithAuth('/procesos_juridicos/backend/controllers/UsuarioController.php?action=list')
        .then(r => r.json())
        .then(data => {
            const tbody = document.querySelector('#tablaUsuarios tbody');
            tbody.innerHTML = '';
            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px">No hay usuarios registrados</td></tr>';
                return;
            }
            data.forEach(u => {
                tbody.innerHTML += `
                    <tr>
                        <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${u.nombre}</td>
                        <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${u.usuario}</td>
                        <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${u.email}</td>
                        <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${u.telefono || '-'}</td>
                        <td style="padding:8px 12px;white-space:nowrap">
                            <button class="btn-icon" onclick="verUsuario(${u.id})"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon" onclick="editarUsuario(${u.id})"><i class="fas fa-edit"></i></button>
                            <button class="btn-icon" onclick="eliminarUsuario(${u.id})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
        });
}

function abrirModalUsuario() {
    document.getElementById('formUsuario').reset();
    document.getElementById('usuarioId').value = '';
    document.getElementById('modalUsuarioTitle').textContent = 'Nuevo Usuario';
    document.getElementById('uPassword').required = true;
    document.getElementById('labelPassword').innerHTML = 'Contraseña: <span style="color:red">*</span>';
    document.getElementById('hintPassword').style.display = 'none';
    document.getElementById('msgError').style.display = 'none';
    document.getElementById('modalUsuario').style.display = 'block';
}

function cerrarModalUsuario() {
    document.getElementById('modalUsuario').style.display = 'none';
}

function guardarUsuario(event) {
    event.preventDefault();
    const msgError = document.getElementById('msgError');
    msgError.style.display = 'none';

    const formData = new FormData(document.getElementById('formUsuario'));
    const id = document.getElementById('usuarioId').value;
    formData.append('action', id ? 'update' : 'create');

    fetchWithAuth('/procesos_juridicos/backend/controllers/UsuarioController.php', {
        method: 'POST', body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            cerrarModalUsuario();
            cargarUsuarios();
            toast('Usuario guardado');
        } else {
            msgError.textContent = data.message || 'Error al guardar';
            msgError.style.display = 'block';
        }
    });
}

function editarUsuario(id) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/UsuarioController.php?action=get&id=${id}`)
        .then(r => r.json())
        .then(u => {
            document.getElementById('usuarioId').value  = u.id;
            document.getElementById('uNombre').value    = u.nombre;
            document.getElementById('uUsuario').value   = u.usuario;
            document.getElementById('uEmail').value     = u.email;
            document.getElementById('uTelefono').value  = u.telefono || '';
            document.getElementById('uPassword').value  = '';
            document.getElementById('uPassword').required = false;
            document.getElementById('labelPassword').innerHTML = 'Contraseña:';
            document.getElementById('hintPassword').style.display = 'block';
            document.getElementById('modalUsuarioTitle').textContent = 'Editar Usuario';
            document.getElementById('msgError').style.display = 'none';
            document.getElementById('modalUsuario').style.display = 'block';
        });
}

function verUsuario(id) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/UsuarioController.php?action=get&id=${id}`)
        .then(r => r.json())
        .then(u => {
            document.getElementById('detalleUsuario').innerHTML = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;padding:10px">
                    <div style="background:#f8f9fa;padding:15px;border-radius:8px;grid-column:span 2">
                        <strong style="color:#2c3e50;font-size:12px;text-transform:uppercase">Nombre</strong>
                        <p style="font-size:18px;margin:5px 0">${u.nombre}</p>
                    </div>
                    <div style="background:#f8f9fa;padding:15px;border-radius:8px">
                        <strong style="color:#2c3e50;font-size:12px;text-transform:uppercase">Usuario</strong>
                        <p style="margin:5px 0">${u.usuario}</p>
                    </div>
                    <div style="background:#f8f9fa;padding:15px;border-radius:8px">
                        <strong style="color:#2c3e50;font-size:12px;text-transform:uppercase">Teléfono</strong>
                        <p style="margin:5px 0">${u.telefono || 'No registrado'}</p>
                    </div>
                    <div style="background:#f8f9fa;padding:15px;border-radius:8px;grid-column:span 2">
                        <strong style="color:#2c3e50;font-size:12px;text-transform:uppercase">Email</strong>
                        <p style="margin:5px 0">${u.email}</p>
                    </div>
                    <div style="grid-column:span 2;text-align:right;color:#7f8c8d;font-size:12px;border-top:1px solid #eee;padding-top:15px">
                        <i class="fas fa-calendar-alt"></i> Creado: ${new Date(u.created_at).toLocaleDateString('es-CO')}
                    </div>
                </div>`;
            document.getElementById('modalVerUsuario').style.display = 'block';
        });
}

function eliminarUsuario(id) {
    if (confirm('¿Está seguro de eliminar este usuario?')) {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        fetchWithAuth('/procesos_juridicos/backend/controllers/UsuarioController.php', {
            method: 'POST', body: fd
        })
        .then(r => r.json())
        .then(data => { if (data.success) { cargarUsuarios(); toast('Usuario eliminado','info'); } });
    }
}

cargarUsuarios();
</script>