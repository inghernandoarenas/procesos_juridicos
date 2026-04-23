<script>
let _pag=1,_total=1,_all=[];
function fetchWithAuth(url,opts={}){
    const t=localStorage.getItem('token');
    if(!t){window.location.href='/procesos_juridicos/frontend/login.php';return Promise.reject();}
    opts.headers={...opts.headers,'Authorization':'Bearer '+t};
    return fetch(url,opts).then(r=>{if(r.status===401){localStorage.clear();window.location.href='/procesos_juridicos/frontend/login.php';}return r;});
}
function renderPag(){
    document.getElementById('paginacion').innerHTML=`
        <button class="pagination-btn" onclick="irPag(${_pag-1})" ${_pag<=1?'disabled':''}><i class="fas fa-chevron-left"></i></button>
        <span class="pagination-info">Página ${_pag} de ${_total}</span>
        <button class="pagination-btn" onclick="irPag(${_pag+1})" ${_pag>=_total?'disabled':''}><i class="fas fa-chevron-right"></i></button>`;
}
function irPag(p){if(p>=1&&p<=_total){_pag=p;renderTabla();}}
function renderTabla(){
    const POR=10,s=(_pag-1)*POR,pg=_all.slice(s,s+POR);
    _total=Math.max(1,Math.ceil(_all.length/POR));
    document.querySelector('#tablaData tbody').innerHTML=pg.length===0
        ?'<tr><td colspan="4" style="text-align:center;padding:30px;color:#aaa">Escriba al menos 3 caracteres para buscar entre los 2.133 despachos</td></tr>'
        :pg.map(d=>`<tr>
            <td style="font-size:12px">${d.nombre}</td>
            <td style="font-size:12px;font-family:monospace;color:#2980b9">${d.codigo_oficial||'—'}</td>
            <td style="font-size:12px;color:#7f8c8d">${d.departamento_nombre||'—'}${d.municipio_nombre&&d.municipio_nombre!==d.departamento_nombre?', '+d.municipio_nombre:''}</td>
            <td style="text-align:center;white-space:nowrap">
                <button class="btn-icon" onclick="ver(${d.id})" data-tooltip="Ver detalle"><i class="fas fa-eye"></i></button>
                <button class="btn-icon" onclick="editar(${d.id})" data-tooltip="Editar"><i class="fas fa-edit"></i></button>
                <button class="btn-icon" onclick="eliminar(${d.id})" data-tooltip="Eliminar"><i class="fas fa-trash"></i></button>
            </td></tr>`).join('');
    renderPag();
}
</script>

<div class="page-header">
    <h2>Despachos Judiciales <span id="totalBadge" style="font-size:12px;background:#eaf4fd;color:#2980b9;padding:2px 10px;border-radius:10px;font-weight:normal;margin-left:8px"></span></h2>
    <button class="btn btn-primary" onclick="abrirModal()">Nuevo Despacho</button>
</div>

<div style="display:flex;gap:8px;margin-bottom:14px">
    <div style="flex:1;position:relative">
        <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#aaa;font-size:13px"></i>
        <input type="text" id="buscador" placeholder="Buscar por nombre o código oficial (mín. 3 caracteres)..."
               style="width:100%;padding:8px 12px 8px 32px;border:2px solid #e0e0e0;border-radius:6px;font-size:13px"
               oninput="buscar(this.value)">
    </div>
    <button class="btn btn-secondary" onclick="limpiarBusqueda()" style="background:#95a5a6;color:white;padding:0 14px;border:none;border-radius:4px;cursor:pointer">
        <i class="fas fa-times"></i> Limpiar
    </button>
</div>

<table id="tablaData">
    <thead><tr>
        <th>Nombre del Despacho</th>
        <th style="width:150px">Código Oficial</th>
        <th style="width:200px">Ubicación</th>
        <th style="width:80px;text-align:center">Acciones</th>
    </tr></thead>
    <tbody></tbody>
</table>
<div id="paginacion" class="pagination-container" style="margin-top:16px;display:flex;justify-content:center;align-items:center;gap:10px"></div>

