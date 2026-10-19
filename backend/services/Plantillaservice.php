<?php
/**
 * PlantillaService — genera documentos Word (.docx) a partir de plantillas.
 *
 * Una plantilla es un .docx normal (con membrete, formato, etc.) que contiene
 * variables del tipo {{grupo.campo}}, p. ej. {{cliente.nombre_completo}}.
 * Al generar, cada variable se reemplaza con datos de la BD (proceso,
 * cliente, firma, abogado que genera, fecha).
 *
 * Word suele partir un mismo texto en varios "runs" internos (p. ej. al
 * cambiar de formato o corregir ortografía en mitad de una variable). El
 * reemplazo trabaja por párrafo, uniendo los textos de sus runs, así que
 * funciona aunque Word haya partido la variable.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Proceso.php';
require_once __DIR__ . '/../models/Configuracion.php';

class PlantillaService {

    const W_NS      = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    const VACIO     = '__________';
    const MESES     = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];

    public static function dirPlantillas(): string {
        return realpath(__DIR__ . '/../../plantillas') ?: (__DIR__ . '/../../plantillas');
    }

    /** Catálogo de variables disponibles: clave => [etiqueta, grupo]. */
    public static function catalogo(): array {
        return [
            // Firma / empresa (tabla configuracion)
            'empresa.nombre'    => ['Nombre de la firma',          'Firma'],
            'empresa.nit'       => ['NIT de la firma',             'Firma'],
            'empresa.telefono'  => ['Teléfono de la firma',        'Firma'],
            'empresa.email'     => ['Email de la firma',           'Firma'],
            'empresa.direccion' => ['Dirección de la firma',       'Firma'],
            'empresa.ciudad'    => ['Ciudad de la firma',          'Firma'],
            'empresa.website'   => ['Sitio web de la firma',       'Firma'],
            // Cliente
            'cliente.nombre'                => ['Nombres del cliente',          'Cliente'],
            'cliente.apellido'              => ['Apellidos del cliente',        'Cliente'],
            'cliente.nombre_completo'       => ['Nombre completo del cliente',  'Cliente'],
            'cliente.tipo_identificacion'   => ['Tipo de documento',            'Cliente'],
            'cliente.numero_identificacion' => ['Número de documento',          'Cliente'],
            'cliente.email'                 => ['Email del cliente',            'Cliente'],
            'cliente.telefono'              => ['Teléfono del cliente',         'Cliente'],
            'cliente.direccion'             => ['Dirección del cliente',        'Cliente'],
            // Proceso
            'proceso.radicado'              => ['Número de radicado',           'Proceso'],
            'proceso.tipo'                  => ['Especialidad / tipo de proceso','Proceso'],
            'proceso.estado'                => ['Estado del proceso',           'Proceso'],
            'proceso.descripcion'           => ['Descripción del proceso',      'Proceso'],
            'proceso.fecha_inicio'          => ['Fecha de inicio',              'Proceso'],
            'proceso.fecha_vencimiento'     => ['Fecha de vencimiento',         'Proceso'],
            'proceso.portal'                => ['Portal de consulta',           'Proceso'],
            'proceso.despacho'              => ['Despacho',                     'Proceso'],
            'proceso.entidad'               => ['Entidad',                      'Proceso'],
            'proceso.departamento'          => ['Departamento',                 'Proceso'],
            'proceso.municipio'             => ['Municipio',                    'Proceso'],
            'proceso.ultima_actuacion'      => ['Última actuación',             'Proceso'],
            'proceso.ultima_actuacion_fecha'=> ['Fecha de la última actuación', 'Proceso'],
            // Abogado que genera el documento (usuario con sesión)
            'abogado.nombre'    => ['Nombre del abogado',   'Abogado'],
            'abogado.cargo'     => ['Cargo del abogado',    'Abogado'],
            'abogado.email'     => ['Email del abogado',    'Abogado'],
            'abogado.telefono'  => ['Teléfono del abogado', 'Abogado'],
            // Fecha
            'fecha.hoy'            => ['Fecha larga (19 de octubre de 2026)',               'Fecha'],
            'fecha.corta'          => ['Fecha corta (19/10/2026)',                          'Fecha'],
            'fecha.dia'            => ['Día',                                               'Fecha'],
            'fecha.mes'            => ['Mes (en letras)',                                   'Fecha'],
            'fecha.anio'           => ['Año',                                               'Fecha'],
            'fecha.lugar_y_fecha'  => ['Ciudad y fecha (Barranquilla, 19 de octubre de 2026)','Fecha'],
        ];
    }

    private static function fechaLarga(?string $ymd): string {
        if (!$ymd) return '';
        $t = strtotime($ymd);
        if (!$t) return '';
        return date('j', $t) . ' de ' . self::MESES[(int)date('n', $t) - 1] . ' de ' . date('Y', $t);
    }

    private static function fechaCorta(?string $ymd): string {
        $t = $ymd ? strtotime($ymd) : false;
        return $t ? date('d/m/Y', $t) : '';
    }

    /** Arma el arreglo plano clave => valor con los datos del proceso y su cliente. */
    public static function construirDatos(int $proceso_id, int $usuario_id): array {
        $db   = (new Database())->getConnection();
        $proc = (new Proceso())->getById($proceso_id);
        if (!$proc) throw new Exception('Proceso no encontrado');

        $st = $db->prepare("SELECT * FROM clientes WHERE id = :id");
        $st->execute([':id' => $proc['cliente_id']]);
        $cli = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        $st = $db->prepare("SELECT nombre, email, telefono, cargo FROM usuarios WHERE id = :id");
        $st->execute([':id' => $usuario_id]);
        $usr = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        $st = $db->prepare("SELECT actuacion, fecha FROM actuaciones WHERE proceso_id = :id ORDER BY fecha DESC, id DESC LIMIT 1");
        $st->execute([':id' => $proceso_id]);
        $act = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        $cfg = (new Configuracion())->getMap();

        $portales = ['rama' => 'Rama Judicial', 'samai' => 'SAMAI', 'tyba' => 'TYBA', 'penal' => 'SIUGJ'];
        $ciudad   = trim($cfg['ciudad'] ?? '');
        $hoy      = date('Y-m-d');

        return [
            'empresa.nombre'    => $cfg['nombre_empresa'] ?? '',
            'empresa.nit'       => $cfg['nit'] ?? '',
            'empresa.telefono'  => $cfg['telefono'] ?? '',
            'empresa.email'     => $cfg['email'] ?? '',
            'empresa.direccion' => $cfg['direccion'] ?? '',
            'empresa.ciudad'    => $ciudad,
            'empresa.website'   => $cfg['website'] ?? '',

            'cliente.nombre'                => $cli['nombre'] ?? '',
            'cliente.apellido'              => $cli['apellido'] ?? '',
            'cliente.nombre_completo'       => trim(($cli['nombre'] ?? '') . ' ' . ($cli['apellido'] ?? '')),
            'cliente.tipo_identificacion'   => $cli['tipo_identificacion'] ?? '',
            'cliente.numero_identificacion' => $cli['numero_identificacion'] ?? '',
            'cliente.email'                 => $cli['email'] ?? '',
            'cliente.telefono'              => $cli['telefono'] ?? '',
            'cliente.direccion'             => $cli['direccion'] ?? '',

            'proceso.radicado'              => $proc['numero_radicado'] ?? '',
            'proceso.tipo'                  => $proc['tipo_proceso_nombre'] ?? ($proc['tipo_proceso'] ?? ''),
            'proceso.estado'                => $proc['estado_proceso_nombre'] ?? ($proc['estado'] ?? ''),
            'proceso.descripcion'           => $proc['descripcion'] ?? '',
            'proceso.fecha_inicio'          => self::fechaLarga($proc['fecha_inicio'] ?? null),
            'proceso.fecha_vencimiento'     => self::fechaLarga($proc['fecha_vencimiento'] ?? null),
            'proceso.portal'                => $portales[$proc['fuente_consulta'] ?? ''] ?? '',
            'proceso.despacho'              => $proc['despacho_nombre'] ?? '',
            'proceso.entidad'               => $proc['entidad_nombre'] ?? '',
            'proceso.departamento'          => $proc['departamento_nombre'] ?? '',
            'proceso.municipio'             => $proc['municipio_nombre'] ?? '',
            'proceso.ultima_actuacion'      => $act['actuacion'] ?? '',
            'proceso.ultima_actuacion_fecha'=> self::fechaLarga($act['fecha'] ?? null),

            'abogado.nombre'    => $usr['nombre'] ?? '',
            'abogado.cargo'     => $usr['cargo'] ?? '',
            'abogado.email'     => $usr['email'] ?? '',
            'abogado.telefono'  => $usr['telefono'] ?? '',

            'fecha.hoy'            => self::fechaLarga($hoy),
            'fecha.corta'          => self::fechaCorta($hoy),
            'fecha.dia'            => date('j'),
            'fecha.mes'            => self::MESES[(int)date('n') - 1],
            'fecha.anio'           => date('Y'),
            'fecha.lugar_y_fecha'  => ($ciudad !== '' ? $ciudad . ', ' : '') . self::fechaLarga($hoy),
        ];
    }

    /**
     * Genera el .docx reemplazando las variables.
     * Devuelve ['archivo' => ruta temporal, 'faltantes' => [etiquetas], 'desconocidas' => [claves]].
     * El llamador debe borrar el archivo temporal.
     */
    public static function render(string $rutaPlantilla, array $datos): array {
        if (!class_exists('ZipArchive')) throw new Exception('La extensión zip de PHP no está habilitada');
        if (!is_file($rutaPlantilla)) throw new Exception('No se encontró el archivo de la plantilla');

        $tmp = tempnam(sys_get_temp_dir(), 'plt');
        if (!copy($rutaPlantilla, $tmp)) throw new Exception('No se pudo preparar el documento');

        $zip = new ZipArchive();
        if ($zip->open($tmp) !== true) { @unlink($tmp); throw new Exception('La plantilla no es un .docx válido'); }

        $catalogo     = self::catalogo();
        $faltantes    = [];
        $desconocidas = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nombre = $zip->getNameIndex($i);
            if (!preg_match('#^word/(document|header\d*|footer\d*)\.xml$#', $nombre)) continue;

            $xml = $zip->getFromIndex($i);
            $dom = new DOMDocument();
            if (!$dom->loadXML($xml, LIBXML_NONET)) continue;

            $xp = new DOMXPath($dom);
            $xp->registerNamespace('w', self::W_NS);

            foreach ($xp->query('//w:p') as $parrafo) {
                $nodos = [];
                foreach ($xp->query('.//w:t', $parrafo) as $t) $nodos[] = $t;
                if (!$nodos) continue;

                $textos = array_map(function ($n) { return $n->textContent; }, $nodos);
                $full   = implode('', $textos);
                if (strpos($full, '{{') === false) continue;

                if (!preg_match_all('/\{\{\s*([a-z_]+\.[a-z_]+)\s*\}\}/', $full, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) continue;

                $reemplazos = [];
                foreach ($m as $hit) {
                    $clave = $hit[1][0];
                    $ini   = $hit[0][1];
                    $fin   = $ini + strlen($hit[0][0]);
                    if (!isset($catalogo[$clave])) {
                        $desconocidas[$clave] = true;
                        $valor = $hit[0][0]; // se deja visible para que se note el error de tipeo
                    } else {
                        $valor = trim((string)($datos[$clave] ?? ''));
                        $valor = preg_replace('/\s*[\r\n]+\s*/', ' ', $valor);
                        if ($valor === '') {
                            $faltantes[$clave] = $catalogo[$clave][0];
                            $valor = self::VACIO;
                        }
                    }
                    $reemplazos[] = [$ini, $fin, $valor];
                }

                // Reescribir el texto de cada nodo respetando los límites de los runs
                $pos0 = 0;
                foreach ($nodos as $idx => $nodo) {
                    $ns = $pos0;
                    $ne = $ns + strlen($textos[$idx]);
                    $pos0 = $ne;
                    $out = '';
                    $pos = $ns;
                    foreach ($reemplazos as [$ms, $me, $val]) {
                        if ($me <= $ns || $ms >= $ne) continue;
                        $segIni = max($ms, $ns);
                        $segFin = min($me, $ne);
                        $out .= substr($full, $pos, $segIni - $pos);
                        if ($ms >= $ns) $out .= $val;
                        $pos = $segFin;
                    }
                    $out .= substr($full, $pos, $ne - $pos);
                    if ($out !== $textos[$idx]) {
                        $nodo->nodeValue = '';
                        $nodo->appendChild($dom->createTextNode($out));
                        $nodo->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                    }
                }
            }

            $zip->addFromString($nombre, $dom->saveXML());
        }

        $zip->close();
        return ['archivo' => $tmp, 'faltantes' => array_values($faltantes), 'desconocidas' => array_keys($desconocidas)];
    }

    /** Comprueba que un archivo subido sea un .docx real (zip con word/document.xml). */
    public static function esDocxValido(string $ruta): bool {
        if (!class_exists('ZipArchive')) return false;
        $zip = new ZipArchive();
        if ($zip->open($ruta) !== true) return false;
        $ok = $zip->locateName('word/document.xml') !== false;
        $zip->close();
        return $ok;
    }
}