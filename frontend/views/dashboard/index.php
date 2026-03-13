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
                let diffTime = fechaVenc - hoy;
                let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                // Clase según urgencia
                let claseUrgencia = 'normal';
                let claseDias = 'dias-verde';
                if(diffDays <= 3) {
                    claseUrgencia = 'urgente';
                    claseDias = 'dias-rojo';
                } else if(diffDays <= 7) {
                    claseUrgencia = 'atencion';
                    claseDias = 'dias-naranja';
                }
                
                // Clase para el estado
                let claseEstado = 'estado-activo';
                if(p.estado === 'En espera') claseEstado = 'estado-espera';
                else if(p.estado === 'Vencido') claseEstado = 'estado-vencido';
                else if(p.estado === 'Finalizado') claseEstado = 'estado-finalizado';
                
                html += `
                    <div class="stat-item ${claseUrgencia}">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <strong>${p.numero_radicado}</strong>
                            <span class="dias-badge ${claseDias}">${diffDays} días</span>
                        </div>
                        <div>${p.nombre} ${p.apellido}</div>
                        <div><small>Vence: ${p.fecha_vencimiento}</small></div>
                        <div><span class="${claseEstado}">${p.estado}</span></div>
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
                html += `
                    <div class="stat-item">
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