<?php
class ApiRamaJudicial {

    private $timeout = 30;
    private $logFile = null;

    public function __construct() {
        $logDir = __DIR__ . '/../../logs';
        if (!file_exists($logDir)) mkdir($logDir, 0777, true);
        $this->logFile = $logDir . '/rama_sync.log';
    }

    private function log($msg) {
        $ts = date('H:i:s');
        file_put_contents($this->logFile, "[$ts] $msg\n", FILE_APPEND);
    }

    private function get($url) {
        $this->log("GET $url");
        $t0 = microtime(true);

        $context = stream_context_create([
            'http' => ['timeout' => $this->timeout, 'ignore_errors' => true],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false]
        ]);

        $response = @file_get_contents($url, false, $context);
        $ms = round((microtime(true) - $t0) * 1000);

        if ($response === false) {
            $this->log("  ERROR: sin respuesta ({$ms}ms)");
            // 1 reintento
            sleep(1);
            $this->log("  Reintentando...");
            $t0 = microtime(true);
            $response = @file_get_contents($url, false, $context);
            $ms = round((microtime(true) - $t0) * 1000);
            if ($response === false) {
                $this->log("  ERROR: reintento también falló ({$ms}ms)");
                return null;
            }
        }

        $this->log("  OK {$ms}ms — " . strlen($response) . " bytes");
        return $response;
    }

    private function totalPaginas(array $resp): int {
        $n = (int)(
            $resp['paginacion']['cantidadPaginas'] ??
            $resp['cantidadPaginas'] ??
            $resp['totalPaginas'] ?? 1
        );
        $this->log("  totalPaginas=$n (registros=" . ($resp['paginacion']['cantidadRegistros'] ?? '?') . ", porPagina=" . ($resp['paginacion']['registrosPagina'] ?? '?') . ")");
        return max(1, $n);
    }

    public function consultarActuacionesPorRadicado($radicado) {
        $this->log("=== INICIO radicado=$radicado ===");

        // ── 1. Despachos del radicado ──────────────────────────────────────────
        $procesos = [];
        $pagina   = 1;

        do {
            $url  = "https://consultaprocesos.ramajudicial.gov.co:448/api/v2/Procesos/Consulta/NumeroRadicacion"
                  . "?numero={$radicado}&SoloActivos=false&pagina={$pagina}";

            $json = $this->get($url);
            if ($json === null) {
                $this->log("ERROR FATAL: no se pudo conectar al endpoint de procesos");
                return null;
            }

            $data = json_decode($json, true);
            if (!is_array($data)) {
                $this->log("ERROR: JSON inválido en endpoint procesos");
                return null;
            }

            $pagina_procesos = $data['procesos'] ?? [];
            $this->log("  Pag $pagina — despachos en esta pagina: " . count($pagina_procesos));

            if (empty($pagina_procesos)) break;

            $procesos  = array_merge($procesos, $pagina_procesos);
            $totalPags = $this->totalPaginas($data);
            $pagina++;

        } while ($pagina <= $totalPags);

        $this->log("Total despachos encontrados: " . count($procesos));

        if (empty($procesos)) return [];

        $resultado = [];

        // ── 2. Actuaciones de cada despacho ───────────────────────────────────
        foreach ($procesos as $idx => $proc) {
            $idProceso = $proc['idProceso'] ?? null;
            if (!$idProceso) { $this->log("Despacho $idx sin idProceso, saltando"); continue; }

            $despacho = trim($proc['despacho'] ?? $proc['nombreDespacho'] ?? $proc['entidad'] ?? 'Sin despacho');
            $this->log("-- Despacho $idx: idProceso=$idProceso | $despacho");

            $paginaAct     = 1;
            $totalActDesp  = 0;

            do {
                $urlAct  = "https://consultaprocesos.ramajudicial.gov.co:448/api/v2/Proceso/Actuaciones/{$idProceso}?pagina={$paginaAct}";
                $actJson = $this->get($urlAct);

                if ($actJson === null) {
                    $this->log("  Timeout en despacho $idProceso pag $paginaAct — saltando");
                    break;
                }

                $respuesta = json_decode($actJson, true);
                if (!is_array($respuesta)) {
                    $this->log("  JSON inválido en actuaciones despacho $idProceso");
                    break;
                }

                $acts = $respuesta['actuaciones'] ?? [];
                $this->log("  Pag $paginaAct — actuaciones recibidas: " . count($acts));

                if (empty($acts)) break;

                foreach ($acts as $act) {
                    $idReg = $act['idRegActuacion'] ?? null;
                    if ($idReg === null) continue;

                    $fechaRaw = $act['fechaRegistro'] ?? $act['fechaActuacion'] ?? null;
                    $fecha    = $fechaRaw ? substr($fechaRaw, 0, 10) : null;
                    $obs      = isset($act['anotacion']) ? trim($act['anotacion']) : null;
                    if ($obs === '') $obs = null;

                    $resultado[] = [
                        'id'            => $idReg,
                        'fecha'         => $fecha,
                        'actuacion'     => trim($act['actuacion'] ?? 'Sin descripción'),
                        'observaciones' => $obs,
                        'despacho'      => $despacho,
                    ];
                    $totalActDesp++;
                }

                $totalPagsAct = $this->totalPaginas($respuesta);
                $this->log("  Pag $paginaAct completada. totalPaginas=$totalPagsAct");
                $paginaAct++;

            } while ($paginaAct <= $totalPagsAct);

            $this->log("  Despacho $idProceso — total actuaciones extraidas: $totalActDesp");
        }

        $this->log("=== FIN — total resultado: " . count($resultado) . " actuaciones ===");
        return $resultado;
    }
}
?>