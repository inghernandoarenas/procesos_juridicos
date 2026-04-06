<style>
/* ══════════════════════════════════════════
   HONORARIOS - KPIs
══════════════════════════════════════════ */
.hon-resumen {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}

.hon-kpi {
    background: #fff;
    border: 1px solid #e8ecef;
    border-radius: 10px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,.05);
    transition: transform .15s, box-shadow .15s;
}

.hon-kpi:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0,0,0,.08);
}

.hon-kpi-icon {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.hon-kpi-body {
    flex: 1;
}

.hon-kpi-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #95a5a6;
    margin-bottom: 2px;
}

.hon-kpi-value {
    font-size: 18px;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1;
}

/* colores */
.hon-total     { background:#eef5ff; color:#3498db; }
.hon-pagado    { background:#eafaf1; color:#27ae60; }
.hon-pendiente { background:#fef9ec; color:#f39c12; }
.hon-vencido   { background:#fdecea; color:#e74c3c; }

/* ══════════════════════════════════════════
   ANEXOS
══════════════════════════════════════════ */
.anexos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 14px;
    padding: 4px 2px;
}
.anexo-card {
    background: #fff;
    border: 1px solid #e8ecef;
    border-radius: 10px;
    padding: 16px 14px 14px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    transition: box-shadow .2s, transform .2s;
    position: relative;
    cursor: default;
}
.anexo-card:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,.1);
    transform: translateY(-2px);
}
.anexo-icono {
    font-size: 40px;
    line-height: 1;
}
.anexo-nombre {
    font-size: 12px;
    font-weight: 600;
    color: #2c3e50;
    text-align: center;
    word-break: break-all;
    line-height: 1.4;
    max-height: 36px;
    overflow: hidden;
}
.anexo-meta {
    font-size: 10px;
    color: #95a5a6;
    text-align: center;
    line-height: 1.6;
}
.anexo-acciones {
    display: flex;
    gap: 6px;
    margin-top: 4px;
}
.anexo-btn {
    border: none;
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: opacity .2s;
}
.anexo-btn:hover { opacity: .8; }
.anexo-btn.descargar { background: #eaf4fd; color: #2980b9; }
.anexo-btn.eliminar  { background: #fdecea; color: #e74c3c; }

.anexo-tipo-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    font-size: 9px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: .3px;
}

/* Zona de drop */
.upload-zone {
    border: 2px dashed #d0d7de;
    border-radius: 10px;
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    margin-bottom: 20px;
    background: #fafbfc;
}
.upload-zone:hover, .upload-zone.dragover {
    border-color: #3498db;
    background: #eaf4fd;
}
.upload-zone i { font-size: 32px; color: #bdc3c7; margin-bottom: 8px; display: block; }
.upload-zone p { font-size: 13px; color: #7f8c8d; margin: 0; }
.upload-zone small { font-size: 11px; color: #bdc3c7; }
.upload-zone input[type=file] { display: none; }

.anexos-empty {
    text-align: center;
    padding: 40px 20px;
    color: #bdc3c7;
}
.anexos-empty i { font-size: 48px; display: block; margin-bottom: 12px; }
.anexos-empty p { font-size: 14px; }

.subiendo-progress {
    display: none;
    background: #eaf4fd;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 16px;
    font-size: 13px;
    color: #2980b9;
    align-items: center;
    gap: 10px;
}

/* ══════════════════════════════════════════
   VISTA DE PROCESOS — HEADER IMPACTANTE
══════════════════════════════════════════ */
.procesos-hero {
    background: linear-gradient(135deg, #1a2a3a 0%, #2c3e50 50%, #1a3a5c 100%);
    border-radius: 12px;
    padding: 24px 28px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(0,0,0,.15);
}
.procesos-hero-left h2 {
    color: white;
    font-size: 22px;
    font-weight: 700;
    margin: 0 0 4px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.procesos-hero-left p {
    color: rgba(255,255,255,.6);
    font-size: 13px;
    margin: 0;
}
.procesos-hero-stats {
    display: flex;
    gap: 20px;
}
.hero-stat {
    text-align: center;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 10px;
    padding: 12px 18px;
    min-width: 80px;
}
.hero-stat-num {
    font-size: 24px;
    font-weight: 700;
    color: white;
    line-height: 1;
    margin-bottom: 4px;
}
.hero-stat-label {
    font-size: 10px;
    color: rgba(255,255,255,.55);
    text-transform: uppercase;
    letter-spacing: .5px;
}
.procesos-hero-btn {
    background: #3498db;
    color: white;
    border: none;
    padding: 12px 22px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background .2s, transform .15s;
    white-space: nowrap;
}
.procesos-hero-btn:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

/* Tabla de procesos mejorada */
#tablaProcesos thead tr { background: linear-gradient(90deg, #2c3e50, #34495e); }
#tablaProcesos th { font-size: 11px; letter-spacing: .5px; text-transform: uppercase; }
#tablaProcesos tbody tr { transition: background .15s; }
#tablaProcesos tbody tr:hover { background: #f0f6ff; }
#tablaProcesos td { vertical-align: middle; }

/* Búsqueda en procesos más compacta */
.procesos-search-bar {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    align-items: center;
}
.procesos-search-bar input {
    flex: 1;
    padding: 10px 12px 10px 38px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 13px;
    transition: border-color .2s;
}
.procesos-search-bar input:focus {
    outline: none;
    border-color: #3498db;
}

/* ══════════════════════════════════════════
   TIMELINE DE ACTUACIONES
══════════════════════════════════════════ */
.timeline-wrap {
    padding: 10px 5px 10px 10px;
    max-height: 520px;
    overflow-y: auto;
}

.timeline {
    position: relative;
    padding-left: 36px;
}

/* Línea vertical central */
.timeline::before {
    content: '';
    position: absolute;
    left: 11px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #3498db, #e0e0e0);
    border-radius: 2px;
}

.tl-item {
    position: relative;
    margin-bottom: 28px;
    animation: fadeInUp .3s ease both;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Punto en la línea */
.tl-dot {
    position: absolute;
    left: -29px;
    top: 6px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #3498db;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #3498db;
    transition: transform .2s;
}

.tl-item:hover .tl-dot {
    transform: scale(1.3);
    background: #2980b9;
}

/* Primera actuación: punto dorado */
.tl-item:first-child .tl-dot {
    background: #f39c12;
    box-shadow: 0 0 0 2px #f39c12;
}

/* Tarjeta de cada actuación */
.tl-card {
    background: #fff;
    border: 1px solid #e8ecef;
    border-radius: 10px;
    padding: 14px 16px;
    box-shadow: 0 2px 6px rgba(0,0,0,.06);
    transition: box-shadow .2s, transform .2s;
    cursor: default;
}

.tl-card:hover {
    box-shadow: 0 6px 18px rgba(52,152,219,.15);
    transform: translateX(3px);
}

.tl-fecha {
    font-size: 11px;
    font-weight: 700;
    color: #3498db;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.tl-titulo {
    font-size: 14px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
    line-height: 1.4;
}

.tl-obs {
    font-size: 12px;
    color: #7f8c8d;
    line-height: 1.5;
    padding-top: 6px;
    border-top: 1px dashed #eee;
    margin-top: 6px;
}

.tl-badge-nueva {
    display: inline-block;
    background: #27ae60;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: .5px;
    vertical-align: middle;
}

.tl-empty {
    text-align: center;
    padding: 50px 20px;
    color: #aaa;
}

.tl-empty i { font-size: 40px; margin-bottom: 10px; display: block; }

/* Header del modal de actuaciones */
.tl-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.tl-contador {
    font-size: 12px;
    color: #7f8c8d;
    background: #f0f4f8;
    padding: 4px 12px;
    border-radius: 20px;
}
</style>

<script>
let paginaActualProcesos = 1;
let totalPaginasProcesos = 1;
let terminoBusqueda = '';
let procesoActual = 0;

function fetchWithAuth(url, options = {}) {
    const token = localStorage.getItem('token');
    if (!token) { window.location.href = '/procesos_juridicos/frontend/login.php'; return Promise.reject(); }
    options.headers = { ...options.headers, 'Authorization': 'Bearer ' + token };
    return fetch(url, options).then(r => {
        if (r.status === 401) { localStorage.clear(); window.location.href = '/procesos_juridicos/frontend/login.php'; }
        return r;
    });
}
</script>

<!-- ── Hero de Procesos ──────────────────────────────────── -->
<div class="procesos-hero">
    <div class="procesos-hero-left">
        <h2><i class="fas fa-gavel"></i> Gestión de Procesos</h2>
        <p>Seguimiento y control de expedientes judiciales</p>
    </div>
    <div class="procesos-hero-stats">
        <div class="hero-stat">
            <div class="hero-stat-num" id="heroTotal">—</div>
            <div class="hero-stat-label">Total</div>
        </div>
        <div class="hero-stat">
            <div class="hero-stat-num" id="heroActivos" style="color:#2ecc71">—</div>
            <div class="hero-stat-label">Activos</div>
        </div>
        <div class="hero-stat">
            <div class="hero-stat-num" id="heroVencen" style="color:#f39c12">—</div>
            <div class="hero-stat-label">Por vencer</div>
        </div>
    </div>
    <button class="procesos-hero-btn" onclick="abrirModalProceso()">
        <i class="fas fa-plus"></i> Nuevo Proceso
    </button>
</div>

<!-- ── Buscador ──────────────────────────────────────────── -->
<div class="procesos-search-bar">
    <div style="flex:1 1 auto;position:relative;min-width:400px;">
        <i class="fas fa-search" 
        style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#95a5a6;font-size:13px">
        </i>
        <input 
            type="text" 
            id="buscarProcesos" 
            placeholder="Buscar por radicado, cliente, tipo o descripción..."
            style="width:100%;padding-left:32px;"
        >
    </div>
    <button class="btn btn-primary" onclick="buscarProcesos()" style="padding:0 20px;height:40px">
        <i class="fas fa-search"></i> Buscar
    </button>
    <button class="btn btn-secondary" onclick="limpiarBusqueda()" style="padding:0 16px;background:#95a5a6;height:40px">
        <i class="fas fa-times"></i> Limpiar
    </button>
</div>

<!-- ══ TABLA ═════════════════════════════════════════════════ -->
<table id="tablaProcesos">
    <thead>
        <tr>
            <th>ID</th><th>Radicado</th><th>Cliente</th>
            <th>Tipo</th><th>Estado</th><th>Vencimiento</th><th>Acciones</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>
<div id="paginacionProcesos" class="pagination-container"
     style="margin-top:20px;display:flex;justify-content:center;align-items:center;gap:10px"></div>

<!-- ══ MODAL CREAR / EDITAR PROCESO ══════════════════════════ -->
<div id="modalProceso" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModalProceso()">&times;</span>
        <h3 id="modalProcesoTitle">Nuevo Proceso</h3>
        <form id="formProceso" onsubmit="guardarProceso(event)">
            <input type="hidden" id="procesoId" name="id">
            <div class="form-group">
                <label>Cliente:</label>
                <div style="display:flex;gap:10px;align-items:center">
                    <select id="cliente_id" name="cliente_id" required style="flex:1">
                        <option value="">Seleccione un cliente</option>
                    </select>
                    <button type="button" class="btn-icon" onclick="abrirModalClienteRapido()" data-tooltip="Nuevo cliente">
                        <i class="fas fa-plus" style="color:#3752e8"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label>Número de Radicado:</label>
                <input type="text" id="numero_radicado" name="numero_radicado" required>
            </div>
            <div class="form-group">
                <label>Tipo de Proceso:</label>
                <select id="tipo_proceso_id" name="tipo_proceso_id" required>
                    <option value="">Seleccione tipo</option>
                </select>
            </div>
            <div class="form-group">
                <label>Descripción:</label>
                <textarea id="descripcion" name="descripcion" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Estado:</label>
                <select id="estado_proceso_id" name="estado_proceso_id" required>
                    <option value="">Seleccione estado</option>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha de Inicio:</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" required>
            </div>
            <div class="form-group">
                <label>Fecha de Vencimiento:</label>
                <input type="date" id="fecha_vencimiento" name="fecha_vencimiento">
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</div>

<!-- ══ MODAL VER PROCESO ══════════════════════════════════════ -->
<div id="modalVerProceso" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModalVer()">&times;</span>
        <h3>Detalles del Proceso</h3>
        <div id="detalleProceso"></div>
    </div>
</div>

<!-- ══ MODAL ANEXOS ══════════════════════════════════════════ -->
<div id="modalAnexos" class="modal">
    <div class="modal-content" style="width:88%;max-width:980px">
        <span class="close" onclick="cerrarModalAnexos()">&times;</span>

        <div style="margin-top:30px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <div>
                    <h3 style="margin:0">Documentos del Proceso</h3>
                    <p id="anexosSubtitulo" style="margin:4px 0 0;font-size:13px;color:#7f8c8d"></p>
                </div>
            </div>

            <input type="hidden" id="anexoProcesoId" name="proceso_id">

            <!-- Barra de progreso -->
            <div class="subiendo-progress" id="subiendoProgress">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Subiendo archivo, por favor espere...</span>
            </div>

            <!-- Zona de subida con selector de categoría -->
            <div style="background:#f8f9fa;border-radius:10px;padding:16px;margin-bottom:20px">
                <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
                    <div style="flex:1;min-width:200px">
                        <label style="display:block;font-size:11px;font-weight:700;color:#7f8c8d;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px">
                            Categoría
                        </label>
                        <select id="categoriaAnexo"
                                style="width:100%;padding:10px 12px;border:2px solid #e0e0e0;border-radius:7px;font-size:13px;color:#2c3e50">
                            <option value="">-- Sin categoría --</option>
                        </select>
                    </div>
                    <div style="flex:2;min-width:250px">
                        <label style="display:block;font-size:11px;font-weight:700;color:#7f8c8d;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px">
                            Archivo
                        </label>
                        <div class="upload-zone" id="uploadZone"
                             style="padding:14px 20px;margin-bottom:0"
                             onclick="document.getElementById('archivoInput').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size:22px;margin-bottom:4px"></i>
                            <p style="font-size:13px;margin:0"><strong>Clic aquí</strong> o arrastra un archivo</p>
                            <small>PDF, Word, Excel, imágenes — máx. 10MB</small>
                            <form id="formAnexo">
                                <input type="file" id="archivoInput" name="archivo"
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.zip,.rar,.txt">
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtro por categoría -->
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                <label style="font-size:12px;color:#7f8c8d;white-space:nowrap">Filtrar por:</label>
                <select id="filtroCategoria" onchange="filtrarAnexos()"
                        style="padding:6px 10px;border:1px solid #e0e0e0;border-radius:6px;font-size:13px;color:#2c3e50">
                    <option value="">Todas las categorías</option>
                </select>
                <span id="anexosSubtituloCount" style="font-size:12px;color:#95a5a6;margin-left:auto"></span>
            </div>

            <!-- Grid de archivos agrupado por categoría -->
            <div id="listaAnexos"></div>
        </div>
    </div>
</div>

<!-- ══ MODAL ACTUACIONES (con Timeline) ══════════════════════ -->
<div id="modalActuaciones" class="modal">
    <div class="modal-content" style="width:80%;max-width:800px">
        <span class="close" onclick="cerrarModalActuaciones()">&times;</span>

        <div style="margin-top:30px">
            <div class="tl-header">
                <div>
                    <h3 style="margin:0">Actuaciones del Proceso</h3>
                    <div id="procesoInfo" style="margin-top:6px;font-size:13px;color:#7f8c8d"></div>
                </div>
                <div style="display:flex;align-items:center;gap:10px">
                    <span id="tlContador" class="tl-contador"></span>
                    <button class="btn btn-primary" onclick="sincronizarRama()" id="btnSincronizar">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                </div>
            </div>
        </div>

        <div class="timeline-wrap">
            <div id="timelineActuaciones"></div>
        </div>
    </div>
</div>

<!-- ══ MODAL CLIENTE RÁPIDO ══════════════════════════════════ -->
<div id="modalClienteRapido" class="modal">
    <div class="modal-content" style="max-width:500px">
        <span class="close" onclick="cerrarModalClienteRapido()">&times;</span>
        <h3>Nuevo Cliente</h3>
        <form id="formClienteRapido" onsubmit="guardarClienteRapido(event)">
            <div class="form-group"><label>Nombre:</label><input type="text" id="nombre_rapido" name="nombre" required></div>
            <div class="form-group"><label>Apellido:</label><input type="text" id="apellido_rapido" name="apellido" required></div>
            <div class="form-group"><label>Email:</label><input type="email" id="email_rapido" name="email"></div>
            <div class="form-group"><label>Teléfono:</label><input type="text" id="telefono_rapido" name="telefono" pattern="[0-9]{7,10}" maxlength="10"></div>
            <div class="form-group"><label>Dirección:</label><textarea id="direccion_rapido" name="direccion" rows="2"></textarea></div>
            <button type="submit" class="btn btn-primary">Guardar Cliente</button>
        </form>
    </div>
</div>

<!-- ══ MODAL HONORARIOS ══════════════════════════════════════ -->
<div id="modalHonorarios" class="modal">
    <div class="modal-content" style="width:90%;max-width:1000px">
        <span class="close" onclick="cerrarHonorarios()">&times;</span>

        <div style="margin-top:30px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <div>
                    <h3 style="margin:0"><i class="fas fa-dollar-sign" style="color:#27ae60;margin-right:8px"></i>Honorarios del Proceso</h3>
                    <p id="honSubtitulo" style="margin:4px 0 0;font-size:13px;color:#7f8c8d"></p>
                </div>
                <button class="btn btn-primary" onclick="abrirFormHonorario()"
                        style="background:#27ae60;border-color:#27ae60;display:flex;align-items:center;gap:6px">
                    <i class="fas fa-plus"></i> Nuevo Cobro
                </button>
            </div>

            <!-- KPIs resumen -->
            <div class="hon-resumen" id="honKpis"></div>

            <!-- Formulario inline (oculto por defecto) -->
            <div id="honFormWrap" style="display:none;background:#f8f9fa;border-radius:10px;padding:18px;margin-bottom:18px;border-left:4px solid #27ae60">
                <h4 style="margin:0 0 14px;color:#2c3e50;font-size:14px" id="honFormTitulo">Nuevo Cobro</h4>
                <input type="hidden" id="honId">
                <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px;margin-bottom:12px">
                    <div class="form-group" style="margin:0">
                        <label style="font-size:11px;font-weight:700;color:#7f8c8d;text-transform:uppercase">Concepto *</label>
                        <input type="text" id="honConcepto" placeholder="Ej: Honorarios audiencia inicial"
                               style="margin-top:4px">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label style="font-size:11px;font-weight:700;color:#7f8c8d;text-transform:uppercase">Tipo *</label>
                        <select id="honTipo" style="margin-top:4px">
                            <option value="pago_puntual">Pago puntual</option>
                            <option value="cuota_periodica">Cuota periódica</option>
                            <option value="honorario_exito">Honorario de éxito</option>
                            <option value="anticipo">Anticipo</option>
                            <option value="gasto_reembolsable">Gasto reembolsable</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label style="font-size:11px;font-weight:700;color:#7f8c8d;text-transform:uppercase">Valor (COP) *</label>
                        <input type="number" id="honValor" placeholder="0" min="0" step="1000"
                               style="margin-top:4px">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 2fr;gap:12px;margin-bottom:14px">
                    <div class="form-group" style="margin:0">
                        <label style="font-size:11px;font-weight:700;color:#7f8c8d;text-transform:uppercase">Fecha causación *</label>
                        <input type="date" id="honFechaCausacion" style="margin-top:4px">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label style="font-size:11px;font-weight:700;color:#7f8c8d;text-transform:uppercase">Fecha pago</label>
                        <input type="date" id="honFechaPago" style="margin-top:4px">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label style="font-size:11px;font-weight:700;color:#7f8c8d;text-transform:uppercase">Estado</label>
                        <select id="honEstado" style="margin-top:4px">
                            <option value="pendiente">Pendiente</option>
                            <option value="pagado">Pagado</option>
                            <option value="vencido">Vencido</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label style="font-size:11px;font-weight:700;color:#7f8c8d;text-transform:uppercase">Observaciones</label>
                        <input type="text" id="honObs" placeholder="Opcional" style="margin-top:4px">
                    </div>
                </div>
                <div style="display:flex;gap:8px">
                    <button class="btn btn-primary" onclick="guardarHonorario()"
                            style="background:#27ae60;border-color:#27ae60">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button class="btn btn-secondary" onclick="cancelarFormHonorario()">
                        Cancelar
                    </button>
                </div>
            </div>

            <!-- Tabla de honorarios -->
            <div id="honTablaWrap">
                <table class="hon-tabla">
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Causación</th>
                            <th>Pago</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="honTbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ══ SCRIPTS ════════════════════════════════════════════════ -->
<script>

// ── Actuaciones / Timeline ────────────────────────────────────
function verActuaciones(procesoId) {
    procesoActual = procesoId;
    fetchWithAuth(`/procesos_juridicos/backend/controllers/ProcesoController.php?action=get&id=${procesoId}`)
        .then(r => r.json())
        .then(p => {
            document.getElementById('procesoInfo').innerHTML =
                `<i class="fas fa-gavel"></i> <strong>${p.numero_radicado}</strong>
                 &nbsp;·&nbsp; ${p.nombre} ${p.apellido}`;
        });
    cargarTimeline(procesoId);
    document.getElementById('modalActuaciones').style.display = 'block';
}

function cargarTimeline(procesoId) {
    const contenedor = document.getElementById('timelineActuaciones');
    contenedor.innerHTML = '<div class="tl-empty"><i class="fas fa-spinner fa-spin"></i>Cargando...</div>';

    fetchWithAuth(`/procesos_juridicos/backend/controllers/ActuacionController.php?action=list&proceso_id=${procesoId}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('tlContador').textContent =
                data.length === 0 ? 'Sin actuaciones' : `${data.length} actuación${data.length !== 1 ? 'es' : ''}`;

            if (data.length === 0) {
                contenedor.innerHTML = `
                    <div class="tl-empty">
                        <i class="fas fa-folder-open"></i>
                        No hay actuaciones registradas para este proceso
                    </div>`;
                return;
            }

            // Ordenar de más reciente a más antigua
            data.sort((a, b) => new Date(b.fecha) - new Date(a.fecha));

            const hoy   = new Date();
            hoy.setHours(0,0,0,0);
            const ayer  = new Date(hoy); ayer.setDate(ayer.getDate() - 1);
            const items = data.map((a, idx) => {
                const fechaAct  = new Date(a.fecha);
                const esNueva   = fechaAct >= ayer;
                const fechaFmt  = fechaAct.toLocaleDateString('es-CO', {
                    weekday: 'short', year: 'numeric', month: 'short', day: 'numeric'
                });

                return `
                <div class="tl-item" style="animation-delay:${idx * 0.05}s">
                    <div class="tl-dot"></div>
                    <div class="tl-card">
                        <div class="tl-fecha">
                            <i class="fas fa-calendar-day"></i>
                            ${fechaFmt}
                            ${esNueva ? '<span class="tl-badge-nueva">nueva</span>' : ''}
                        </div>
                        <div class="tl-titulo">${a.actuacion}</div>
                        ${a.observaciones
                            ? `<div class="tl-obs"><i class="fas fa-comment-alt" style="margin-right:5px;color:#bdc3c7"></i>${a.observaciones}</div>`
                            : ''}
                    </div>
                </div>`;
            }).join('');

            contenedor.innerHTML = `<div class="timeline">${items}</div>`;
        });
}

function sincronizarRama() {
    const btn = document.getElementById('btnSincronizar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sincronizando...';

    const fd = new FormData();
    fd.append('action', 'sincronizar');
    fd.append('proceso_id', procesoActual);

    fetchWithAuth('/procesos_juridicos/backend/controllers/SincronizarRamaController.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            toast(data.success ? data.message : 'Error: ' + data.message, data.success ? 'success' : 'error', 4000);
            cargarTimeline(procesoActual);
        })
        .catch(() => {
            toast('Sincronización completada');
            cargarTimeline(procesoActual);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Actualizar';
        });
}

function cerrarModalActuaciones() {
    document.getElementById('modalActuaciones').style.display = 'none';
}

// ── Procesos ──────────────────────────────────────────────────
function buscarProcesos() {
    terminoBusqueda = document.getElementById('buscarProcesos').value.trim();
    paginaActualProcesos = 1;
    cargarProcesos(1, terminoBusqueda);
}

function limpiarBusqueda() {
    document.getElementById('buscarProcesos').value = '';
    terminoBusqueda = '';
    cargarProcesos(1, '');
}

function cargarProcesos(pagina = 1, buscar = '') {
    let url = `/procesos_juridicos/backend/controllers/ProcesoController.php?action=list&pagina=${pagina}`;
    if (buscar) url += `&buscar=${encodeURIComponent(buscar)}`;

    fetchWithAuth(url).then(r => r.json()).then(result => {
        paginaActualProcesos = result.pagina;
        totalPaginasProcesos = result.total_paginas;

        const tbody = document.querySelector('#tablaProcesos tbody');
        tbody.innerHTML = '';

        // Actualizar stats del hero
        document.getElementById('heroTotal').textContent = result.total || 0;

        if (result.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:30px">No se encontraron procesos</td></tr>';
            document.getElementById('paginacionProcesos').innerHTML = '';
            return;
        }

        // Contar activos y por vencer en la página actual
        const hoy = new Date();
        const en15dias = new Date(); en15dias.setDate(en15dias.getDate() + 15);
        const activos  = result.data.filter(p => p.estado_proceso_nombre && p.estado_proceso_nombre.toLowerCase().includes('activ')).length;
        const porVencer = result.data.filter(p => {
            if (!p.fecha_vencimiento) return false;
            const fv = new Date(p.fecha_vencimiento);
            return fv >= hoy && fv <= en15dias;
        }).length;
        document.getElementById('heroActivos').textContent = activos;
        document.getElementById('heroVencen').textContent  = porVencer;

        result.data.forEach(p => {

            const rad = (p.numero_radicado || '')
                .replace(/'/g, "\\'")
                .replace(/"/g, "&quot;");

            tbody.innerHTML += `
                <tr>
                    <td>${p.id}</td>
                    <td>${p.numero_radicado}</td>
                    <td>${p.nombre} ${p.apellido}</td>
                    <td>${p.tipo_proceso_nombre || p.tipo_proceso || '—'}</td>
                    <td>${p.estado_proceso_nombre || p.estado || '—'}</td>
                    <td>${p.fecha_vencimiento || 'N/A'}</td>
                    <td>
                        <button class="btn-icon" onclick="verProceso(${p.id})" data-tooltip="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>

                        <button class="btn-icon" onclick="verActuaciones(${p.id})" data-tooltip="Timeline">
                            <i class="fas fa-stream"></i>
                        </button>

                        <button class="btn-icon" onclick="editarProceso(${p.id})" data-tooltip="Editar">
                            <i class="fas fa-edit"></i>
                        </button>

                        <button class="btn-icon" onclick="abrirModalAnexos(${p.id})" data-tooltip="Anexos">
                            <i class="fas fa-paperclip"></i>
                        </button>

                        <button class="btn-icon" 
                                onclick="abrirHonorarios(${p.id}, '${rad}')" 
                                data-tooltip="Honorarios">
                            <i class="fas fa-dollar-sign" style="color:#27ae60"></i>
                        </button>

                        <button class="btn-icon" onclick="abrirReporte(${p.id})" data-tooltip="Generar reporte PDF">
                            <i class="fas fa-file-pdf" style="color:#e74c3c"></i>
                        </button>

                        <button class="btn-icon" onclick="eliminarProceso(${p.id})" data-tooltip="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
        });
        renderPaginacionProcesos();
    });
}

function cargarClientesSelect() {
    const sel = document.getElementById('cliente_id');
    const valorActual = sel.value;
    return fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php?action=getClientes')
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">Seleccione un cliente</option>';
            data.forEach(c => sel.innerHTML += `<option value="${c.id}">${c.nombre} ${c.apellido}</option>`);
            if (valorActual) sel.value = valorActual;
        });
}

function abrirModalProceso() {
    Promise.all([cargarClientesSelect(), cargarTiposProceso(), cargarEstadosProceso()]).then(() => {
        document.getElementById('formProceso').reset();
        document.getElementById('procesoId').value = '';
        document.getElementById('modalProcesoTitle').textContent = 'Nuevo Proceso';
        document.getElementById('modalProceso').style.display = 'block';
    });
}

function cerrarModalProceso() { document.getElementById('modalProceso').style.display = 'none'; }

function guardarProceso(event) {
    event.preventDefault();
    const fi = document.getElementById('fecha_inicio').value;
    const fv = document.getElementById('fecha_vencimiento').value;
    if (fv && new Date(fv) < new Date(fi)) {
        alert('La fecha de vencimiento no puede ser menor a la fecha de inicio');
        return;
    }
    const fd = new FormData(document.getElementById('formProceso'));
    fd.append('action', document.getElementById('procesoId').value ? 'update' : 'create');
    fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => { if (data.success) { cerrarModalProceso(); cargarProcesos(1, terminoBusqueda); toast('Proceso guardado correctamente'); } else { toast('Error al guardar el proceso','error'); } });
}

function editarProceso(id) {
    Promise.all([cargarClientesSelect(), cargarTiposProceso(), cargarEstadosProceso()])
        .then(() => fetchWithAuth(`/procesos_juridicos/backend/controllers/ProcesoController.php?action=get&id=${id}`))
        .then(r => r.json())
        .then(p => {
            document.getElementById('procesoId').value          = p.id;
            document.getElementById('cliente_id').value         = p.cliente_id;
            document.getElementById('numero_radicado').value    = p.numero_radicado;
            document.getElementById('tipo_proceso_id').value    = p.tipo_proceso_id;
            document.getElementById('estado_proceso_id').value  = p.estado_proceso_id;
            document.getElementById('descripcion').value        = p.descripcion || '';
            document.getElementById('fecha_inicio').value       = p.fecha_inicio;
            document.getElementById('fecha_vencimiento').value  = p.fecha_vencimiento || '';
            document.getElementById('modalProcesoTitle').textContent = 'Editar Proceso';
            document.getElementById('modalProceso').style.display = 'block';
        });
}

function verProceso(id) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/ProcesoController.php?action=get&id=${id}`)
        .then(r => r.json())
        .then(p => {
            document.getElementById('detalleProceso').innerHTML = `
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:15px;padding:10px">
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">ID Proceso</strong>
                        <span style="font-size:16px">${p.id}</span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Radicado</strong>
                        <span style="font-size:16px;font-weight:bold;color:#3498db">${p.numero_radicado}</span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px;grid-column:span 2">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Cliente</strong>
                        <span style="font-size:16px">${p.nombre} ${p.apellido}</span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Tipo de Proceso</strong>
                        <span style="font-size:16px">${p.tipo_proceso_nombre || '—'}</span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Estado</strong>
                        <span style="font-size:14px;padding:4px 10px;border-radius:4px;background:${p.estado_color||'#3498db'};color:white;display:inline-block">
                            ${p.estado_proceso_nombre || '—'}
                        </span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Fecha Inicio</strong>
                        <span style="font-size:16px">${p.fecha_inicio}</span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Fecha Vencimiento</strong>
                        <span style="font-size:16px;${p.fecha_vencimiento && new Date(p.fecha_vencimiento)<new Date()?'color:#e74c3c;font-weight:bold':''}">
                            ${p.fecha_vencimiento || 'N/A'}
                        </span>
                    </div>
                    <div style="background:#f8f9fa;padding:10px;border-radius:6px;grid-column:span 2">
                        <strong style="color:#2c3e50;display:block;font-size:12px;text-transform:uppercase">Descripción</strong>
                        <span style="font-size:14px;line-height:1.5">${p.descripcion || 'Sin descripción'}</span>
                    </div>
                </div>`;
            document.getElementById('modalVerProceso').style.display = 'block';
        });
}

function cerrarModalVer() { document.getElementById('modalVerProceso').style.display = 'none'; }

function eliminarProceso(id) {
    if (confirm('¿Está seguro de eliminar este proceso?')) {
        const fd = new FormData();
        fd.append('action','delete'); fd.append('id', id);
        fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php', { method:'POST', body:fd })
            .then(r => r.json())
            .then(data => { if (data.success) { cargarProcesos(1, terminoBusqueda); toast('Proceso eliminado','info'); } });
    }
}

// ── Anexos ────────────────────────────────────────────────────
let anexosData    = [];
let anexosProceso = 0;

function getIconoAnexo(tipo, nombre) {
    const t = (tipo || '').toLowerCase();
    const n = (nombre || '').toLowerCase();
    if (t.includes('pdf') || n.endsWith('.pdf'))
        return { icono: '📄', badge: 'PDF',   bg: '#fdecea', fg: '#e74c3c' };
    if (t.includes('word') || n.endsWith('.doc') || n.endsWith('.docx'))
        return { icono: '📝', badge: 'Word',  bg: '#eaf4fd', fg: '#2980b9' };
    if (t.includes('excel') || t.includes('spreadsheet') || n.endsWith('.xls') || n.endsWith('.xlsx'))
        return { icono: '📊', badge: 'Excel', bg: '#eafaf1', fg: '#27ae60' };
    if (t.includes('image') || n.match(/\.(jpg|jpeg|png|gif|webp)$/))
        return { icono: '🖼️', badge: 'IMG',   bg: '#f5eef8', fg: '#8e44ad' };
    if (t.includes('zip') || t.includes('rar') || n.match(/\.(zip|rar|7z)$/))
        return { icono: '🗜️', badge: 'ZIP',   bg: '#fef9ec', fg: '#f39c12' };
    if (t.includes('text') || n.endsWith('.txt'))
        return { icono: '📃', badge: 'TXT',   bg: '#f2f3f4', fg: '#7f8c8d' };
    return     { icono: '📁', badge: 'FILE',  bg: '#eaf4fd', fg: '#3498db' };
}

function abrirModalAnexos(id) {
    anexosProceso = id;
    document.getElementById('anexoProcesoId').value = id;
    document.getElementById('modalAnexos').style.display = 'block';

    cargarCategorias().then(() => cargarAnexos(id));

    const zone = document.getElementById('uploadZone');
    zone.ondragover  = e => { e.preventDefault(); zone.classList.add('dragover'); };
    zone.ondragleave = () => zone.classList.remove('dragover');
    zone.ondrop = e => {
        e.preventDefault();
        zone.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file) subirArchivo(file, id);
    };
    document.getElementById('archivoInput').onchange = e => {
        const file = e.target.files[0];
        if (file) subirArchivo(file, id);
    };
}

function cerrarModalAnexos() {
    document.getElementById('modalAnexos').style.display = 'none';
    document.getElementById('archivoInput').value = '';
    document.getElementById('filtroCategoria').value = '';
}

function cargarCategorias() {
    return fetchWithAuth('/procesos_juridicos/backend/controllers/AnexoController.php?action=categorias')
        .then(r => r.json())
        .then(cats => {
            const selSubida = document.getElementById('categoriaAnexo');
            const selFiltro = document.getElementById('filtroCategoria');
            selSubida.innerHTML = '<option value="">-- Sin categoría --</option>';
            selFiltro.innerHTML = '<option value="">Todas las categorías</option>';
            cats.forEach(c => {
                selSubida.innerHTML += `<option value="${c.id}">${c.nombre}</option>`;
                selFiltro.innerHTML += `<option value="${c.id}">${c.nombre}</option>`;
            });
        });
}

function subirArchivo(file, procesoId) {
    if (file.size > 10 * 1024 * 1024) {
        toast('El archivo supera el límite de 10MB', 'error');
        return;
    }
    const progress    = document.getElementById('subiendoProgress');
    const categoriaId = document.getElementById('categoriaAnexo').value;
    progress.style.display = 'flex';

    const fd = new FormData();
    fd.append('action',       'upload');
    fd.append('proceso_id',   procesoId);
    fd.append('categoria_id', categoriaId);
    fd.append('archivo',      file);

    fetchWithAuth('/procesos_juridicos/backend/controllers/AnexoController.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            progress.style.display = 'none';
            document.getElementById('archivoInput').value = '';
            if (data.success) { cargarAnexos(procesoId); toast('Archivo subido correctamente'); }
            else { toast('Error al subir el archivo', 'error'); }
        })
        .catch(() => { progress.style.display = 'none'; });
}

function cargarAnexos(procesoId) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/AnexoController.php?action=list&proceso_id=${procesoId}`)
        .then(r => r.json())
        .then(data => {
            anexosData = data;
            document.getElementById('anexosSubtitulo').textContent =
                data.length === 0
                    ? 'Sin documentos adjuntos'
                    : `${data.length} documento${data.length !== 1 ? 's' : ''} adjunto${data.length !== 1 ? 's' : ''}`;
            renderAnexos(data);
        });
}

