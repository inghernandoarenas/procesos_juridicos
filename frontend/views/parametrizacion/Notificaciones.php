<div class="page-header">
    <h2>Configuración de Notificaciones</h2>
    <button class="btn btn-primary" onclick="abrirModalNotif()">Nueva Notificación</button>
</div>

<table id="tablaNotif">
    <thead>
        <tr>
            <th>Usuario</th>
            <th>Tipo</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

<div id="modalNotif" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModalNotif()">&times;</span>
        <h3 id="modalNotifTitle">Nueva Notificación</h3>
        <form id="formNotif" onsubmit="guardarNotif(event)">
            <input type="hidden" id="notifId" name="id">

            <div class="form-group">
                <label>Usuario: <span style="color:red">*</span></label>
                <select id="notifUsuarioId" name="usuario_id" required onchange="cargarDatosUsuario(this.value)">
                    <option value="">-- Seleccione un usuario --</option>
                </select>
            </div>

            <div class="form-group">
                <label>Tipo de notificación: <span style="color:red">*</span></label>
                <select id="notifTipo" name="tipo" required>
                    <option value="email">Solo Email</option>
                    <option value="whatsapp">Solo WhatsApp</option>
                    <option value="ambos">Ambos</option>
                </select>
            </div>

            <div style="border:1px solid #e0e0e0;border-radius:8px;padding:15px;margin-bottom:15px;background:#fafafa">
                <p style="margin:0 0 12px 0;font-size:13px;color:#666">
                    <i class="fas fa-info-circle"></i>
                    Datos precargados del usuario. Puedes editarlos si necesitas usar un correo o teléfono diferente.
                </p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" id="notifEmail" name="email" placeholder="correo@ejemplo.com">
                    </div>
                    <div class="form-group">
                        <label>Teléfono WhatsApp:</label>
                        <input type="text" id="notifTelefono" name="telefono" placeholder="573001234567">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</div>

<div id="modalVerNotif" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('modalVerNotif').style.display='none'">&times;</span>
        <h3>Detalle de Notificación</h3>
        <div id="detalleNotif"></div>
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

