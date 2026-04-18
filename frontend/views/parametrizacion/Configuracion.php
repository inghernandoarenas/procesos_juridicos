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

    <!-- SAMAI -->
    <div class="config-section">
        <div class="config-section-header" style="background:linear-gradient(135deg,#4a235a,#7c3aed)">
            <i class="fas fa-landmark"></i> SAMAI â Consejo de Estado
        </div>
        <div class="config-section-body">
            <p style="font-size:13px;color:#7f8c8d;margin:0 0 16px;line-height:1.6">
                SAMAI requiere una sesión activa para traer las actuaciones. Copia las cookies
                de tu sesión desde DevTools (F12 → Application → Cookies) y pégalas aquí.
            </p>
            <div class="config-grid">
                <div class="config-field">
                    <label><i class="fas fa-cookie-bite" style="color:#7c3aed"></i> ASP.NET_SessionId</label>
                    <input type="text" name="samai_session_id" id="samai_session_id"
                           placeholder="Valor de ASP.NET_SessionId"
                           style="font-family:monospace;font-size:12px">
                </div>
                <div class="config-field">
                    <label><i class="fas fa-shield-alt" style="color:#7c3aed"></i> __AntiXsrfToken</label>
                    <input type="text" name="samai_xsrf_token" id="samai_xsrf_token"
                           placeholder="Valor de __AntiXsrfToken"
                           style="font-family:monospace;font-size:12px">
                </div>
                <div class="config-field" style="grid-column:span 2">
                    <label><i class="fas fa-key" style="color:#7c3aed"></i> TiPMix <span style="font-size:10px;font-weight:400;color:#bdc3c7">— cookie Azure, requerida para ver actuaciones</span></label>
                    <input type="text" name="samai_tipmix" id="samai_tipmix"
                           placeholder="Valor de TiPMix (ej: 40.466264138971496)"
                           style="font-family:monospace;font-size:12px;width:100%">
                </div>
            </div>
            <div style="margin-top:12px;background:#f3e8ff;border-radius:8px;padding:10px 14px;font-size:12px;color:#6b21a8">
                <i class="fas fa-info-circle"></i>
                Entra a samai.consejodeestado.gov.co, busca un proceso, luego
                F12 → Application → Cookies → samai.consejodeestado.gov.co.
                Copia <code>ASP.NET_SessionId</code>, <code>__AntiXsrfToken</code> y <code>TiPMix</code>.
                Duran ~1 hora — renuévalas si dejan de funcionar.
            </div>
            <div style="margin-top:10px">
                <button type="button" onclick="probarSamai()"
                        style="background:#7c3aed;color:white;border:none;padding:8px 16px;border-radius:6px;font-size:12px;cursor:pointer">
                    <i class="fas fa-vial"></i> Probar conexión SAMAI
                </button>
                <span id="samaiTestResult" style="margin-left:12px;font-size:12px"></span>
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

function probarSamai() {
    const sid   = document.getElementById('samai_session_id').value.trim();
    const token = document.getElementById('samai_xsrf_token').value.trim();
    const res   = document.getElementById('samaiTestResult');
    if (!sid || !token) {
        res.innerHTML = '<span style="color:#e74c3c">Rellena ambos campos primero</span>';
        return;
    }
    res.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Probando...';
    fetchWithAuth('/procesos_juridicos/backend/controllers/ConfiguracionController.php?action=test_samai')
        .then(r => r.json())
        .then(d => {
            res.innerHTML = d.success
                ? '<span style="color:#27ae60"><i class="fas fa-check-circle"></i> Conexión OK</span>'
                : '<span style="color:#e74c3c"><i class="fas fa-times-circle"></i> ' + d.message + '</span>';
        })
        .catch(() => res.innerHTML = '<span style="color:#e74c3c">Error de red</span>');
}
</script>