function filtrarAnexos() {
    const catId    = document.getElementById('filtroCategoria').value;
    const filtrados = catId ? anexosData.filter(a => String(a.categoria_id) === catId) : anexosData;
    renderAnexos(filtrados);
}

function renderAnexos(data) {
    const contenedor = document.getElementById('listaAnexos');
    const count      = document.getElementById('anexosSubtituloCount');
    count.textContent = data.length !== anexosData.length
        ? `Mostrando ${data.length} de ${anexosData.length}`
        : '';

    if (data.length === 0) {
        contenedor.innerHTML =
            `<div class="anexos-empty">
                <i class="fas fa-folder-open"></i>
                <p>${anexosData.length === 0 ? 'No hay documentos adjuntos para este proceso' : 'No hay documentos en esta categoría'}</p>
            </div>`;
        return;
    }

    // Agrupar por categoría
    const grupos = {};
    data.forEach(a => {
        const cat = a.categoria_nombre || 'Sin categoría';
        if (!grupos[cat]) grupos[cat] = [];
        grupos[cat].push(a);
    });

    let html = '';
    Object.keys(grupos).sort().forEach(cat => {
        const items = grupos[cat].map(a => {
            const info  = getIconoAnexo(a.tipo_archivo, a.nombre_archivo);
            const fecha = new Date(a.fecha_subida).toLocaleDateString('es-CO',
                { day:'2-digit', month:'short', year:'numeric' });
            const nombre = a.nombre_archivo.length > 22
                ? a.nombre_archivo.substring(0, 20) + '…'
                : a.nombre_archivo;
            return `
            <div class="anexo-card">
                <span class="anexo-tipo-badge" style="background:${info.bg};color:${info.fg}">${info.badge}</span>
                <div class="anexo-icono">${info.icono}</div>
                <div class="anexo-nombre" title="${a.nombre_archivo}">${nombre}</div>
                <div class="anexo-meta">${fecha}</div>
                <div class="anexo-acciones">
                    <a href="/procesos_juridicos/${a.ruta_archivo}" target="_blank"
                       class="anexo-btn descargar" download="${a.nombre_archivo}">
                        <i class="fas fa-download"></i> Ver
                    </a>
                    <button class="anexo-btn eliminar" onclick="eliminarAnexo(${a.id}, ${anexosProceso})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>`;
        }).join('');

        html += `
        <div style="margin-bottom:20px">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;padding-bottom:6px;border-bottom:2px solid #eef2f7">
                <i class="fas fa-folder" style="color:#3498db;font-size:13px"></i>
                <span style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#2c3e50">${cat}</span>
                <span style="font-size:11px;color:#95a5a6;background:#f0f4f8;padding:1px 8px;border-radius:10px">${grupos[cat].length}</span>
            </div>
            <div class="anexos-grid">${items}</div>
        </div>`;
    });

    contenedor.innerHTML = html;
}

