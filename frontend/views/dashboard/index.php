<style>
    /* Tooltips para dashboard */
.stat-item[data-tooltip] {
    position: relative;
    cursor: help;
}

.stat-item[data-tooltip]:hover:after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 0;
    background: #2c3e50;
    color: white;
    padding: 12px 15px;
    border-radius: 8px;
    font-size: 12px;
    line-height: 1.6;
    white-space: pre-line;
    min-width: 280px;
    max-width: 350px;
    z-index: 1000;
    margin-bottom: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    pointer-events: none;
    text-align: left;
    font-weight: normal;
    word-wrap: break-word;
    text-align: left;
}

.stat-item[data-tooltip]:hover:before {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 20px;
    border-width: 6px;
    border-style: solid;
    border-color: #2c3e50 transparent transparent transparent;
    margin-bottom: -4px;
    z-index: 1000;
    pointer-events: none;
}
</style>

<script>
    // Función para obtener headers con token
function getHeaders() {
    const token = localStorage.getItem('token');
    return {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/x-www-form-urlencoded'
    };
}

// Función para hacer fetch con token
function fetchWithAuth(url, options = {}) {
    const token = localStorage.getItem('token');
    
    if (!token) {
        window.location.href = '/procesos_juridicos/frontend/login.php';
        return Promise.reject('No token');
    }
    
    options.headers = {
        ...options.headers,
        'Authorization': 'Bearer ' + token
    };
    
    return fetch(url, options)
        .then(response => {
            if (response.status === 401) {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                window.location.href = '/procesos_juridicos/frontend/login.php';
                return Promise.reject('Unauthorized');
            }
            return response;
        });
}
</script>


<div class="dashboard">
    <h2>Dashboard</h2>
    
    <div class="stats-container">
        <div class="stat-card">
            <h3>⚠️ Próximos a Vencer</h3>
            <div id="proximosVencer" class="stat-list"></div>
        </div>
        
        <div class="stat-card">
            <h3>⏳ En Espera de Respuesta</h3>
            <div id="enEspera" class="stat-list"></div>
        </div>
    </div>
</div>

<script>

function cargarProximosVencer() {
    fetch('/procesos_juridicos/backend/controllers/ProcesoController.php?action=proximosVencer')
        .then(response => response.json())
        .then(data => {
            let div = document.getElementById('proximosVencer');
            if(data.length === 0) {
                div.innerHTML = '<p class="sin-datos">No hay procesos próximos a vencer</p>';
                return;
            }
            
            let html = '';
            data.forEach(p => {
                let fechaVenc = new Date(p.fecha_vencimiento);
                let hoy = new Date();
                let diffDays = Math.ceil((fechaVenc - hoy) / (1000 * 60 * 60 * 24));
                
                let claseUrgencia = 'normal';
                let claseDias = 'dias-verde';
                if(diffDays <= 3) {
                    claseUrgencia = 'urgente';
                    claseDias = 'dias-rojo';
                } else if(diffDays <= 7) {
                    claseUrgencia = 'atencion';
                    claseDias = 'dias-naranja';
                }
                
                // Tooltip con información detallada
                let tooltip = `
                    📋 Radicado: ${p.numero_radicado}
                    👤 Cliente: ${p.nombre} ${p.apellido}
                    📝 Tipo: ${p.tipo_proceso}
                    📅 Inicio: ${p.fecha_inicio}
                    ⚠️ Vence: ${p.fecha_vencimiento}
                    📊 Estado: ${p.estado}
                    ${p.descripcion ? '📄 Desc: ' + p.descripcion.substring(0, 100) : ''}
                `;
                
                html += `
                     <div class="stat-item ${claseUrgencia}" data-tooltip="${tooltip}">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <strong>${p.numero_radicado}</strong>
                            <span class="dias-badge ${claseDias}">${diffDays} días</span>
                        </div>
                        <div>${p.nombre} ${p.apellido}</div>
                        <div><small>Vence: ${p.fecha_vencimiento}</small></div>
                        <div><span class="${p.estado === 'Activo' ? 'estado-activo' : 'estado-espera'}">${p.estado}</span></div>
                    </div>
                `;
            });
            div.innerHTML = html;
        });
}

function cargarEnEspera() {
    fetch('/procesos_juridicos/backend/controllers/ProcesoController.php?action=enEspera')
        .then(response => response.json())
        .then(data => {
            let div = document.getElementById('enEspera');
            if(data.length === 0) {
                div.innerHTML = '<p class="sin-datos">No hay procesos en espera</p>';
                return;
            }
            
            let html = '';
            data.forEach(p => {
                // Tooltip con información detallada
                let tooltip = `
                    📋 Radicado: ${p.numero_radicado}
                    👤 Cliente: ${p.nombre} ${p.apellido}
                    📝 Tipo: ${p.tipo_proceso}
                    📅 Inicio: ${p.fecha_inicio}
                    📅 Vence: ${p.fecha_vencimiento || 'No definida'}
                    ⏳ Estado: ${p.estado}
                    ${p.descripcion ? '📄 Desc: ' + p.descripcion.substring(0, 100) : ''}
                `;
                
                html += `
                    <div class="stat-item" data-tooltip="${tooltip}">
                        <strong>${p.numero_radicado}</strong>
                        <div>${p.nombre} ${p.apellido}</div>
                        <div><small>${p.tipo_proceso}</small></div>
                        <div><span class="estado-espera">⏳ ${p.estado}</span></div>
                    </div>
                `;
            });
            div.innerHTML = html;
        });
}

// Cargar datos al entrar
cargarProximosVencer();
cargarEnEspera();

// Refrescar cada 5 minutos (opcional)
setInterval(() => {
    cargarProximosVencer();
    cargarEnEspera();
}, 300000);
</script>