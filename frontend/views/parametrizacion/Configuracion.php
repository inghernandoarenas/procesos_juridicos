<style>
.config-form {
    max-width: 750px;
}
.config-section {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    margin-bottom: 20px;
    overflow: hidden;
}
.config-section-header {
    background: linear-gradient(135deg, #2c3e50, #34495e);
    color: white;
    padding: 14px 20px;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.config-section-body {
    padding: 20px;
}
.config-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.config-grid.single { grid-template-columns: 1fr; }
.config-field label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #7f8c8d;
    text-transform: uppercase;
    letter-spacing: .4px;
    margin-bottom: 6px;
}
.config-field input,
.config-field textarea {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e8ecef;
    border-radius: 7px;
    font-size: 14px;
    color: #2c3e50;
    transition: border-color .2s;
    box-sizing: border-box;
}
.config-field input:focus,
.config-field textarea:focus {
    outline: none;
    border-color: #3498db;
}
.config-field textarea { resize: vertical; min-height: 70px; }

.config-preview {
    background: linear-gradient(135deg, #1a2a3a, #2c3e50);
    border-radius: 10px;
    padding: 20px 24px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    max-width: 750px;
}
.preview-icon {
    font-size: 40px;
    line-height: 1;
}
.preview-nombre {
    font-size: 18px;
    font-weight: 700;
    color: white;
}
.preview-subtitulo {
    font-size: 12px;
    color: rgba(255,255,255,.6);
    margin-top: 3px;
}
.preview-datos {
    margin-top: 6px;
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}
.preview-dato {
    font-size: 11px;
    color: rgba(255,255,255,.5);
    display: flex;
    align-items: center;
    gap: 5px;
}
</style>

<div class="page-header">
    <h2>Configuración del Sistema</h2>
</div>

<!-- Preview en tiempo real -->
<div class="config-preview" id="configPreview">
    <div class="preview-icon">⚖️</div>
    <div>
        <div class="preview-nombre" id="previewNombre">Oficina Jurídica</div>
        <div class="preview-subtitulo" id="previewSubtitulo">Sistema de Gestión de Procesos Judiciales</div>
        <div class="preview-datos">
            <span class="preview-dato" id="previewTel" style="display:none"><i class="fas fa-phone"></i> <span></span></span>
            <span class="preview-dato" id="previewEmail" style="display:none"><i class="fas fa-envelope"></i> <span></span></span>
            <span class="preview-dato" id="previewCiudad" style="display:none"><i class="fas fa-map-marker-alt"></i> <span></span></span>
        </div>
    </div>
</div>

<form class="config-form" id="formConfig" onsubmit="guardarConfig(event)">

    <!-- Datos principales -->
    <div class="config-section">
        <div class="config-section-header">
            <i class="fas fa-building"></i> Datos del Despacho
        </div>
        <div class="config-section-body">
            <div class="config-grid">
                <div class="config-field">
                    <label>Nombre del Despacho / Firma</label>
                    <input type="text" name="nombre_empresa" id="nombre_empresa"
                           placeholder="Ej: Arenas & Asociados Abogados"
                           oninput="actualizarPreview()">
                </div>
                <div class="config-field">
                    <label>Subtítulo / Eslogan</label>
                    <input type="text" name="subtitulo" id="subtitulo"
                           placeholder="Ej: Especialistas en Derecho Laboral"
                           oninput="actualizarPreview()">
                </div>
                <div class="config-field">
                    <label>NIT / Documento</label>
                    <input type="text" name="nit" id="nit" placeholder="Ej: 900.123.456-7">
                </div>
                <div class="config-field">
                    <label>Año Copyright</label>
                    <input type="text" name="anio_copyright" id="anio_copyright" placeholder="2025">
                </div>
            </div>
        </div>
    </div>

    <!-- Contacto -->
    <div class="config-section">
        <div class="config-section-header">
            <i class="fas fa-address-card"></i> Contacto
        </div>
        <div class="config-section-body">
            <div class="config-grid">
                <div class="config-field">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" id="telefono"
                           placeholder="Ej: +57 300 123 4567"
                           oninput="actualizarPreview()">
                </div>
                <div class="config-field">
                    <label>Email</label>
                    <input type="email" name="email" id="email"
                           placeholder="contacto@despacho.com"
                           oninput="actualizarPreview()">
                </div>
                <div class="config-field">
                    <label>Ciudad</label>
                    <input type="text" name="ciudad" id="ciudad"
                           placeholder="Ej: Barranquilla, Atlántico"
                           oninput="actualizarPreview()">
                </div>
                <div class="config-field">
                    <label>Sitio Web</label>
                    <input type="text" name="website" id="website"
                           placeholder="www.despacho.com">
                </div>
                <div class="config-field" style="grid-column:span 2">
                    <label>Dirección</label>
                    <input type="text" name="direccion" id="direccion"
                           placeholder="Ej: Calle 72 #45-20, Piso 3">
                </div>
            </div>
        </div>
    </div>

    <!-- Reportes -->
    <div class="config-section">
        <div class="config-section-header">
            <i class="fas fa-file-pdf"></i> Reportes PDF
        </div>
        <div class="config-section-body">
            <div class="config-grid single">
                <div class="config-field">
                    <label>Texto del pie de página en reportes</label>
                    <textarea name="pie_reporte" id="pie_reporte"
                              placeholder="Texto que aparece al final de cada reporte generado"></textarea>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;align-items:center">
        <button type="submit" class="btn btn-primary" style="padding:12px 28px;font-size:14px">
            <i class="fas fa-save"></i> Guardar Configuración
        </button>
        <span id="configMsg" style="font-size:13px;color:#7f8c8d"></span>
    </div>

</form>

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

// Cargar config al entrar
function cargarConfig() {
    fetchWithAuth('/procesos_juridicos/backend/controllers/ConfiguracionController.php?action=get')
        .then(r => r.json())
        .then(data => {
            Object.keys(data).forEach(clave => {
                const el = document.getElementById(clave);
                if (el) el.value = data[clave] || '';
            });
            actualizarPreview();
        });
}

// Preview en tiempo real
function actualizarPreview() {
    const nombre   = document.getElementById('nombre_empresa').value  || 'Oficina Jurídica';
    const subtitulo = document.getElementById('subtitulo').value       || 'Sistema de Gestión de Procesos Judiciales';
    const tel      = document.getElementById('telefono').value;
    const email    = document.getElementById('email').value;
    const ciudad   = document.getElementById('ciudad').value;

    document.getElementById('previewNombre').textContent    = nombre;
    document.getElementById('previewSubtitulo').textContent = subtitulo;

    const showDato = (id, valor) => {
        const el = document.getElementById(id);
        if (valor) {
            el.style.display = 'flex';
            el.querySelector('span').textContent = valor;
        } else {
            el.style.display = 'none';
        }
    };

    showDato('previewTel',    tel);
    showDato('previewEmail',  email);
    showDato('previewCiudad', ciudad);
}

// Guardar
function guardarConfig(event) {
    event.preventDefault();
    const fd = new FormData(document.getElementById('formConfig'));
    fd.append('action', 'save');

    fetchWithAuth('/procesos_juridicos/backend/controllers/ConfiguracionController.php', {
        method: 'POST', body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            toast('Configuración guardada correctamente');
        } else {
            toast('Error al guardar la configuración', 'error');
        }
    });
}

cargarConfig();
</script>