function eliminarAnexo(id, procesoId) {
    if (confirm('¿Eliminar este documento?')) {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        fetchWithAuth('/procesos_juridicos/backend/controllers/AnexoController.php', { method:'POST', body:fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) { cargarAnexos(procesoId); toast('Archivo eliminado', 'info'); }
            });
    }
}

// ── Cliente rápido ────────────────────────────────────────────
function abrirModalClienteRapido() {
    document.getElementById('formClienteRapido').reset();
    document.getElementById('modalClienteRapido').style.display = 'block';
}

function cerrarModalClienteRapido() { document.getElementById('modalClienteRapido').style.display = 'none'; }

function guardarClienteRapido(event) {
    event.preventDefault();
    const fd = new FormData(document.getElementById('formClienteRapido'));
    fd.append('action','create');
    fetchWithAuth('/procesos_juridicos/backend/controllers/ClienteController.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) { cerrarModalClienteRapido(); cargarClientesSelect(); toast('Cliente creado exitosamente'); }
            else { toast('Error al crear el cliente','error'); }
        });
}

// ── Selects ───────────────────────────────────────────────────
function cargarTiposProceso() {
    return fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php?action=getTipos')
        .then(r => r.json())
        .then(data => {
            const sel = document.getElementById('tipo_proceso_id');
            sel.innerHTML = '<option value="">Seleccione tipo</option>';
            data.forEach(t => sel.innerHTML += `<option value="${t.id}">${t.nombre}</option>`);
        });
}

