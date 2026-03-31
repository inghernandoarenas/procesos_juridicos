<?php
class ApiRamaJudicial {

    private $timeout = 15; // segundos máximo de espera

    private function get($url) {
        $context = stream_context_create([
            'http' => [
                'timeout'        => $this->timeout,
                'ignore_errors'  => true,
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null; // timeout o error de red
        }

        return $response;
    }

    public function consultarActuacionesPorRadicado($radicado) {
        // 1️⃣ Buscar proceso por radicado
        $url  = "https://consultaprocesos.ramajudicial.gov.co:448/api/v2/Procesos/Consulta/NumeroRadicacion"
              . "?numero={$radicado}&SoloActivos=false&pagina=1";

        $json = $this->get($url);

        if ($json === null) {
            return null; // señal de timeout / error de red
        }

        $data      = json_decode($json, true);
        $idProceso = $data['procesos'][0]['idProceso'] ?? null;

        if (!$idProceso) {
            return [];
        }

        // 2️⃣ Traer actuaciones
        $urlAct  = "https://consultaprocesos.ramajudicial.gov.co:448/api/v2/Proceso/Actuaciones/{$idProceso}?pagina=1";
        $actJson = $this->get($urlAct);

        if ($actJson === null) {
            return null;
        }

        $actuaciones = json_decode($actJson, true);

        $resultado = [];
        foreach ($actuaciones['actuaciones'] ?? [] as $act) {
            $resultado[] = [
                'id'           => $act['idRegActuacion'],
                'fecha'        => $act['fechaRegistro'] ?? $act['fechaActuacion'],
                'actuacion'    => $act['actuacion']   ?? 'Sin descripción',
                'observaciones'=> $act['anotacion']   ?? null,
            ];
        }

        return $resultado;
    }
}
?>