function cargarNotificaciones() {
    fetchWithAuth('/procesos_juridicos/backend/controllers/NotificacionConfigController.php?action=list')
        .then(r => r.json())
        .then(data => {
            const tbody = document.querySelector('#tablaNotif tbody');
            tbody.innerHTML = '';
            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px">No hay notificaciones configuradas</td></tr>';
                return;
            }
            const tipos = { email: 'Solo Email', whatsapp: 'Solo WhatsApp', ambos: 'Ambos' };
            data.forEach(n => {
                tbody.innerHTML += `
                    <tr>
                        <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${n.usuario_nombre} <span style="color:#888">(${n.usuario_login})</span></td>
                        <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${tipos[n.tipo] || n.tipo}</td>
                        <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${n.email || '<span style="color:#bbb">—</span>'}</td>
                        <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${n.telefono || '<span style="color:#bbb">—</span>'}</td>
                        <td style="padding:8px 12px;white-space:nowrap">
                            <button class="btn-icon" onclick="verNotif(${n.id})"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon" onclick="editarNotif(${n.id})"><i class="fas fa-edit"></i></button>
                            <button class="btn-icon" onclick="eliminarNotif(${n.id})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
        });
}

function cargarSelectUsuarios(selectedId = null) {
    return fetchWithAuth('/procesos_juridicos/backend/controllers/UsuarioController.php?action=list')
        .then(r => r.json())
        .then(data => {
            const sel = document.getElementById('notifUsuarioId');
            sel.innerHTML = '<option value="">-- Seleccione un usuario --</option>';
            data.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.id;
                opt.textContent = `${u.nombre} (${u.usuario})`;
                if (selectedId && u.id == selectedId) opt.selected = true;
                sel.appendChild(opt);
            });
        });
}

function cargarDatosUsuario(usuarioId) {
    if (!usuarioId) return;
    fetchWithAuth(`/procesos_juridicos/backend/controllers/NotificacionConfigController.php?action=get_usuario&id=${usuarioId}`)
        .then(r => r.json())
        .then(u => {
            if (!u) return;
            document.getElementById('notifEmail').value    = u.email    || '';
            document.getElementById('notifTelefono').value = u.telefono || '';
        });
}

function abrirModalNotif() {
    document.getElementById('formNotif').reset();
    document.getElementById('notifId').value = '';
    document.getElementById('modalNotifTitle').textContent = 'Nueva Notificación';
    cargarSelectUsuarios().then(() => {
        document.getElementById('modalNotif').style.display = 'block';
    });
}

function cerrarModalNotif() {
    document.getElementById('modalNotif').style.display = 'none';
}

function guardarNotif(event) {
    event.preventDefault();
    const fd = new FormData(document.getElementById('formNotif'));
    const id = document.getElementById('notifId').value;
    fd.append('action', id ? 'update' : 'create');
    fetchWithAuth('/procesos_juridicos/backend/controllers/NotificacionConfigController.php', {
        method: 'POST', body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { cerrarModalNotif(); cargarNotificaciones(); toast('Notificación guardada'); }
        else { toast('Error al guardar la notificación','error'); }
    });
}

function editarNotif(id) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/NotificacionConfigController.php?action=get&id=${id}`)
        .then(r => r.json())
        .then(n => {
            cargarSelectUsuarios(n.usuario_id).then(() => {
                document.getElementById('notifId').value       = n.id;
                document.getElementById('notifTipo').value     = n.tipo;
                document.getElementById('notifEmail').value    = n.email    || '';
                document.getElementById('notifTelefono').value = n.telefono || '';
                document.getElementById('modalNotifTitle').textContent = 'Editar Notificación';
                document.getElementById('modalNotif').style.display = 'block';
            });
        });
}

function verNotif(id) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/NotificacionConfigController.php?action=get&id=${id}`)
        .then(r => r.json())
        .then(n => {
            const tipos = { email: 'Solo Email', whatsapp: 'Solo WhatsApp', ambos: 'Ambos' };
            document.getElementById('detalleNotif').innerHTML = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;padding:10px">
                    <div style="background:#f8f9fa;padding:15px;border-radius:8px;grid-column:span 2">
                        <strong style="color:#2c3e50;font-size:12px;text-transform:uppercase">Usuario</strong>
                        <p style="font-size:16px;margin:5px 0">${n.usuario_nombre} <small style="color:#888">(${n.usuario_login})</small></p>
                    </div>
                    <div style="background:#f8f9fa;padding:15px;border-radius:8px">
                        <strong style="color:#2c3e50;font-size:12px;text-transform:uppercase">Tipo</strong>
                        <p style="margin:5px 0">${tipos[n.tipo] || n.tipo}</p>
                    </div>
                    <div style="background:#f8f9fa;padding:15px;border-radius:8px">
                        <strong style="color:#2c3e50;font-size:12px;text-transform:uppercase">Email</strong>
                        <p style="margin:5px 0">${n.email || 'No configurado'}</p>
                    </div>
                    <div style="background:#f8f9fa;padding:15px;border-radius:8px;grid-column:span 2">
                        <strong style="color:#2c3e50;font-size:12px;text-transform:uppercase">Teléfono WhatsApp</strong>
                        <p style="margin:5px 0">${n.telefono || 'No configurado'}</p>
                    </div>
                </div>`;
            document.getElementById('modalVerNotif').style.display = 'block';
        });
}

function eliminarNotif(id) {
    if (confirm('¿Está seguro de eliminar esta notificación?')) {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        fetchWithAuth('/procesos_juridicos/backend/controllers/NotificacionConfigController.php', {
            method: 'POST', body: fd
        })
        .then(r => r.json())
        .then(data => { if (data.success) { cargarNotificaciones(); toast('Notificación eliminada','info'); } });
    }
}

cargarNotificaciones();
</script>