function cargarEstadosProceso() {
    return fetchWithAuth('/procesos_juridicos/backend/controllers/ProcesoController.php?action=getEstados')
        .then(r => r.json())
        .then(data => {
            const sel = document.getElementById('estado_proceso_id');
            sel.innerHTML = '<option value="">Seleccione estado</option>';
            data.forEach(e => sel.innerHTML += `<option value="${e.id}">${e.nombre}</option>`);
        });
}

// ── Paginación ────────────────────────────────────────────────
function renderPaginacionProcesos() {
    document.getElementById('paginacionProcesos').innerHTML = `
        <button class="pagination-btn" onclick="cambiarPaginaProcesos(${paginaActualProcesos-1})" ${paginaActualProcesos<=1?'disabled':''}>
            <i class="fas fa-chevron-left"></i>
        </button>
        <span class="pagination-info">Página ${paginaActualProcesos} de ${totalPaginasProcesos}</span>
        <button class="pagination-btn" onclick="cambiarPaginaProcesos(${paginaActualProcesos+1})" ${paginaActualProcesos>=totalPaginasProcesos?'disabled':''}>
            <i class="fas fa-chevron-right"></i>
        </button>`;
}

function cambiarPaginaProcesos(p) {
    if (p >= 1 && p <= totalPaginasProcesos) cargarProcesos(p, terminoBusqueda);
}

