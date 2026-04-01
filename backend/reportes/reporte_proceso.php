<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Proceso.php';
require_once __DIR__ . '/../models/Actuacion.php';
require_once __DIR__ . '/../libs/JWT.php';


$id = $_GET['id'] ?? 0;
if (!$id) { echo "Proceso no encontrado"; exit; }

$procesoModel   = new Proceso();
$actuacionModel = new Actuacion();

$proceso     = $procesoModel->getById($id);

// Cargar configuración del despacho
require_once __DIR__ . '/../models/Configuracion.php';
$cfg = (new Configuracion())->getMap();
$nombreEmpresa = $cfg['nombre_empresa'] ?? 'Oficina Jurídica';
$subtitulo     = $cfg['subtitulo']      ?? 'Sistema de Gestión de Procesos Judiciales';
$nit           = $cfg['nit']            ?? '';
$telefono      = $cfg['telefono']       ?? '';
$email         = $cfg['email']          ?? '';
$ciudad        = $cfg['ciudad']         ?? '';
$website       = $cfg['website']        ?? '';
$pieReporte    = $cfg['pie_reporte']    ?? 'Documento generado automáticamente';
$actuaciones = $actuacionModel->getByProceso($id);

if (!$proceso) { echo "Proceso no encontrado"; exit; }

$fechaGeneracion  = date('d/m/Y H:i');
$totalActuaciones = count($actuaciones);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Proceso <?= htmlspecialchars($proceso['numero_radicado']) ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 13px; color: #2c3e50;
    background: #fff; padding: 40px;
    max-width: 900px; margin: 0 auto;
}

