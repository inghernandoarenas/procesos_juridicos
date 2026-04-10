<?php
/**
 * ApiSamai.php — SAMAI, Consejo de Estado / Tribunales Administrativos
 *
 * PAYLOAD CONFIRMADO (DevTools):
 *   POST /Vistas/Casos/Jprocesos.ashx/listaprocesosdata
 *   Content-Type: application/json; charset=UTF-8
 *   Body: {
 *     "FW_tipobusqueda"  : "FW_Rbtradicado",
 *     "FW_ppexacta"      : "",
 *     "FW_tipoarea"      : "FW_RbtCorporacion",
 *     "FW_Txtcriterios"  : "RADICADO",
 *     "FW_LstCorporacion": "CODIGO_DESPACHO",  ← necesario
 *     "FW_LstSeccion"    : "",
 *     "FW_LstPonente"    : "",
 *     "FW_FechaI"        : "",
 *     "FW_FechaF"        : "",
 *     "FW_LstcriterioV"  : "",
 *     "FW_LstcriterioP"  : ""
 *   }
 *
 * NOTA IMPORTANTE: El portal exige seleccionar el juzgado/corporación (FW_LstCorporacion).
 * El código del despacho se infiere de los primeros 7 dígitos del radicado.
 * Ej: radicado 08001333300320230008000 → código 0800133
 *
 * Las actuaciones requieren captcha en el browser — no se pueden scrapear desde PHP.
 * Solución: el endpoint de búsqueda nos da el guid; las actuaciones se almacenan
 * en el campo DETALLES y se pueden enriquecer si el usuario las sincroniza manualmente
 * desde el browser (futuro: endpoint alternativo o headless browser).
 */
class ApiSamai {

    private $timeout = 25;
    private $baseUrl = 'https://samai.consejodeestado.gov.co';
    private $logFile;

    // Mapa de códigos de corporación/despacho por prefijo de radicado
    // El código está en posiciones 0-6 del radicado (ciudad+despacho)
    // Se pueden ampliar según necesidad
    private $corporaciones = [
        // Barranquilla — Tribunal Administrativo del Atlántico
        '0800133' => '0800133',
        '0800123' => '0800123',
        // Bogotá — Consejo de Estado / Tribunal Administrativo de Cundinamarca
        '1100133' => '1100133',
        '1100123' => '1100123',
        // Medellín — Tribunal Administrativo de Antioquia
        '0500133' => '0500133',
        '0500123' => '0500123',
        // Cali — Tribunal Administrativo del Valle
        '7600133' => '7600133',
        // Bucaramanga
        '6800133' => '6800133',
        // Default vacío — SAMAI intentará buscar sin filtro de corporación
    ];

    public function __construct() {
        $logDir = __DIR__ . '/../../logs';
        if (!file_exists($logDir)) mkdir($logDir, 0777, true);
        $this->logFile = $logDir . '/samai_sync.log';
    }

    private function log($msg) {
        file_put_contents($this->logFile, '[' . date('H:i:s') . "] $msg\n", FILE_APPEND);
    }

    /**
     * Infiere el código de corporación desde el radicado.
     * Los primeros 7 dígitos identifican ciudad + despacho en SAMAI.
     */
    private function inferirCorporacion(string $radicado): string {
        $limpio = preg_replace('/[^0-9]/', '', $radicado);
        $prefix = substr($limpio, 0, 7);
        return $this->corporaciones[$prefix] ?? $prefix;
    }