// ── Búsqueda con debounce ─────────────────────────────────────
let timeoutBusqueda;
document.getElementById('buscarProcesos').addEventListener('input', function() {
    clearTimeout(timeoutBusqueda);
    timeoutBusqueda = setTimeout(buscarProcesos, 500);
});
document.getElementById('buscarProcesos').addEventListener('keyup', e => {
    if (e.key === 'Enter') buscarProcesos();
});

// ── Reporte PDF ──────────────────────────────────────────────
function abrirReporte(id) {
    window.open(`/procesos_juridicos/backend/reportes/reporte_proceso.php?id=${id}`, '_blank');
}

// ── Honorarios ───────────────────────────────────────────────
let honProcesoId  = 0;
let honRadicado   = '';

const tiposHon = {
    pago_puntual:        'Pago puntual',
    cuota_periodica:     'Cuota periódica',
    honorario_exito:     'Honorario éxito',
    anticipo:            'Anticipo',
    gasto_reembolsable:  'Gasto reembolsable',
};

function fmtCOP(v) {
    return new Intl.NumberFormat('es-CO', { style:'currency', currency:'COP', minimumFractionDigits:0 }).format(v||0);
}

function abrirHonorarios(procesoId, radicado) {
    honProcesoId = procesoId;
    honRadicado  = radicado;
    document.getElementById('honSubtitulo').textContent = 'Radicado: ' + radicado;
    document.getElementById('honFormWrap').style.display = 'none';
    document.getElementById('modalHonorarios').style.display = 'block';
    cargarHonorarios();
}

