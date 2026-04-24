<?php
/**
 * ApiPublicaciones
 * Consulta el portal de Publicaciones Procesales de la Rama Judicial (Liferay)
 * y extrae las publicaciones de un despacho en un rango de fechas.
 *
 * El portal NO tiene API JSON — devuelve HTML que se parsea aquí.
 */
class ApiPublicaciones {

    private string $baseUrl  = 'https://publicacionesprocesales.ramajudicial.gov.co';
    private string $portletId = 'co_com_avanti_efectosProcesales_PublicacionesEfectosProcesalesPortletV2_INSTANCE_BIyXQFHVaYaq';
    private int    $timeout  = 30;
    private string $logFile;

    public function __construct() {
        $this->logFile = __DIR__ . '/../../logs/publicaciones_sync.log';
    }

    private function log(string $msg): void {
        $ts = date('H:i:s');
        file_put_contents($this->logFile, "[$ts] $msg\n", FILE_APPEND);
    }

    /**
     * Consulta publicaciones de un despacho en un rango de fechas.
     *
     * @param string $codigoDespacho  Código oficial p.ej. "080013105001C"
     * @param string $fechaInicio     YYYY-MM-DD
     * @param string $fechaFin        YYYY-MM-DD
     * @return array|null  null si error de conexión, [] si sin resultados
     */
    public function consultarPorDespacho(string $codigoDespacho, string $fechaInicio, string $fechaFin): ?array {
        $p = $this->portletId;
        $url = $this->baseUrl . '/web/publicaciones-procesales/inicio'
             . '?p_p_id='         . urlencode($p)
             . '&p_p_lifecycle=0'
             . '&p_p_state=normal'
             . '&p_p_mode=view'
             . '&_' . urlencode($p) . '_action=busqueda'
             . '&_' . urlencode($p) . '_fechaInicio=' . urlencode($fechaInicio)
             . '&_' . urlencode($p) . '_fechaFin='    . urlencode($fechaFin)
             . '&_' . urlencode($p) . '_idDepto=%2B'
             . '&_' . urlencode($p) . '_idDespacho='  . urlencode($codigoDespacho)
             . '&_' . urlencode($p) . '_verTotales=true';

        $this->log("GET despacho=$codigoDespacho rango=$fechaInicio/$fechaFin");

        $ctx = stream_context_create([
            'http' => [
                'method'     => 'GET',
                'timeout'    => $this->timeout,
                'header'     => implode("\r\n", [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept: text/html,application/xhtml+xml',
                    'Accept-Language: es-CO,es;q=0.9',
                ]),
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        $html = @file_get_contents($url, false, $ctx);

        if ($html === false) {
            $this->log("  ERROR: no se pudo conectar con el portal");
            return null;
        }

        $publicaciones = $this->parsearHTML($html, $codigoDespacho);
        $this->log("  Encontradas: " . count($publicaciones) . " publicaciones");
        return $publicaciones;
    }

    /**
     * Parsea el HTML del portal y extrae las publicaciones.
     * Estructura del portal:
     *   - Cada publicación es una "card" con categorías en spans/tags
     *   - Categorías: Tipo, Departamento, Municipio, Entidad, Especialidad, Despacho
     *   - Fecha de publicación
     *   - Título (p.ej. "Notificación por Estado No.064 de 20 de abril de 2026")
     */
    private function parsearHTML(string $html, string $codigoDespacho): array {
        $results = [];

        // Suprimir errores de HTML malformado
        $prevErrors = libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_use_internal_errors($prevErrors);

        $xpath = new DOMXPath($doc);

        // Las publicaciones están en divs con clase que contiene "efectos" o en artículos
        // Buscar por el patrón: divs que contienen "Fecha de Publicación:"
        $cards = $xpath->query('//*[contains(@class,"portlet-body") or contains(@class,"journal-content-article")]//div[.//text()[contains(.,"Fecha de Publicaci")]]');

        // Si no encontramos por clase, buscar todos los divs con fecha de publicación
        if ($cards === false || $cards->length === 0) {
            $cards = $xpath->query('//div[.//text()[contains(., "Fecha de Publicaci")]][not(ancestor::div[.//text()[contains(., "Fecha de Publicaci")]])]');
        }

        // Último recurso: buscar el patrón de categorías
        if ($cards === false || $cards->length === 0) {
            // Buscar por "Categorías |" que aparece en cada card
            $cards = $xpath->query('//div[.//text()[contains(., "Categor")]][.//text()[contains(., "Fecha de Publicaci")]][not(ancestor::div[.//text()[contains(., "Categor")]][.//text()[contains(., "Fecha de Publicaci")]])]');
        }

        if ($cards === false || $cards->length === 0) {
            $this->log("  HTML parseado pero sin cards encontradas (0 resultados o estructura inesperada)");
            // Intentar parsear directamente por regex como fallback
            return $this->parsearPorRegex($html, $codigoDespacho);
        }

        foreach ($cards as $card) {
            $texto = $card->textContent;

            // Extraer título
            $titulo = $this->extraerTitulo($xpath, $card);

            // Extraer fecha de publicación
            $fecha = $this->extraerFecha($texto);
            if (!$fecha) continue;

            // Extraer categorías del texto
            $categorias = $this->extraerCategorias($texto);

            $results[] = [
                'titulo'      => $titulo ?: ('Publicación ' . $fecha),
                'fecha'       => $fecha,
                'tipo'        => $categorias['tipo']        ?? 'Publicación',
                'departamento'=> $categorias['departamento']?? '',
                'municipio'   => $categorias['municipio']   ?? '',
                'entidad'     => $categorias['entidad']     ?? '',
                'especialidad'=> $categorias['especialidad']?? '',
                'despacho'    => $categorias['despacho']    ?? $codigoDespacho,
            ];
        }

        // Si DOM no encontró nada, intentar regex
        if (empty($results)) {
            return $this->parsearPorRegex($html, $codigoDespacho);
        }

        return $results;
    }

    private function extraerTitulo(DOMXPath $xpath, DOMNode $card): string {
        // Buscar h1, h2, h3, o el primer texto grande
        foreach (['h1','h2','h3','h4','strong'] as $tag) {
            $nodes = $xpath->query('.//' . $tag, $card);
            if ($nodes && $nodes->length > 0) {
                $t = trim($nodes->item(0)->textContent);
                if (strlen($t) > 5) return $t;
            }
        }
        return '';
    }

    private function extraerFecha(string $texto): ?string {
        // Buscar "Fecha de Publicación: YYYY-MM-DD" o "Fecha de Publicación: DD mes YYYY"
        if (preg_match('/Fecha de Publicaci[oó]n[:\s]+(\d{4}-\d{2}-\d{2})/u', $texto, $m)) {
            return $m[1];
        }
        // Formato "20 abr 2026" o "20 de abril de 2026"
        if (preg_match('/Fecha de Publicaci[oó]n[:\s]+(\d{1,2})[- ](?:de )?([a-záéíóúñ]+)[- ](?:de )?(\d{4})/ui', $texto, $m)) {
            $meses = ['enero'=>'01','febrero'=>'02','marzo'=>'03','abril'=>'04','mayo'=>'05',
                      'junio'=>'06','julio'=>'07','agosto'=>'08','septiembre'=>'09',
                      'octubre'=>'10','noviembre'=>'11','diciembre'=>'12',
                      'ene'=>'01','feb'=>'02','mar'=>'03','abr'=>'04','may'=>'05',
                      'jun'=>'06','jul'=>'07','ago'=>'08','sep'=>'09','oct'=>'10','nov'=>'11','dic'=>'12'];
            $mes = $meses[strtolower($m[2])] ?? null;
            if ($mes) return $m[3] . '-' . $mes . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
        }
        return null;
    }

    private function extraerCategorias(string $texto): array {
        $cats = [];
        $patrones = [
            'tipo'         => '/Tipo de publicaci[oó]n[:\s]*([^\n|]+)/ui',
            'departamento' => '/Departamento[:\s]*([^\n|]+)/ui',
            'municipio'    => '/Municipio[:\s]*([^\n|]+)/ui',
            'entidad'      => '/Entidad[:\s]*([^\n|]+)/ui',
            'especialidad' => '/Especialidad[:\s]*([^\n|]+)/ui',
            'despacho'     => '/Despacho[:\s]*([^\n|]+)/ui',
        ];
        foreach ($patrones as $key => $pat) {
            if (preg_match($pat, $texto, $m)) {
                $cats[$key] = trim(preg_replace('/\s+/', ' ', $m[1]));
            }
        }
        return $cats;
    }

    /**
     * Fallback: parsear el HTML con regex cuando DOMXPath no encuentra cards.
     */
    private function parsearPorRegex(string $html, string $codigoDespacho): array {
        $results = [];

        // Buscar bloques entre "Categorías |" y "VER DETALLE" o siguiente categoría
        preg_match_all('/Categor[ií]as\s*\|(.+?)(?:VER DETALLE|Fecha de Publicaci)/su', $html, $bloques);

        // Buscar fechas en el HTML
        preg_match_all('/Fecha de Publicaci[oó]n[:\s]*([0-9]{4}-[0-9]{2}-[0-9]{2})/ui', $html, $fechas);
        // Buscar títulos
        preg_match_all('/(?:Notificaci[oó]n|Edicto|Traslado|Remate|Aviso)[^<\n]{5,80}/ui', $html, $titulos);

        $nFechas  = count($fechas[1]);
        $nBloques = count($bloques[1]);

        for ($i = 0; $i < $nFechas; $i++) {
            $fecha = $fechas[1][$i];
            $cats  = isset($bloques[1][$i]) ? $this->extraerCategorias($bloques[1][$i]) : [];
            $titulo = isset($titulos[0][$i]) ? trim(strip_tags($titulos[0][$i])) : ('Publicación ' . $fecha);

            $results[] = [
                'titulo'      => $titulo,
                'fecha'       => $fecha,
                'tipo'        => $cats['tipo']         ?? 'Publicación',
                'departamento'=> $cats['departamento'] ?? '',
                'municipio'   => $cats['municipio']    ?? '',
                'entidad'     => $cats['entidad']      ?? '',
                'especialidad'=> $cats['especialidad'] ?? '',
                'despacho'    => $cats['despacho']     ?? $codigoDespacho,
            ];
        }

        return $results;
    }
}
?>