    private function post(string $url, array $payload): ?string {
        $this->log("POST $url");
        $this->log("  Payload: " . json_encode($payload));
        $t0 = microtime(true);

        $ctx = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'timeout'       => $this->timeout,
                'ignore_errors' => true,
                'header'        => implode("\r\n", [
                    'Content-Type: application/json; charset=UTF-8',
                    'Accept: application/json, text/javascript, */*; q=0.01',
                    'Accept-Language: es-CO,es;q=0.9',
                    'Origin: '           . $this->baseUrl,
                    'Referer: '          . $this->baseUrl . '/Vistas/Casos/procesos.aspx',
                    'X-Requested-With: XMLHttpRequest',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120',
                ]),
                'content' => json_encode($payload),
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        $res = @file_get_contents($url, false, $ctx);
        $ms  = round((microtime(true) - $t0) * 1000);

        if ($res === false) {
            $this->log("  ✗ sin respuesta ({$ms}ms) — reintentando...");
            sleep(1);
            $res = @file_get_contents($url, false, $ctx);
            if ($res === false) { $this->log("  ✗ reintento falló"); return null; }
        }

        $this->log("  ✓ {$ms}ms — " . strlen($res) . " bytes");
        return $res;
    }

    private function limpiarRadicado(string $r): string {
        return trim(ltrim(trim($r), "'\""));
    }

    private function extraerGuid(string $html): ?string {
        // goprocs_gestion('08001333300320230008000','0800133', '1')
        if (preg_match("/goprocs_gestion\('([^']+)','([^']+)'/", $html, $m)) {
            return $m[1] . $m[2];
        }
        return null;
    }