function cerrarHonorarios() {
    document.getElementById('modalHonorarios').style.display = 'none';
}

function cargarHonorarios() {
    Promise.all([
        fetchWithAuth(`/procesos_juridicos/backend/controllers/HonorarioController.php?action=resumen&proceso_id=${honProcesoId}`).then(r=>r.json()),
        fetchWithAuth(`/procesos_juridicos/backend/controllers/HonorarioController.php?action=list&proceso_id=${honProcesoId}`).then(r=>r.json())
    ]).then(([resumen, lista]) => {
        renderHonKpis(resumen);
        renderHonTabla(lista);
    });
}

function renderHonKpis(r) {
    document.getElementById('honKpis').innerHTML = `
        <div class="hon-kpi cobrado">
            <div class="hon-kpi-num">${fmtCOP(r.total_cobrado || 0)}</div>
            <div class="hon-kpi-label">Total cobrado</div>
        </div>

        <div class="hon-kpi pagado">
            <div class="hon-kpi-num">${fmtCOP(r.total_pagado || 0)}</div>
            <div class="hon-kpi-label">Pagado</div>
        </div>

        <div class="hon-kpi pendiente">
            <div class="hon-kpi-num">${fmtCOP(r.total_pendiente || 0)}</div>
            <div class="hon-kpi-label">Pendiente</div>
        </div>

        <div class="hon-kpi vencido">
            <div class="hon-kpi-num">${fmtCOP(r.total_vencido || 0)}</div>
            <div class="hon-kpi-label">Vencido</div>
        </div>
    `;
}