<div id="modalForm" class="modal">
    <div class="modal-content" style="max-width:560px">
        <span class="close" onclick="cerrarModal()">&times;</span>
        <h3 id="modalTitle">Nuevo Despacho</h3>
        <form onsubmit="guardar(event)">
            <input type="hidden" id="itemId">
            <div class="form-group">
                <label>Nombre: <span style="color:#e74c3c">*</span></label>
                <input type="text" id="nombre" required placeholder="Ej: Juzgado 001 Laboral del Circuito de Barranquilla">
            </div>
            <div class="form-group">
                <label>Código Oficial Rama Judicial:</label>
                <input type="text" id="codigo_oficial" placeholder="Ej: 080013105001C" maxlength="20"
                       style="font-family:monospace;letter-spacing:1px">
            </div>
            <div class="form-group">
                <label>Descripción:</label>
                <textarea id="descripcion" rows="2" placeholder="Notas opcionales"
                          style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:13px;resize:vertical"></textarea>
            </div>
            <div class="form-group">
                <label>Entidad:</label>
                <select id="entidad_id"><option value="">— Seleccionar —</option></select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label>Departamento:</label>
                    <select id="departamento_id" onchange="cargarMunis(this.value)"><option value="">— Seleccionar —</option></select>
                </div>
                <div class="form-group">
                    <label>Municipio:</label>
                    <select id="municipio_id"><option value="">— Seleccionar depto —</option></select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
        </form>
    </div>
</div>

<script>
const URL_C='/procesos_juridicos/backend/controllers/DespachoController.php';
const URL_E='/procesos_juridicos/backend/controllers/EntidadController.php';
const URL_D='/procesos_juridicos/backend/controllers/DepartamentoController.php';
const URL_M='/procesos_juridicos/backend/controllers/MunicipioController.php';
let searchTimeout;

function buscar(q){
    clearTimeout(searchTimeout);
    if(!q){_all=[];renderTabla();document.getElementById('totalBadge').textContent='';return;}
    if(q.length<3) return;
    searchTimeout=setTimeout(()=>{
        fetchWithAuth(`${URL_C}?action=search&q=${encodeURIComponent(q)}`).then(r=>r.json()).then(data=>{
            _all=data;_pag=1;
            document.getElementById('totalBadge').textContent=data.length+' resultado'+( data.length!==1?'s':'');
            renderTabla();
        });
    },350);
}

function limpiarBusqueda(){
    document.getElementById('buscador').value='';
    _all=[];renderTabla();document.getElementById('totalBadge').textContent='';
}

function cargarMunis(deptoId,selVal=''){
    const sel=document.getElementById('municipio_id');
    if(!deptoId){sel.innerHTML='<option value="">— Seleccionar depto —</option>';return Promise.resolve();}
    return fetchWithAuth(`${URL_M}?action=byDepartamento&departamento_id=${deptoId}`).then(r=>r.json()).then(data=>{
        sel.innerHTML='<option value="">— Seleccionar —</option>'+data.map(m=>`<option value="${m.id}" ${m.id==selVal?'selected':''}>${m.nombre}</option>`).join('');
    });
}

function cargarSelects(deptoId='',munId=''){
    return Promise.all([
        fetchWithAuth(URL_E+'?action=list').then(r=>r.json()),
        fetchWithAuth(URL_D+'?action=list').then(r=>r.json()),
    ]).then(([ents,deptos])=>{
        document.getElementById('entidad_id').innerHTML='<option value="">— Seleccionar —</option>'+ents.map(e=>`<option value="${e.id}">${e.nombre}</option>`).join('');
        document.getElementById('departamento_id').innerHTML='<option value="">— Seleccionar —</option>'+deptos.map(d=>`<option value="${d.id}" ${d.id==deptoId?'selected':''}>${d.nombre}</option>`).join('');
        if(deptoId) return cargarMunis(deptoId,munId);
    });
}

