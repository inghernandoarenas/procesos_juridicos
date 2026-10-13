<style>
.doc-header { margin-bottom: 22px; }
.doc-header p { color: #7f8c8d; font-size: 13px; margin-top: 4px; }
.doc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 18px;
}
.doc-card {
    background: white;
    border-radius: 10px;
    padding: 22px;
    box-shadow: 0 2px 6px rgba(0,0,0,.08);
    border-top: 4px solid #3498db;
    display: flex;
    flex-direction: column;
}
.doc-card-icon {
    width: 46px; height: 46px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; color: white;
    margin-bottom: 14px;
}
.doc-card h3 { font-size: 15px !important; margin-bottom: 8px !important; color: #2c3e50; }
.doc-card p.doc-desc { font-size: 12.5px; color: #7f8c8d; line-height: 1.5; flex: 1; margin-bottom: 16px; }
.doc-card .doc-actions { display: flex; flex-direction: column; gap: 8px; }
.doc-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 9px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 600;
    text-decoration: none; transition: opacity .15s; cursor: pointer; border: none;
}
.doc-btn:hover { opacity: .85; }
.doc-btn-primary { background: #3498db; color: white; }
.doc-btn-outline { background: #f4f8fb; color: #2c3e50; border: 1px solid #dce8f0; }
.doc-card.manual .doc-card-icon { background: linear-gradient(135deg,#2b579a,#4472c4); }
.doc-card.swagger .doc-card-icon { background: linear-gradient(135deg,#8e44ad,#9b59b6); }
.doc-card.vision .doc-card-icon { background: linear-gradient(135deg,#2b579a,#4472c4); }
.doc-note {
    margin-top: 22px; background: #fff8e7; border-left: 4px solid #f39c12;
    padding: 12px 16px; border-radius: 6px; font-size: 12.5px; color: #7f5a0c;
}
</style>

<div class="page-header doc-header">
    <div>
        <h2><i class="fas fa-book-open" style="color:#3498db;margin-right:8px"></i>Documentación</h2>
        <p>Todo lo que necesita un desarrollador, QA o un nuevo integrante del equipo para entender y usar el sistema — en un solo lugar.</p>
    </div>
</div>

<div class="doc-grid">

    <div class="doc-card manual">
        <div class="doc-card-icon"><i class="fas fa-user-graduate"></i></div>
        <h3>Manual de Usuario</h3>
        <p class="doc-desc">Guía paso a paso de cada pantalla y función del sistema: dashboard, procesos, sincronización con los portales, honorarios, notificaciones y más. Pensada para quien usa la aplicación en el día a día.</p>
        <div class="doc-actions">
            <a class="doc-btn doc-btn-primary" href="/procesos_juridicos/docs/manual_usuario.docx" download>
                <i class="fas fa-file-word"></i> Descargar Word
            </a>
        </div>
    </div>

    <div class="doc-card swagger">
        <div class="doc-card-icon"><i class="fas fa-plug"></i></div>
        <h3>Documentación de la API (Swagger)</h3>
        <p class="doc-desc">Especificación OpenAPI 3.0 de los 21 endpoints del backend: parámetros, respuestas, autenticación. Incluye un visor interactivo para probar llamadas directamente desde el navegador.</p>
        <div class="doc-actions">
            <a class="doc-btn doc-btn-outline" href="/procesos_juridicos/docs/index.html" target="_blank">
                <i class="fas fa-external-link-alt"></i> Abrir documentación interactiva
            </a>
            <a class="doc-btn doc-btn-primary" href="/procesos_juridicos/docs/openapi.yaml" download>
                <i class="fas fa-file-code"></i> Descargar openapi.yaml
            </a>
        </div>
    </div>

    <div class="doc-card vision">
        <div class="doc-card-icon"><i class="fas fa-sitemap"></i></div>
        <h3>Visión General y Arquitectura</h3>
        <p class="doc-desc">Qué es el sistema, qué problema resuelve, sus módulos, flujos de negocio, modelo de datos y arquitectura técnica — con diagrama. Punto de entrada para un líder técnico, un QA o un nuevo desarrollador.</p>
        <div class="doc-actions">
            <a class="doc-btn doc-btn-primary" href="/procesos_juridicos/docs/vision_general_arquitectura.docx" download>
                <i class="fas fa-file-word"></i> Descargar Word
            </a>
        </div>
    </div>

</div>

<div class="doc-note">
    <i class="fas fa-circle-info" style="margin-right:6px"></i>
    Los Word son una foto del documento al momento de generarlos. Si el proyecto cambia y se necesita actualizar el contenido, avisa para regenerarlos y reemplazar estos archivos.
</div>