/* Encabezado */
.header {
    display: flex; justify-content: space-between; align-items: center;
    padding-bottom: 20px; border-bottom: 3px solid #2c3e50; margin-bottom: 28px;
}
.header-logo { display: flex; align-items: center; gap: 14px; }
.logo-icono  { font-size: 42px; line-height: 1; }
.logo-texto h1 { font-size: 18px; font-weight: 700; color: #2c3e50; }
.logo-texto p  { font-size: 11px; color: #7f8c8d; margin-top: 2px; }
.header-meta { text-align: right; font-size: 11px; color: #7f8c8d; line-height: 1.8; }
.header-meta strong { display: block; font-size: 13px; color: #2c3e50; margin-bottom: 4px; }

/* Título */
.titulo-reporte {
    background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    color: white; padding: 16px 22px; border-radius: 8px;
    margin-bottom: 24px; display: flex;
    justify-content: space-between; align-items: center;
}
.titulo-reporte h2 { font-size: 16px; font-weight: 600; }
.titulo-reporte .radicado { font-size: 13px; opacity: .85; margin-top: 3px; }
.estado-badge {
    padding: 6px 14px; border-radius: 20px; font-size: 12px;
    font-weight: 700; background: rgba(255,255,255,.2);
    border: 1px solid rgba(255,255,255,.4); color: white;
}

/* Secciones */
.seccion { margin-bottom: 24px; }
.seccion-titulo {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .8px; color: #3498db; margin-bottom: 10px;
    padding-bottom: 6px; border-bottom: 1px solid #e8ecef;
}

/* Grid datos */
.datos-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; }
.datos-grid.dos-col { grid-template-columns: repeat(2,1fr); }
.dato-item {
    background: #f8f9fa; padding: 12px 14px;
    border-radius: 6px; border-left: 3px solid #e0e0e0;
}
.dato-item.destacado { border-left-color: #3498db; }
.dato-item.span2 { grid-column: span 2; }
.dato-item.span3 { grid-column: span 3; }
.dato-label { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: #95a5a6; margin-bottom: 4px; }
.dato-valor { font-size: 14px; font-weight: 600; color: #2c3e50; }

/* Tabla actuaciones */
.tabla-actuaciones { width: 100%; border-collapse: collapse; font-size: 12px; }
.tabla-actuaciones thead tr { background: #2c3e50; color: white; }
.tabla-actuaciones th {
    padding: 10px 14px; text-align: left;
    font-size: 11px; text-transform: uppercase;
    letter-spacing: .5px; font-weight: 600;
}
.tabla-actuaciones td {
    padding: 10px 14px; border-bottom: 1px solid #f0f0f0;
    vertical-align: top; line-height: 1.5;
}
.tabla-actuaciones tbody tr:nth-child(even) { background: #f8f9fa; }
.col-num   { width: 40px; color: #bdc3c7; font-size: 11px; }
.col-fecha { width: 100px; white-space: nowrap; color: #3498db; font-weight: 600; }
.col-act   { width: 38%; font-weight: 500; }
.col-obs   { color: #7f8c8d; }

.resumen-num {
    display: inline-flex; align-items: center; gap: 6px;
    background: #eaf4fd; color: #2980b9;
    padding: 5px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600; margin-bottom: 12px;
}
.sin-actuaciones { text-align: center; padding: 30px; color: #bdc3c7; font-style: italic; }

/* Pie */
.footer {
    margin-top: 36px; padding-top: 16px; border-top: 1px solid #e0e0e0;
    display: flex; justify-content: space-between;
    font-size: 10px; color: #aaa;
}

/* Botón imprimir */
.btn-imprimir {
    position: fixed; bottom: 30px; right: 30px;
    background: #2c3e50; color: white; border: none;
    padding: 14px 24px; border-radius: 50px;
    font-size: 14px; font-weight: 600; cursor: pointer;
    box-shadow: 0 4px 20px rgba(0,0,0,.25);
    display: flex; align-items: center; gap: 8px;
    transition: background .2s;
}
.btn-imprimir:hover { background: #3498db; }

@media print {
    .btn-imprimir { display: none !important; }
    body { padding: 20px; }
}
</style>
</head>
<body>

<button class="btn-imprimir" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>

<!-- Encabezado -->
<div class="header">
    <div class="header-logo">
        <div class="logo-icono">⚖️</div>
        <div class="logo-texto">
            <h1><?= htmlspecialchars($nombreEmpresa) ?></h1>
            <p><?= htmlspecialchars($subtitulo) ?></p>
            <?php if ($nit): ?><p style="font-size:10px;color:#95a5a6;margin-top:2px">NIT: <?= htmlspecialchars($nit) ?></p><?php endif; ?>
        </div>
    </div>
    <div class="header-meta">
        <strong>Reporte de Proceso</strong>
        Generado el: <?= $fechaGeneracion ?><br>
        Total actuaciones: <?= $totalActuaciones ?>
        <?php if ($telefono): ?><br><?= htmlspecialchars($telefono) ?><?php endif; ?>
        <?php if ($ciudad): ?><br><?= htmlspecialchars($ciudad) ?><?php endif; ?>
    </div>
</div>

<!-- Título con estado -->
<div class="titulo-reporte">
    <div>
        <h2>Expediente Judicial</h2>
        <div class="radicado">Radicado: <?= htmlspecialchars($proceso['numero_radicado']) ?></div>
    </div>
    <div class="estado-badge"><?= htmlspecialchars($proceso['estado_proceso_nombre'] ?? 'Sin estado') ?></div>
</div>

<!-- Datos del proceso -->
<div class="seccion">
    <div class="seccion-titulo">📋 Datos del Proceso</div>
    <div class="datos-grid">
        <div class="dato-item destacado">
            <div class="dato-label">Número de Radicado</div>
            <div class="dato-valor"><?= htmlspecialchars($proceso['numero_radicado']) ?></div>
        </div>
        <div class="dato-item">
            <div class="dato-label">Tipo de Proceso</div>
            <div class="dato-valor"><?= htmlspecialchars($proceso['tipo_proceso_nombre'] ?? '—') ?></div>
        </div>
        <div class="dato-item">
            <div class="dato-label">Estado</div>
            <div class="dato-valor"><?= htmlspecialchars($proceso['estado_proceso_nombre'] ?? '—') ?></div>
        </div>
        <div class="dato-item">
            <div class="dato-label">Fecha de Inicio</div>
            <div class="dato-valor"><?= $proceso['fecha_inicio'] ? date('d/m/Y', strtotime($proceso['fecha_inicio'])) : '—' ?></div>
        </div>
        <div class="dato-item">
            <div class="dato-label">Fecha de Vencimiento</div>
            <div class="dato-valor"><?= $proceso['fecha_vencimiento'] ? date('d/m/Y', strtotime($proceso['fecha_vencimiento'])) : 'No definida' ?></div>
        </div>
        <div class="dato-item">
            <div class="dato-label">Días activo</div>
            <div class="dato-valor">
                <?php
                    $inicio = new DateTime($proceso['fecha_inicio']);
                    $hoy    = new DateTime();
                    echo $inicio->diff($hoy)->days . ' días';
                ?>
            </div>
        </div>
        <?php if ($proceso['descripcion']): ?>
        <div class="dato-item span3">
            <div class="dato-label">Descripción</div>
            <div class="dato-valor" style="font-weight:400;font-size:13px"><?= htmlspecialchars($proceso['descripcion']) ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Datos del cliente -->
<div class="seccion">
    <div class="seccion-titulo">👤 Datos del Cliente</div>
    <div class="datos-grid dos-col">
        <div class="dato-item destacado span2">
            <div class="dato-label">Nombre Completo</div>
            <div class="dato-valor" style="font-size:16px"><?= htmlspecialchars($proceso['nombre'] . ' ' . $proceso['apellido']) ?></div>
        </div>
    </div>
</div>

<!-- Actuaciones -->
<div class="seccion">
    <div class="seccion-titulo">⚡ Historial de Actuaciones</div>
    <div class="resumen-num">
        📄 <?= $totalActuaciones ?> actuación<?= $totalActuaciones != 1 ? 'es' : '' ?> registrada<?= $totalActuaciones != 1 ? 's' : '' ?>
    </div>

    <?php if (empty($actuaciones)): ?>
        <div class="sin-actuaciones">No hay actuaciones registradas para este proceso</div>
    <?php else: ?>
    <table class="tabla-actuaciones">
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-fecha">Fecha</th>
                <th class="col-act">Actuación</th>
                <th class="col-obs">Observaciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($actuaciones as $i => $a): ?>
            <tr>
                <td class="col-num"><?= $totalActuaciones - $i ?></td>
                <td class="col-fecha"><?= date('d/m/Y', strtotime($a['fecha'])) ?></td>
                <td class="col-act"><?= htmlspecialchars($a['actuacion']) ?></td>
                <td class="col-obs"><?= htmlspecialchars($a['observaciones'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Pie -->
<div class="footer">
    <span><?= htmlspecialchars($nombreEmpresa) ?> — <?= htmlspecialchars($pieReporte) ?></span>
    <span><?= $fechaGeneracion ?></span>
</div>

</body>
</html>