function abrirModal(){
    ['itemId','nombre','codigo_oficial','descripcion'].forEach(id=>document.getElementById(id).value='');
    document.getElementById('modalTitle').textContent='Nuevo Despacho';
    cargarSelects().then(()=>document.getElementById('modalForm').style.display='block');
}
function cerrarModal(){document.getElementById('modalForm').style.display='none';}

function editar(id){
    fetchWithAuth(`${URL_C}?action=get&id=${id}`).then(r=>r.json()).then(d=>{
        document.getElementById('itemId').value=d.id;
        document.getElementById('nombre').value=d.nombre;
        document.getElementById('codigo_oficial').value=d.codigo_oficial||'';
        document.getElementById('descripcion').value=d.descripcion||'';
        document.getElementById('modalTitle').textContent='Editar Despacho';
        cargarSelects(d.departamento_id,d.municipio_id).then(()=>{
            document.getElementById('entidad_id').value=d.entidad_id||'';
            document.getElementById('modalForm').style.display='block';
        });
    });
}

function guardar(e){
    e.preventDefault();
    const id=document.getElementById('itemId').value;
    const fd=new FormData();
    fd.append('action',id?'update':'create');
    if(id) fd.append('id',id);
    fd.append('nombre',          document.getElementById('nombre').value);
    fd.append('codigo_oficial',  document.getElementById('codigo_oficial').value);
    fd.append('descripcion',     document.getElementById('descripcion').value);
    fd.append('entidad_id',      document.getElementById('entidad_id').value);
    fd.append('departamento_id', document.getElementById('departamento_id').value);
    fd.append('municipio_id',    document.getElementById('municipio_id').value);
    fetchWithAuth(URL_C,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.success){cerrarModal();toast('Despacho guardado');buscar(document.getElementById('buscador').value);}
        else toast('Error al guardar','error');
    });
}

function eliminar(id){
    if(!confirm('¿Eliminar este despacho?'))return;
    const fd=new FormData();fd.append('action','delete');fd.append('id',id);
    fetchWithAuth(URL_C,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.success){_all=_all.filter(x=>x.id!==id);renderTabla();toast('Despacho eliminado','info');}
    });
}

renderTabla(); // muestra mensaje inicial

function ver(id){
    fetchWithAuth(`${URL_C}?action=get&id=${id}`).then(r=>r.json()).then(d=>{
        document.getElementById('verContenido').innerHTML=`
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:8px">
                <div style="background:#f8f9fa;padding:10px;border-radius:6px;grid-column:span 2">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#95a5a6;margin-bottom:4px">Nombre</div>
                    <div style="font-size:14px;font-weight:600;color:#2c3e50">${d.nombre}</div>
                </div>
                <div style="background:#eaf4fd;padding:10px;border-radius:6px;grid-column:span 2">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#95a5a6;margin-bottom:4px">Código Oficial Rama Judicial</div>
                    <div style="font-size:15px;font-family:monospace;color:#2980b9;font-weight:700">${d.codigo_oficial||'No asignado'}</div>
                </div>
                <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#95a5a6;margin-bottom:4px">Entidad</div>
                    <div style="font-size:13px">${d.entidad_nombre||'—'}</div>
                </div>
                <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#95a5a6;margin-bottom:4px">Departamento</div>
                    <div style="font-size:13px">${d.departamento_nombre||'—'}</div>
                </div>
                <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#95a5a6;margin-bottom:4px">Municipio</div>
                    <div style="font-size:13px">${d.municipio_nombre||'—'}</div>
                </div>
                ${d.descripcion?`<div style="background:#f8f9fa;padding:10px;border-radius:6px">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#95a5a6;margin-bottom:4px">Descripción</div>
                    <div style="font-size:13px">${d.descripcion}</div>
                </div>`:''}
            </div>`;
        document.getElementById('modalVer').style.display='block';
    });
}
</script>

<div id="modalVer" class="modal">
    <div class="modal-content" style="max-width:560px">
        <span class="close" onclick="document.getElementById('modalVer').style.display='none'">&times;</span>
        <h3>Detalle del Despacho</h3>
        <div id="verContenido" style="margin-top:10px"></div>
    </div>
</div>