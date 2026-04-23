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
        ?'<tr><td colspan="3" style="text-align:center;padding:30px;color:#aaa">No hay municipios</td></tr>'
        :pg.map(m=>`<tr>
            <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${m.nombre}</td>
            <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${m.departamento_nombre||'—'}</td>
            <td style="padding:8px 12px;text-align:center;white-space:nowrap">
                <button class="btn-icon" onclick="editar(${m.id})"><i class="fas fa-edit"></i></button>
                <button class="btn-icon" onclick="eliminar(${m.id})"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`).join('');
    renderPag();
}
</script>

<div class="page-header">
    <h2>Municipios</h2>
    <button class="btn btn-primary" onclick="abrirModal()">Nuevo Municipio</button>
</div>

<table id="tablaData">
    <thead><tr><th>Municipio</th><th>Departamento</th><th style="width:90px;text-align:center">Acciones</th></tr></thead>
    <tbody></tbody>
</table>
<div id="paginacion" class="pagination-container" style="margin-top:16px;display:flex;justify-content:center;align-items:center;gap:10px"></div>

<div id="modalForm" class="modal">
    <div class="modal-content" style="max-width:460px">
        <span class="close" onclick="cerrarModal()">&times;</span>
        <h3 id="modalTitle">Nuevo Municipio</h3>
        <form onsubmit="guardar(event)">
            <input type="hidden" id="itemId">
            <div class="form-group">
                <label>Departamento: <span style="color:#e74c3c">*</span></label>
                <div style="display:flex;gap:8px;align-items:center">
                    <select id="departamento_id" required style="flex:1"><option value="">— Seleccionar —</option></select>
                    <button type="button" onclick="abrirModalDepto()" class="btn-icon">
                        <i class="fas fa-plus" style="color:#3498db"></i>
                    </button>
                </div>
            </div>
            <div class="form-group"><label>Nombre: <span style="color:#e74c3c">*</span></label><input type="text" id="nombre" required placeholder="Ej: Barranquilla"></div>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</div>

<div id="modalDepto" class="modal">
    <div class="modal-content" style="max-width:380px">
        <span class="close" onclick="cerrarModalDepto()">&times;</span>
        <h3>Nuevo Departamento</h3>
        <form onsubmit="guardarDepto(event)">
            <div class="form-group"><label>Nombre:</label><input type="text" id="deptoNombre" required placeholder="Ej: Atlántico"></div>
            <button type="submit" class="btn btn-primary">Guardar Departamento</button>
        </form>
    </div>
</div>

<script>
const URL_M='/procesos_juridicos/backend/controllers/MunicipioController.php';
const URL_D='/procesos_juridicos/backend/controllers/DepartamentoController.php';

function cargarDeptos(selVal=''){
    return fetchWithAuth(URL_D+'?action=list').then(r=>r.json()).then(data=>{
        document.getElementById('departamento_id').innerHTML='<option value="">— Seleccionar —</option>'+
            data.map(d=>`<option value="${d.id}" ${d.id==selVal?'selected':''}>${d.nombre}</option>`).join('');
    });
}
function cargar(){fetchWithAuth(URL_M+'?action=list').then(r=>r.json()).then(d=>{_all=d;_pag=1;renderTabla();});}
function abrirModal(){document.getElementById('itemId').value='';document.getElementById('nombre').value='';document.getElementById('modalTitle').textContent='Nuevo Municipio';cargarDeptos().then(()=>document.getElementById('modalForm').style.display='block');}
function cerrarModal(){document.getElementById('modalForm').style.display='none';}
function editar(id){
    fetchWithAuth(`${URL_M}?action=get&id=${id}`).then(r=>r.json()).then(m=>{
        document.getElementById('itemId').value=m.id;document.getElementById('nombre').value=m.nombre;
        document.getElementById('modalTitle').textContent='Editar Municipio';
        cargarDeptos(m.departamento_id).then(()=>document.getElementById('modalForm').style.display='block');
    });
}
function guardar(e){
    e.preventDefault();const id=document.getElementById('itemId').value;const fd=new FormData();
    fd.append('action',id?'update':'create');if(id)fd.append('id',id);
    fd.append('departamento_id',document.getElementById('departamento_id').value);
    fd.append('nombre',document.getElementById('nombre').value);
    fetchWithAuth(URL_M,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success){cerrarModal();cargar();toast('Municipio guardado');}else toast('Error','error');});
}
function eliminar(id){
    if(!confirm('¿Eliminar este municipio?'))return;
    const fd=new FormData();fd.append('action','delete');fd.append('id',id);
    fetchWithAuth(URL_M,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success){cargar();toast('Municipio eliminado','info');}});
}
function abrirModalDepto(){document.getElementById('deptoNombre').value='';document.getElementById('modalDepto').style.display='block';}
function cerrarModalDepto(){document.getElementById('modalDepto').style.display='none';}
function guardarDepto(e){
    e.preventDefault();const fd=new FormData();fd.append('action','create');fd.append('nombre',document.getElementById('deptoNombre').value);
    fetchWithAuth(URL_D,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success){cerrarModalDepto();toast('Departamento creado');cargarDeptos(d.id);}else toast('Error','error');});
}
cargar();
</script>