function renderHonTabla(lista) {
    const tbody = document.getElementById('honTbody');
    if (lista.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:30px;color:#aaa">
            <i class="fas fa-file-invoice-dollar" style="font-size:32px;display:block;margin-bottom:8px"></i>
            No hay cobros registrados para este proceso
        </td></tr>`;
        return;
    }
    tbody.innerHTML = lista.map(h => {
        const fmtFecha = f => f ? new Date(f+'T00:00:00').toLocaleDateString('es-CO',{day:'2-digit',month:'short',year:'numeric'}) : '—';
        const accPagar = h.estado !== 'pagado'
            ? `<button class="btn-icon" onclick="pagarHonorario(${h.id})" data-tooltip="Marcar pagado" style="color:#27ae60"><i class="fas fa-check-circle"></i></button>`
            : '';
        return `<tr>
            <td><strong>${h.concepto}</strong>${h.observaciones ? '<br><small style="color:#95a5a6">'+h.observaciones+'</small>' : ''}</td>
            <td><span class="hon-tipo-badge">${tiposHon[h.tipo]||h.tipo}</span></td>
            <td><strong>${fmtCOP(h.valor)}</strong></td>
            <td style="font-size:12px;color:#7f8c8d">${fmtFecha(h.fecha_causacion)}</td>
            <td style="font-size:12px;color:#7f8c8d">${fmtFecha(h.fecha_pago)}</td>
            <td><span class="hon-badge ${h.estado}">${h.estado}</span></td>
            <td>
                ${accPagar}
                <button class="btn-icon" onclick="editarHonorario(${h.id})" data-tooltip="Editar"><i class="fas fa-edit"></i></button>
                <button class="btn-icon" onclick="eliminarHonorario(${h.id})" data-tooltip="Eliminar"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`;
    }).join('');
}

function abrirFormHonorario() {
    document.getElementById('honId').value             = '';
    document.getElementById('honConcepto').value        = '';
    document.getElementById('honTipo').value            = 'pago_puntual';
    document.getElementById('honValor').value           = '';
    document.getElementById('honFechaCausacion').value  = new Date().toISOString().split('T')[0];
    document.getElementById('honFechaPago').value       = '';
    document.getElementById('honEstado').value          = 'pendiente';
    document.getElementById('honObs').value             = '';
    document.getElementById('honFormTitulo').textContent = 'Nuevo Cobro';
    document.getElementById('honFormWrap').style.display = 'block';
    document.getElementById('honConcepto').focus();
}

function cancelarFormHonorario() {
    document.getElementById('honFormWrap').style.display = 'none';
}

function editarHonorario(id) {
    fetchWithAuth(`/procesos_juridicos/backend/controllers/HonorarioController.php?action=get&id=${id}`)
        .then(r=>r.json())
        .then(h => {
            document.getElementById('honId').value             = h.id;
            document.getElementById('honConcepto').value        = h.concepto;
            document.getElementById('honTipo').value            = h.tipo;
            document.getElementById('honValor').value           = h.valor;
            document.getElementById('honFechaCausacion').value  = h.fecha_causacion;
            document.getElementById('honFechaPago').value       = h.fecha_pago || '';
            document.getElementById('honEstado').value          = h.estado;
            document.getElementById('honObs').value             = h.observaciones || '';
            document.getElementById('honFormTitulo').textContent = 'Editar Cobro';
            document.getElementById('honFormWrap').style.display = 'block';
            document.getElementById('honConcepto').focus();
        });
}

function guardarHonorario() {
    const id        = document.getElementById('honId').value;
    const concepto  = document.getElementById('honConcepto').value.trim();
    const valor     = document.getElementById('honValor').value;
    const fechaCaus = document.getElementById('honFechaCausacion').value;

    if (!concepto || !valor || !fechaCaus) {
        toast('Complete los campos obligatorios', 'error');
        return;
    }

    const fd = new FormData();
    fd.append('action',           id ? 'update' : 'create');
    if (id) fd.append('id',       id);
    fd.append('proceso_id',       honProcesoId);
    fd.append('concepto',         concepto);
    fd.append('tipo',             document.getElementById('honTipo').value);
    fd.append('valor',            valor);
    fd.append('fecha_causacion',  fechaCaus);
    fd.append('fecha_pago',       document.getElementById('honFechaPago').value);
    fd.append('estado',           document.getElementById('honEstado').value);
    fd.append('observaciones',    document.getElementById('honObs').value);

    fetchWithAuth('/procesos_juridicos/backend/controllers/HonorarioController.php', { method:'POST', body:fd })
        .then(r=>r.json())
        .then(data => {
            if (data.success) {
                cancelarFormHonorario();
                cargarHonorarios();
                toast(id ? 'Cobro actualizado' : 'Cobro registrado correctamente');
            } else {
                toast('Error al guardar', 'error');
            }
        });
}

function pagarHonorario(id) {
    const hoy = new Date().toISOString().split('T')[0];
    if (!confirm('¿Marcar este cobro como pagado hoy?')) return;
    const fd = new FormData();
    fd.append('action',     'pagar');
    fd.append('id',         id);
    fd.append('fecha_pago', hoy);
    fetchWithAuth('/procesos_juridicos/backend/controllers/HonorarioController.php', { method:'POST', body:fd })
        .then(r=>r.json())
        .then(data => {
            if (data.success) { cargarHonorarios(); toast('Pago registrado ✓'); }
        });
}

function eliminarHonorario(id) {
    if (!confirm('¿Eliminar este cobro?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fetchWithAuth('/procesos_juridicos/backend/controllers/HonorarioController.php', { method:'POST', body:fd })
        .then(r=>r.json())
        .then(data => {
            if (data.success) { cargarHonorarios(); toast('Cobro eliminado', 'info'); }
        });
}

// ── Init ──────────────────────────────────────────────────────
cargarProcesos(1, '');
</script>