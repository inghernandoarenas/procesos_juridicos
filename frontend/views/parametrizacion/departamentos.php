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
        ?'<tr><td colspan="2" style="padding:30px;text-align:center;color:#aaa">No hay departamentos</td></tr>'
        :pg.map(d=>`<tr>
            <td style="padding:8px 12px;font-size:12px;color:#2c3e50">${d.nombre}</td>
            <td style="padding:8px 12px;text-align:center;white-space:nowrap">
                <button class="btn-icon" onclick="editar(${d.id})"><i class="fas fa-edit"></i></button>
                <button class="btn-icon" onclick="eliminar(${d.id})"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`).join('');
    renderPag();
}
</script>

<div class="page-header">
    <h2>Departamentos</h2>
    <button class="btn btn-primary" onclick="abrirModal()">Nuevo Departamento</button>
</div>

<table id="tablaData">
    <thead><tr><th>Nombre</th><th style="width:90px;text-align:center">Acciones</th></tr></thead>
    <tbody></tbody>
</table>
<div id="paginacion" class="pagination-container" style="margin-top:16px;display:flex;justify-content:center;align-items:center;gap:10px"></div>

<div id="modalForm" class="modal">
    <div class="modal-content" style="max-width:420px">
        <span class="close" onclick="cerrarModal()">&times;</span>
        <h3 id="modalTitle">Nuevo Departamento</h3>
        <form onsubmit="guardar(event)">
            <input type="hidden" id="itemId">
            <div class="form-group"><label>Nombre:</label><input type="text" id="nombre" required placeholder="Ej: Atlántico"></div>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</div>

<script>
const URL_C='/procesos_juridicos/backend/controllers/DepartamentoController.php';
function cargar(){fetchWithAuth(URL_C+'?action=list').then(r=>r.json()).then(d=>{_all=d;_pag=1;renderTabla();});}
function abrirModal(){document.getElementById('itemId').value='';document.getElementById('nombre').value='';document.getElementById('modalTitle').textContent='Nuevo Departamento';document.getElementById('modalForm').style.display='block';}
function cerrarModal(){document.getElementById('modalForm').style.display='none';}
function editar(id){
    fetchWithAuth(`${URL_C}?action=get&id=${id}`).then(r=>r.json()).then(d=>{
        document.getElementById('itemId').value=d.id;document.getElementById('nombre').value=d.nombre;
        document.getElementById('modalTitle').textContent='Editar Departamento';document.getElementById('modalForm').style.display='block';
    });
}
function guardar(e){
    e.preventDefault();const id=document.getElementById('itemId').value;const fd=new FormData();
    fd.append('action',id?'update':'create');if(id)fd.append('id',id);fd.append('nombre',document.getElementById('nombre').value);
    fetchWithAuth(URL_C,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success){cerrarModal();cargar();toast('Departamento guardado');}else toast('Error al guardar','error');});
}
function eliminar(id){
    if(!confirm('¿Eliminar este departamento?'))return;
    const fd=new FormData();fd.append('action','delete');fd.append('id',id);
    fetchWithAuth(URL_C,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success){cargar();toast('Departamento eliminado','info');}});
}
cargar();
</script>