    private function parsearDetallesHtml(string $html): array {
        $texto  = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));
        $lineas = array_filter(array_map('trim', explode("\n", $texto)));
        $tipo = $demandante = $demandado = $ponente = '';
        foreach ($lineas as $l) {
            if (empty($tipo) && str_contains($l, 'Ingreso:'))
                $tipo = trim(explode('-', $l)[0]);
            if (str_contains($l, 'Ponente:'))
                $ponente    = trim(str_replace('Ponente:', '', $l));
            if (str_contains($l, 'Demandante:'))
                $demandante = trim(str_replace('Demandante:', '', $l));
            if (str_contains($l, 'Demandado:'))
                $demandado  = trim(str_replace('Demandado:', '', $l));
        }
        return compact('tipo', 'ponente', 'demandante', 'demandado');
    }

    /**
     * Busca el proceso en SAMAI con el payload correcto confirmado por DevTools.
     */
    public function buscarProceso(string $radicado): ?array {
        $this->log("=== SAMAI buscarProceso: $radicado ===");

        $corporacion = $this->inferirCorporacion($radicado);
        $this->log("  Corporación inferida: $corporacion");

        $url     = $this->baseUrl . '/Vistas/Casos/Jprocesos.ashx/listaprocesosdata';
        $payload = [
            'FW_tipobusqueda'   => 'FW_Rbtradicado',
            'FW_ppexacta'       => '',
            'FW_tipoarea'       => 'FW_RbtCorporacion',
            'FW_Txtcriterios'   => $radicado,
            'FW_LstCorporacion' => $corporacion,
            'FW_LstSeccion'     => '',
            'FW_LstPonente'     => '',
            'FW_FechaI'         => '',
            'FW_FechaF'         => '',
            'FW_LstcriterioV'   => '',
            'FW_LstcriterioP'   => '',
        ];

        $raw = $this->post($url, $payload);
        if ($raw === null) return null;

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $this->log("  ✗ JSON inválido: " . substr($raw, 0, 300));

            // Reintentar sin filtro de corporación
            $this->log("  Reintentando sin filtro de corporación...");
            $payload['FW_LstCorporacion'] = '';
            $raw2 = $this->post($url, $payload);
            if ($raw2 !== null) {
                $data = json_decode($raw2, true);
            }
            if (!is_array($data)) {
                $this->log("  ✗ Ambos intentos fallaron");
                return null;
            }
        }

        $this->log("  Registros encontrados: " . count($data));

        $result = [];
        foreach ($data as $item) {
            $det  = $this->parsearDetallesHtml($item['DETALLES'] ?? '');
            $guid = $this->extraerGuid($item['ACCIONES'] ?? '');
            // Extraer código de despacho del guid o del ACCIONES
            preg_match("/goprocs_gestion\('[^']+','([^']+)'/", $item['ACCIONES'] ?? '', $mDesp);
            $codDespacho = $mDesp[1] ?? $corporacion;

            $result[] = [
                'radicado'    => $this->limpiarRadicado($item['RADICADO'] ?? ''),
                'guid'        => $guid,
                'cod_despacho'=> $codDespacho,
                'tipo'        => $det['tipo'],
                'ponente'     => $det['ponente'],
                'demandante'  => $det['demandante'],
                'demandado'   => $det['demandado'],
            ];
        }
        return $result;
    }

    /**
     * Intenta traer actuaciones desde SAMAI.
     *
     * LIMITACIÓN CONOCIDA: El portal exige captcha al ver el detalle desde browser.
     * Desde PHP (servidor) NO hay captcha — el captcha solo aparece en sesiones browser.
     * Por eso intentamos directamente el HTML del detalle desde PHP.
     */
    public function consultarActuaciones(string $guid, string $despachoLabel): ?array {
        $this->log("-- consultarActuaciones guid=$guid");

        // Opción A: endpoint AJAX listaactuacionesdata (mismo patrón)
        $urlAjax = $this->baseUrl . '/Vistas/Casos/Jprocesos.ashx/listaactuacionesdata';
        $rawAjax = $this->post($urlAjax, ['guid' => $guid]);

        if ($rawAjax !== null) {
            $dataAjax = json_decode($rawAjax, true);
            if (is_array($dataAjax) && !empty($dataAjax)) {
                $this->log("  ✓ AJAX devolvió " . count($dataAjax) . " registros");
                $this->log("  Muestra: " . json_encode($dataAjax[0] ?? []));
                return $this->mapearActuacionesJson($dataAjax, $despachoLabel);
            }
            $this->log("  AJAX vacío o no JSON (" . strlen($rawAjax) . "b): " . substr($rawAjax, 0, 100));
        }

        // Opción B: GET HTML del detalle
        $urlHtml = $this->baseUrl . '/Vistas/Casos/list_procesos.aspx?guid=' . urlencode($guid);
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => $this->timeout,
                'ignore_errors' => true,
                'header'        => implode("\r\n", [
                    'Accept: text/html,application/xhtml+xml',
                    'Accept-Language: es-CO,es;q=0.9',
                    'Referer: ' . $this->baseUrl . '/Vistas/Casos/procesos.aspx',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120',
                ]),
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        $rawHtml = @file_get_contents($urlHtml, false, $ctx);
        if ($rawHtml === null || $rawHtml === false) {
            $this->log("  ✗ HTML no disponible");
            return null;
        }

        $this->log("  HTML recibido: " . strlen($rawHtml) . " bytes");

        // Detectar captcha
        if (str_contains($rawHtml, 'captcha') || str_contains($rawHtml, 'recaptcha') || str_contains($rawHtml, 'robot')) {
            $this->log("  ⚠ Captcha detectado en HTML");
            return null; // señal especial
        }

        $acts = $this->parsearActuacionesHtml($rawHtml, $despachoLabel);
        $this->log("  HTML parseado: " . count($acts) . " actuaciones");
        return $acts;
    }

    private function mapearActuacionesJson(array $data, string $despacho): array {
        $result = [];
        foreach ($data as $i => $act) {
            $fecha = null;
            foreach (['FECHA_ACTUACION','FECHA','fecha','FECHA_REG','FechaActuacion'] as $k) {
                if (!empty($act[$k])) { $fecha = $this->normalizarFecha($act[$k]); break; }
            }
            $actuacion = null;
            foreach (['ACTUACION','actuacion','TIPO','TipoActuacion','DESCRIPCION'] as $k) {
                if (!empty($act[$k])) { $actuacion = $act[$k]; break; }
            }
            $obs = null;
            foreach (['ANOTACION','anotacion','OBS','OBSERVACION','Anotacion'] as $k) {
                if (!empty($act[$k])) { $obs = $act[$k]; break; }
            }
            $id = null;
            foreach (['ID','id','IDACTUACION','IdActuacion'] as $k) {
                if (!empty($act[$k])) { $id = $act[$k]; break; }
            }
            $result[] = [
                'id'            => $id ?? ('samai_' . $i . '_' . substr(md5(json_encode($act)), 0, 8)),
                'fecha'         => $fecha,
                'actuacion'     => $actuacion ?? 'Sin descripción',
                'observaciones' => $obs,
                'despacho'      => $despacho,
            ];
        }
        return $result;
    }

    private function parsearActuacionesHtml(string $html, string $despacho): array {
        $result = [];
        // Buscar tablas con datos de actuaciones
        preg_match_all('/<table[^>]*>(.*?)<\/table>/si', $html, $tables);
        foreach ($tables[1] as $tabla) {
            preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $tabla, $filas);
            $header_saltado = false;
            foreach ($filas[1] as $fila) {
                preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/si', $fila, $celdas);
                $cols = array_values(array_filter(
                    array_map(fn($c) => trim(strip_tags($c)), $celdas[1]),
                    fn($c) => $c !== ''
                ));
                if (count($cols) < 2) continue;
                if (!$header_saltado) { $header_saltado = true; continue; }

                $fecha = $actuacion = $obs = null;
                foreach ($cols as $val) {
                    if (!$fecha && preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $val, $m))
                        $fecha = "{$m[3]}-{$m[2]}-{$m[1]}";
                    elseif (!$fecha && preg_match('/^(\d{4}-\d{2}-\d{2})/', $val, $m))
                        $fecha = $m[1];
                    elseif ($fecha && !$actuacion && strlen($val) > 3)
                        $actuacion = $val;
                    elseif ($fecha && $actuacion && !$obs)
                        $obs = $val ?: null;
                }
                if (!$fecha || !$actuacion) continue;
                $result[] = [
                    'id'            => 'samai_' . substr(md5($fecha . $actuacion . $despacho), 0, 12),
                    'fecha'         => $fecha,
                    'actuacion'     => $actuacion,
                    'observaciones' => $obs,
                    'despacho'      => $despacho,
                ];
            }
            if (!empty($result)) break; // primera tabla con datos
        }
        return $result;
    }

    private function normalizarFecha(string $f): ?string {
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $f, $m)) return "{$m[3]}-{$m[2]}-{$m[1]}";
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $f, $m)) return $m[1];
        return null;
    }

    /**
     * Método principal.
     */
    public function consultarActuacionesPorRadicado(string $radicado): ?array {
        $this->log("=== SAMAI INICIO radicado=$radicado ===");

        $procesos = $this->buscarProceso($radicado);
        if ($procesos === null) return null;
        if (empty($procesos)) { $this->log("  Proceso no encontrado"); return []; }

        $resultado = [];
        foreach ($procesos as $proc) {
            if (!$proc['guid']) { $this->log("  Sin guid, omitiendo"); continue; }

            $despacho = trim(
                ($proc['tipo'] ?: 'SAMAI') .
                ($proc['demandante'] ? ' — ' . $proc['demandante'] : '')
            );

            $acts = $this->consultarActuaciones($proc['guid'], $despacho);

            if ($acts === null) {
                // Captcha o error — reportar pero no fallar
                $this->log("  ⚠ No se pudieron traer actuaciones (posible captcha)");
                // Crear entrada informativa para que el usuario sepa que el proceso existe
                $resultado[] = [
                    'id'            => 'samai_info_' . $proc['guid'],
                    'fecha'         => date('Y-m-d'),
                    'actuacion'     => 'Proceso encontrado en SAMAI — actuaciones requieren verificación manual',
                    'observaciones' => 'Tipo: ' . ($proc['tipo'] ?: '—') . ' | Demandante: ' . ($proc['demandante'] ?: '—'),
                    'despacho'      => $despacho,
                ];
                continue;
            }

            $resultado = array_merge($resultado, $acts);
        }

        $this->log("=== SAMAI FIN — total: " . count($resultado) . " actuaciones ===");
        return $resultado;
    }
}
?>