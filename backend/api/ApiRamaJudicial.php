<?php
class ApiRamaJudicial {
    
    public function consultarActuacionesPorRadicado($radicado) {
        // 1️⃣ buscar proceso
        $url = "https://consultaprocesos.ramajudicial.gov.co:448/api/v2/Procesos/Consulta/NumeroRadicacion?numero=$radicado&SoloActivos=false&pagina=1";
        
        $json = file_get_contents($url);
        $data = json_decode($json, true);
        
        $idProceso = $data['procesos'][0]['idProceso'] ?? null;
        
        if(!$idProceso) {
            return [];
        }
        
        // 2️⃣ traer actuaciones
        $urlAct = "https://consultaprocesos.ramajudicial.gov.co:448/api/v2/Proceso/Actuaciones/$idProceso?pagina=1";
        
        $act = file_get_contents($urlAct);
        $actuaciones = json_decode($act, true);
        
        // Formatear para devolver con el ID incluido
        $resultado = [];
        foreach($actuaciones['actuaciones'] ?? [] as $act) {
            $resultado[] = [
                'id' => $act['idRegActuacion'], 
                'fecha' => $act['fechaRegistro'] ?? $act['fechaActuacion'],
                'actuacion' => $act['actuacion'] ?? 'Sin descripción',
                'observaciones' => $act['anotacion'] ?? null
            ];
        }
        
        return $resultado;
    }
}
?>