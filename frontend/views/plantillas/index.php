<style>
/* ── Plantillas ─────────────────────────────────────────── */
.pl-head { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:6px; }
.pl-head h2 { margin:0; font-size:17px; }
.pl-sub { font-size:12px; color:#7f8c8d; margin:0 0 16px; }
.pl-btn { border:1px solid #e0e0e0; background:#fff; color:#2c3e50; padding:7px 14px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
.pl-btn:hover { background:#f8f9fa; }
.pl-btn.primario { background:#3498db; border-color:#3498db; color:#fff; }
.pl-btn.primario:hover { background:#2f89c5; }
.pl-btn i { font-size:12px; }

.pl-vars { background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,.07); padding:14px 18px; margin-bottom:18px; display:none; }
.pl-vars.abierto { display:block; }
.pl-vars h4 { margin:0 0 4px; font-size:13px; color:#2c3e50; }
.pl-vars p  { margin:0 0 10px; font-size:12px; color:#7f8c8d; }
.pl-vars-grupo { margin-bottom:10px; }
.pl-vars-grupo > span { display:inline-block; width:74px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#95a5a6; vertical-align:top; padding-top:5px; }
.pl-var { display:inline-block; margin:2px 4px 2px 0; padding:3px 9px; border-radius:12px; background:#eaf4fd; color:#2980b9; font-size:11px; font-family:Consolas,monospace; cursor:pointer; border:none; }
.pl-var:hover { background:#d6eaf8; }

.pl-cat { margin-bottom:22px; }
.pl-cat-titulo { display:flex; align-items:center; gap:8px; margin-bottom:10px; padding-bottom:6px; border-bottom:2px solid #eef2f7; }
.pl-cat-titulo b { font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#2c3e50; }
.pl-cat-titulo span { font-size:11px; color:#95a5a6; background:#f0f4f8; padding:1px 8px; border-radius:10px; }
.pl-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(270px, 1fr)); gap:14px; }
.pl-card { background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,.07); padding:14px 16px; display:flex; flex-direction:column; gap:8px; border-top:4px solid #3498db; transition:transform .2s, box-shadow .2s; }
.pl-card:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,.1); }
.pl-card.inactiva { opacity:.55; border-top-color:#bdc3c7; }
.pl-card-top { display:flex; align-items:center; gap:10px; }
.pl-ico { width:36px; height:36px; border-radius:9px; background:#eaf4fd; color:#2980b9; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.pl-nombre { font-weight:700; font-size:13px; color:#2c3e50; line-height:1.25; }
.pl-desc { font-size:12px; color:#7f8c8d; min-height:32px; line-height:1.4; }
.pl-acciones { display:flex; align-items:center; gap:6px; margin-top:2px; }
.pl-acciones .pl-btn.primario { flex:1; justify-content:center; }
.pl-ib { border:none; background:#f4f6f8; color:#7f8c8d; width:30px; height:30px; border-radius:7px; cursor:pointer; }
.pl-ib:hover { background:#e9edf1; color:#2c3e50; }
.pl-ib.rojo:hover { background:#fdecea; color:#e74c3c; }
.pl-vacio { text-align:center; padding:50px 20px; color:#bdc3c7; }
.pl-vacio i { font-size:46px; display:block; margin-bottom:10px; }

.pl-resultados { border:1px solid #e8ecef; border-radius:8px; max-height:180px; overflow-y:auto; margin-top:6px; display:none; }
.pl-res { padding:8px 12px; font-size:12px; cursor:pointer; border-bottom:1px solid #f1f3f5; }
.pl-res:last-child { border-bottom:none; }
.pl-res:hover { background:#f4f9fd; }
.pl-res small { display:block; color:#95a5a6; }
.pl-sel { display:none; align-items:center; gap:10px; background:#eafaf1; color:#1e8449; border-radius:8px; padding:9px 12px; font-size:12px; margin-top:8px; }
.pl-sel b { font-weight:700; }
.pl-sel button { margin-left:auto; border:none; background:transparent; color:#1e8449; cursor:pointer; font-size:14px; }
</style>

<div class="pl-head">
    <h2><i class="fas fa-file-signature" style="color:#3498db;margin-right:6px"></i>Plantillas de documentos</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="pl-btn" onclick="plToggleVars()"><i class="fas fa-code"></i> Variables disponibles</button>
        <button class="pl-btn primario" onclick="plAbrirSubir()"><i class="fas fa-upload"></i> Subir plantilla</button>
    </div>
</div>
<p class="pl-sub">Documentos Word con membrete de la firma. Al generarlos, el sistema completa las variables con los datos del proceso, el cliente y el abogado.</p>

<div class="pl-vars" id="plVars">
    <h4>Variables que puedes usar dentro del Word</h4>
    <p>Escríbelas tal cual (con las dobles llaves) en cualquier parte del documento —incluidos encabezado y pie— y se reemplazan al generar. Clic en una para copiarla. Si un dato no existe, queda <b>__________</b> para que se complete a mano.</p>
    <div id="plVarsLista"></div>
</div>

<div id="plLista"></div>

<!-- ══ MODAL GENERAR ═════════════════════════════════════════ -->
<div id="modalGenerar" class="modal">
    <div class="modal-content" style="width:92%;max-width:520px">
        <span class="close" onclick="plCerrar('modalGenerar')">&times;</span>
        <h3><i class="fas fa-file-word" style="color:#2980b9"></i> <span id="plGenTitulo">Generar documento</span></h3>

        <div class="form-group">
            <label>Proceso</label>
            <input type="text" id="plBuscarProceso" placeholder="Busca por radicado o por cliente..." autocomplete="off" oninput="plBuscarProcesoDebounce()">
            <div class="pl-resultados" id="plResultados"></div>
            <div class="pl-sel" id="plSel"><i class="fas fa-check-circle"></i><div id="plSelTxt"></div><button onclick="plQuitarProceso()" title="Cambiar"><i class="fas fa-times"></i></button></div>
        </div>
        <label style="display:flex;align-items:center;gap:8px;font-size:12px;margin-bottom:16px;cursor:pointer">
            <input type="checkbox" id="plGuardar" checked style="width:auto"> Guardar también una copia en los Anexos del proceso
        </label>
        <div style="text-align:right">
            <button class="pl-btn" onclick="plCerrar('modalGenerar')">Cancelar</button>
            <button class="pl-btn primario" id="plGenBtn" onclick="plGenerar()"><i class="fas fa-download"></i> Generar y descargar</button>
        </div>
    </div>
</div>

<!-- ══ MODAL SUBIR / EDITAR ══════════════════════════════════ -->
<div id="modalPlantilla" class="modal">
    <div class="modal-content" style="width:92%;max-width:520px">
        <span class="close" onclick="plCerrar('modalPlantilla')">&times;</span>
        <h3><i class="fas fa-file-signature" style="color:#3498db"></i> <span id="plFormTitulo">Subir plantilla</span></h3>
        <input type="hidden" id="plFormId">
        <div class="form-group"><label>Nombre *</label><input type="text" id="plFormNombre" maxlength="150"></div>
        <div class="form-group"><label>Categoría</label>
            <input type="text" id="plFormCategoria" list="plCategorias" maxlength="60" placeholder="Ej: Demandas, Poderes, Memoriales...">
            <datalist id="plCategorias"></datalist>
        </div>
        <div class="form-group"><label>Descripción</label><textarea id="plFormDesc" rows="2"></textarea></div>
        <div class="form-group" id="plFormArchivoWrap"><label>Archivo Word (.docx, máx. 5 MB) *</label><input type="file" id="plFormArchivo" accept=".docx"></div>
        <div style="text-align:right">
            <button class="pl-btn" onclick="plCerrar('modalPlantilla')">Cancelar</button>
            <button class="pl-btn primario" onclick="plGuardar()"><i class="fas fa-save"></i> Guardar</button>
        </div>
    </div>
</div>
<input type="file" id="plReemplazarInput" accept=".docx" style="display:none">

<script>
const PL_API = '/procesos_juridicos/backend/controllers/PlantillaController.php';
const PL_PROC_API = '/procesos_juridicos/backend/controllers/ProcesoController.php';
let plData = [];
let plPlantillaActual = null;
let plProcesoSel = null;
let plTimer = null;

function plEsc(t) {
    return String(t ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function plFetch(url, options = {}) {
    const token = localStorage.getItem('token');
    if (!token) { window.location.href = '/procesos_juridicos/frontend/login.php'; return Promise.reject(); }
    options.headers = { ...(options.headers || {}), 'Authorization': 'Bearer ' + token };
    return fetch(url, options).then(r => {
        if (r.status === 401) { localStorage.clear(); window.location.href = '/procesos_juridicos/frontend/login.php'; }
        return r;
    });
}
function plAbrir(id)  { document.getElementById(id).style.display = 'block'; }
function plCerrar(id) { document.getElementById(id).style.display = 'none'; }

// Guarda un blob como archivo descargable
function plDescargarBlob(blob, nombre) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = nombre;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 2000);
}
function plNombreDeHeader(r, porDefecto) {
    const cd = r.headers.get('Content-Disposition') || '';
    const m = cd.match(/filename\*=UTF-8''([^;]+)/i);
    if (m) { try { return decodeURIComponent(m[1]); } catch (e) {} }
    return porDefecto;
}

// ── Lista ───────────────────────────────────────────────────
function plCargar() {
    plFetch(PL_API + '?action=list&todas=1').then(r => r.json()).then(d => {
        plData = Array.isArray(d) ? d : [];
        plRender();
        const dl = document.getElementById('plCategorias');
        dl.innerHTML = [...new Set(plData.map(p => p.categoria))].map(c => `<option value="${plEsc(c)}">`).join('');
    }).catch(() => {});
}

function plRender() {
    const cont = document.getElementById('plLista');
    if (!plData.length) {
        cont.innerHTML = '<div class="pl-vacio"><i class="fas fa-file-alt"></i>Aún no hay plantillas. Sube la primera con el botón “Subir plantilla”.</div>';
        return;
    }
    const grupos = {};
    plData.forEach(p => { (grupos[p.categoria] = grupos[p.categoria] || []).push(p); });
    cont.innerHTML = Object.keys(grupos).sort().map(cat => `
        <div class="pl-cat">
            <div class="pl-cat-titulo"><i class="fas fa-folder" style="color:#3498db;font-size:13px"></i><b>${plEsc(cat)}</b><span>${grupos[cat].length}</span></div>
            <div class="pl-grid">${grupos[cat].map(p => `
                <div class="pl-card ${Number(p.activo) ? '' : 'inactiva'}">
                    <div class="pl-card-top">
                        <div class="pl-ico"><i class="fas fa-file-word"></i></div>
                        <div class="pl-nombre">${plEsc(p.nombre)}${Number(p.activo) ? '' : ' <small style="color:#95a5a6">(inactiva)</small>'}</div>
                    </div>
                    <div class="pl-desc">${plEsc(p.descripcion || '')}</div>
                    <div class="pl-acciones">
                        ${Number(p.activo) ? `<button class="pl-btn primario" onclick="plAbrirGenerar(${p.id})"><i class="fas fa-magic"></i> Generar</button>` : `<span style="flex:1"></span>`}
                        <button class="pl-ib" title="Descargar la plantilla (para editarla en Word)" onclick="plDescargarPlantilla(${p.id})"><i class="fas fa-download"></i></button>
                        <button class="pl-ib" title="Reemplazar el archivo Word" onclick="plReemplazar(${p.id})"><i class="fas fa-sync-alt"></i></button>
                        <button class="pl-ib" title="Editar nombre, categoría o activar/desactivar" onclick="plAbrirEditar(${p.id})"><i class="fas fa-pen"></i></button>
                        <button class="pl-ib rojo" title="Eliminar" onclick="plEliminar(${p.id})"><i class="fas fa-trash"></i></button>
                    </div>
                </div>`).join('')}
            </div>
        </div>`).join('');
}

// ── Variables ───────────────────────────────────────────────
function plToggleVars() {
    const box = document.getElementById('plVars');
    box.classList.toggle('abierto');
    if (box.classList.contains('abierto') && !document.getElementById('plVarsLista').innerHTML) {
        plFetch(PL_API + '?action=variables').then(r => r.json()).then(vars => {
            const grupos = {};
            vars.forEach(v => { (grupos[v.grupo] = grupos[v.grupo] || []).push(v); });
            document.getElementById('plVarsLista').innerHTML = Object.keys(grupos).map(g => `
                <div class="pl-vars-grupo"><span>${plEsc(g)}</span>${grupos[g].map(v =>
                    `<button class="pl-var" title="${plEsc(v.etiqueta)}" onclick="plCopiarVar('${v.clave}')">{{${v.clave}}}</button>`).join('')}</div>`).join('');
        });
    }
}
function plCopiarVar(clave) {
    const txt = '{{' + clave + '}}';
    (navigator.clipboard ? navigator.clipboard.writeText(txt) : Promise.reject())
        .then(() => toast('Copiada: ' + txt, 'info', 1800))
        .catch(() => toast(txt, 'info', 3500));
}

// ── Generar ─────────────────────────────────────────────────
function plAbrirGenerar(id) {
    plPlantillaActual = plData.find(p => p.id == id);
    if (!plPlantillaActual) return;
    document.getElementById('plGenTitulo').textContent = plPlantillaActual.nombre;
    plQuitarProceso();
    document.getElementById('plBuscarProceso').value = '';
    document.getElementById('plResultados').style.display = 'none';
    plAbrir('modalGenerar');
    setTimeout(() => document.getElementById('plBuscarProceso').focus(), 50);
}

function plBuscarProcesoDebounce() {
    clearTimeout(plTimer);
    plTimer = setTimeout(plBuscarProceso, 250);
}
function plBuscarProceso() {
    const q = document.getElementById('plBuscarProceso').value.trim();
    const box = document.getElementById('plResultados');
    if (q.length < 2) { box.style.display = 'none'; return; }
    plFetch(`${PL_PROC_API}?action=list&pagina=1&buscar=${encodeURIComponent(q)}`).then(r => r.json()).then(d => {
        const rows = (d && d.data) || [];
        box.innerHTML = rows.length
            ? rows.map(p => `<div class="pl-res" onclick='plElegirProceso(${p.id}, ${JSON.stringify(p.numero_radicado)}, ${JSON.stringify((p.nombre || "") + " " + (p.apellido || ""))})'>
                    <b>${plEsc(p.numero_radicado)}</b><small>${plEsc((p.nombre || '') + ' ' + (p.apellido || ''))}${p.tipo_proceso_nombre ? ' · ' + plEsc(p.tipo_proceso_nombre) : ''}</small></div>`).join('')
            : '<div class="pl-res" style="color:#95a5a6;cursor:default">Sin resultados</div>';
        box.style.display = 'block';
    });
}
function plElegirProceso(id, radicado, cliente) {
    plProcesoSel = id;
    document.getElementById('plResultados').style.display = 'none';
    document.getElementById('plBuscarProceso').style.display = 'none';
    document.getElementById('plSelTxt').innerHTML = `<b>${plEsc(radicado)}</b><br>${plEsc(cliente.trim())}`;
    document.getElementById('plSel').style.display = 'flex';
}
function plQuitarProceso() {
    plProcesoSel = null;
    document.getElementById('plSel').style.display = 'none';
    document.getElementById('plBuscarProceso').style.display = '';
}

function plGenerar() {
    if (!plPlantillaActual) return;
    if (!plProcesoSel) { toast('Selecciona un proceso', 'warning'); return; }
    const btn = document.getElementById('plGenBtn');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';

    const fd = new FormData();
    fd.append('action', 'generar');
    fd.append('plantilla_id', plPlantillaActual.id);
    fd.append('proceso_id', plProcesoSel);
    if (document.getElementById('plGuardar').checked) fd.append('guardar', '1');

    plFetch(PL_API, { method: 'POST', body: fd }).then(async r => {
        if (!r.ok || (r.headers.get('Content-Type') || '').includes('application/json')) {
            const e = await r.json().catch(() => ({}));
            throw new Error(e.message || 'No se pudo generar el documento');
        }
        const falt = JSON.parse(decodeURIComponent(r.headers.get('X-Plantilla-Faltantes') || '[]'));
        const desc = JSON.parse(decodeURIComponent(r.headers.get('X-Plantilla-Desconocidas') || '[]'));
        const guardado = r.headers.get('X-Plantilla-Guardado') === '1';
        const nombre = plNombreDeHeader(r, 'documento.docx');
        plDescargarBlob(await r.blob(), nombre);
        plCerrar('modalGenerar');
        toast('Documento generado' + (guardado ? ' y guardado en Anexos' : ''), 'success', 4000);
        if (falt.length) toast('Sin datos (quedaron en blanco): ' + falt.join(', '), 'warning', 8000);
        if (desc.length) toast('Variables no reconocidas en la plantilla: ' + desc.join(', '), 'warning', 8000);
    }).catch(e => {
        if (e && e.message) toast(e.message, 'error', 6000);
    }).finally(() => {
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-download"></i> Generar y descargar';
    });
}

// ── Descargar / reemplazar / editar / eliminar ─────────────
function plDescargarPlantilla(id) {
    plFetch(`${PL_API}?action=descargar&id=${id}`).then(async r => {
        if (!r.ok) throw 0;
        plDescargarBlob(await r.blob(), plNombreDeHeader(r, 'plantilla.docx'));
    }).catch(() => toast('No se pudo descargar la plantilla', 'error'));
}

let plReemplazarId = 0;
function plReemplazar(id) {
    plReemplazarId = id;
    document.getElementById('plReemplazarInput').value = '';
    document.getElementById('plReemplazarInput').click();
}
document.getElementById('plReemplazarInput').addEventListener('change', e => {
    const f = e.target.files[0];
    if (!f || !plReemplazarId) return;
    if (!confirm('¿Reemplazar el archivo Word de esta plantilla por “' + f.name + '”?')) return;
    const fd = new FormData();
    fd.append('action', 'reemplazar'); fd.append('id', plReemplazarId); fd.append('archivo', f);
    plFetch(PL_API, { method: 'POST', body: fd }).then(r => r.json()).then(d => {
        if (d.success) { toast('Plantilla actualizada'); plCargar(); }
        else toast(d.message || 'No se pudo reemplazar', 'error');
    });
});

function plAbrirSubir() {
    document.getElementById('plFormTitulo').textContent = 'Subir plantilla';
    ['plFormId', 'plFormNombre', 'plFormCategoria', 'plFormDesc', 'plFormArchivo'].forEach(i => document.getElementById(i).value = '');
    document.getElementById('plFormArchivoWrap').style.display = '';
    plAbrir('modalPlantilla');
}
function plAbrirEditar(id) {
    const p = plData.find(x => x.id == id); if (!p) return;
    document.getElementById('plFormTitulo').textContent = 'Editar plantilla';
    document.getElementById('plFormId').value = p.id;
    document.getElementById('plFormNombre').value = p.nombre;
    document.getElementById('plFormCategoria').value = p.categoria;
    document.getElementById('plFormDesc').value = p.descripcion || '';
    document.getElementById('plFormArchivoWrap').style.display = 'none';
    plAbrir('modalPlantilla');
}
function plGuardar() {
    const id = document.getElementById('plFormId').value;
    const nombre = document.getElementById('plFormNombre').value.trim();
    if (!nombre) { toast('El nombre es obligatorio', 'warning'); return; }
    const fd = new FormData();
    fd.append('nombre', nombre);
    fd.append('categoria', document.getElementById('plFormCategoria').value.trim());
    fd.append('descripcion', document.getElementById('plFormDesc').value.trim());
    if (id) {
        fd.append('action', 'actualizar'); fd.append('id', id);
        const p = plData.find(x => x.id == id);
        fd.append('activo', p && Number(p.activo) ? 1 : 0);
    } else {
        const f = document.getElementById('plFormArchivo').files[0];
        if (!f) { toast('Selecciona el archivo .docx', 'warning'); return; }
        fd.append('action', 'subir'); fd.append('archivo', f);
    }
    plFetch(PL_API, { method: 'POST', body: fd }).then(r => r.json()).then(d => {
        if (d.success) { plCerrar('modalPlantilla'); toast('Plantilla guardada'); plCargar(); }
        else toast(d.message || 'No se pudo guardar', 'error');
    });
}
function plEliminar(id) {
    const p = plData.find(x => x.id == id); if (!p) return;
    if (!confirm('¿Eliminar la plantilla “' + p.nombre + '”? Los documentos ya generados no se afectan.')) return;
    const fd = new FormData(); fd.append('action', 'eliminar'); fd.append('id', id);
    plFetch(PL_API, { method: 'POST', body: fd }).then(r => r.json()).then(d => {
        if (d.success) { toast('Plantilla eliminada', 'info'); plCargar(); }
        else toast(d.message || 'No se pudo eliminar', 'error');
    });
}

plCargar();
</script>