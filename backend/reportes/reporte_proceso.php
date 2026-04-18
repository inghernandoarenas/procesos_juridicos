<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Proceso.php';
require_once __DIR__ . '/../models/Actuacion.php';
require_once __DIR__ . '/../models/Honorario.php';
require_once __DIR__ . '/../libs/JWT.php';

$id            = (int)($_GET['id'] ?? 0);
$mostrarHon    = isset($_GET['honorarios']) ? ($_GET['honorarios'] === '1') : true;
if (!$id) { echo "Proceso no encontrado"; exit; }

$procesoModel   = new Proceso();
$actuacionModel = new Actuacion();
$honorarioModel = new Honorario();

$proceso     = $procesoModel->getById($id);
if (!$proceso) { echo "Proceso no encontrado"; exit; }

require_once __DIR__ . '/../models/Configuracion.php';
$cfg = (new Configuracion())->getMap();
$nombreEmpresa = $cfg['nombre_empresa'] ?? 'Oficina Jurídica';
$subtitulo     = $cfg['subtitulo']      ?? 'Sistema de Gestión';
$nit           = $cfg['nit']            ?? '';
$telefono      = $cfg['telefono']       ?? '';
$email         = $cfg['email']          ?? '';
$ciudad        = $cfg['ciudad']         ?? '';
$website       = $cfg['website']        ?? '';
$pieReporte    = $cfg['pie_reporte']    ?? 'Documento generado automáticamente';

$actuaciones      = $actuacionModel->getByProceso($id);
$honorarios       = $mostrarHon ? $honorarioModel->getByProceso($id) : [];
$fechaGeneracion  = date('d/m/Y H:i');
$totalActuaciones = count($actuaciones);

// Mapa de fuentes
$fuenteLabels = [
    'rama'    => '⚖️ Rama Judicial',
    'samai'   => '🏛️ SAMAI — Consejo de Estado',
    'penal'   => '🔒 SIUGJ — Penal',
    'tyba'    => '📁 TYBA',
    'ninguna' => 'Sin portal asignado',
];
$fuenteLabel = $fuenteLabels[$proceso['fuente_consulta'] ?? 'ninguna'] ?? '—';

// Resumen financiero
$totalCobrado = $totalPagado = $totalPendiente = 0;
foreach ($honorarios as $h) {
    $totalCobrado += $h['valor'];
    if ($h['estado'] === 'pagado')   $totalPagado   += $h['valor'];
    if ($h['estado'] === 'pendiente') $totalPendiente += $h['valor'];
}

// Datos del cliente
$db          = (new Database())->getConnection();
$stmtCli     = $db->prepare("SELECT * FROM clientes WHERE id = :id LIMIT 1");
$stmtCli->execute([':id' => $proceso['cliente_id']]);
$clienteData = $stmtCli->fetch(PDO::FETCH_ASSOC) ?: [];

function fmtCOP($v) { return '$' . number_format($v, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Proceso <?= htmlspecialchars($proceso['numero_radicado']) ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',Arial,sans-serif; font-size:13px; color:#2c3e50; background:#fff; padding:40px; max-width:900px; margin:0 auto; }

/* Toolbar solo en pantalla */
.toolbar { display:flex; gap:10px; margin-bottom:24px; padding:12px 16px; background:#f8f9fa; border-radius:8px; border:1px solid #e0e0e0; align-items:center; }
.toolbar button { padding:8px 16px; border:none; border-radius:6px; cursor:pointer; font-size:13px; font-weight:600; display:flex; align-items:center; gap:6px; }
.btn-print { background:#2c3e50; color:white; }
.btn-print:hover { background:#1a252f; }
.btn-toggle { background:#eaf4fd; color:#2980b9; }
.btn-toggle:hover { background:#d6eaf8; }
.toolbar-label { font-size:13px; color:#7f8c8d; margin-left:auto; }
@media print { .toolbar { display:none; } }

/* Header */
.header { display:flex; justify-content:space-between; align-items:center; padding-bottom:20px; border-bottom:3px solid #2c3e50; margin-bottom:28px; }
.logo-texto h1 { font-size:18px; font-weight:700; color:#2c3e50; }
.logo-texto p  { font-size:11px; color:#7f8c8d; margin-top:2px; }
.header-meta { text-align:right; font-size:11px; color:#7f8c8d; line-height:1.8; }
.header-meta strong { display:block; font-size:13px; color:#2c3e50; margin-bottom:4px; }

/* Título */
.titulo-reporte { background:linear-gradient(135deg,#2c3e50 0%,#3498db 100%); color:white; padding:16px 22px; border-radius:8px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; }
.titulo-reporte h2 { font-size:16px; font-weight:600; }
.titulo-reporte .radicado { font-size:12px; opacity:.85; margin-top:3px; }
.estado-badge { padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700; background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.4); color:white; }

/* Secciones */
.seccion { margin-bottom:24px; }
.seccion-titulo { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#3498db; margin-bottom:10px; padding-bottom:6px; border-bottom:1px solid #e8ecef; }

/* Grids de datos */
.datos-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
.datos-grid.dos-col { grid-template-columns:repeat(2,1fr); }
.dato-item { background:#f8f9fa; padding:10px 14px; border-radius:7px; border-left:3px solid #e0e0e0; }
.dato-item.destacado { border-left-color:#3498db; }
.dato-item.span2 { grid-column:span 2; }
.dato-item.span3 { grid-column:span 3; }
.dato-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#95a5a6; margin-bottom:4px; }
.dato-valor { font-size:14px; font-weight:600; color:#2c3e50; }

/* Fuente badge */
.fuente-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; }
.fuente-rama  { background:#eaf4fd; color:#2980b9; }
.fuente-samai { background:#f3e8ff; color:#7c3aed; }
.fuente-penal { background:#fdecea; color:#c0392b; }
.fuente-tyba  { background:#fef3c7; color:#92400e; }
.fuente-ninguna { background:#f0f0f0; color:#6b7280; }

/* KPIs financieros */
.kpi-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:16px; }
.kpi-box { padding:12px 16px; border-radius:8px; text-align:center; }
.kpi-box.cobrado  { background:#eaf4fd; }
.kpi-box.pagado   { background:#eafaf1; }
.kpi-box.pendiente{ background:#fef9ec; }
.kpi-num  { font-size:18px; font-weight:700; }
.kpi-label{ font-size:10px; text-transform:uppercase; letter-spacing:.4px; margin-top:2px; color:#7f8c8d; }

/* Tabla actuaciones */
.tabla-actuaciones { width:100%; border-collapse:collapse; font-size:12px; }
.tabla-actuaciones thead tr { background:#2c3e50; color:white; }
.tabla-actuaciones th { padding:8px 10px; text-align:left; font-size:10px; text-transform:uppercase; letter-spacing:.4px; }
.tabla-actuaciones td { padding:8px 10px; border-bottom:1px solid #f0f0f0; vertical-align:top; }
.tabla-actuaciones tbody tr:nth-child(even) { background:#f9f9f9; }
.tabla-actuaciones tbody tr:hover { background:#eaf4fd; }
.col-num { width:35px; text-align:center; }
.col-fecha { width:90px; white-space:nowrap; }
.col-act { width:40%; }

/* Tabla honorarios */
.tabla-honorarios { width:100%; border-collapse:collapse; font-size:12px; }
.tabla-honorarios thead tr { background:#27ae60; color:white; }
.tabla-honorarios th { padding:8px 10px; text-align:left; font-size:10px; text-transform:uppercase; letter-spacing:.4px; }
.tabla-honorarios td { padding:8px 10px; border-bottom:1px solid #f0f0f0; vertical-align:top; }
.tabla-honorarios tbody tr:nth-child(even) { background:#f9f9f9; }
.badge-estado { padding:2px 8px; border-radius:10px; font-size:10px; font-weight:700; }
.badge-pagado   { background:#eafaf1; color:#27ae60; }
.badge-pendiente{ background:#fef9ec; color:#f39c12; }
.badge-vencido  { background:#fdecea; color:#e74c3c; }

/* Footer */
.footer { margin-top:32px; padding-top:12px; border-top:1px solid #e8ecef; display:flex; justify-content:space-between; font-size:10px; color:#95a5a6; }
.sin-actuaciones { text-align:center; padding:20px; color:#bdc3c7; font-style:italic; }
.resumen-num { font-size:12px; color:#7f8c8d; margin-bottom:10px; }
</style>
</head>
<body>

<!-- Toolbar (solo en pantalla, no en impresión) -->
<div class="toolbar">
    <button class="btn-print" onclick="window.print()">
        <i>🖨️</i> Imprimir / PDF
    </button>
    <button class="btn-toggle" id="btnToggleHon" onclick="toggleHonorarios()">
        💰 <?= $mostrarHon ? 'Ocultar honorarios' : 'Mostrar honorarios' ?>
    </button>
    <span class="toolbar-label">Generado: <?= $fechaGeneracion ?></span>
</div>

<!-- Encabezado -->
<div class="header">
    <div style="display:flex;align-items:center;gap:14px">
        <div style="font-size:42px;line-height:1">⚖️</div>
        <div class="logo-texto">
            <h1><?= htmlspecialchars($nombreEmpresa) ?></h1>
            <p><?= htmlspecialchars($subtitulo) ?></p>
            <?php if ($nit): ?><p>NIT: <?= htmlspecialchars($nit) ?></p><?php endif; ?>
        </div>
    </div>
    <div class="header-meta">
        <strong>Reporte de Proceso Judicial</strong>
        <?php if ($telefono): ?>📞 <?= htmlspecialchars($telefono) ?><br><?php endif; ?>
        <?php if ($email): ?>✉️ <?= htmlspecialchars($email) ?><br><?php endif; ?>
        <?php if ($ciudad): ?>📍 <?= htmlspecialchars($ciudad) ?><br><?php endif; ?>
        <?= $fechaGeneracion ?>
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
            <div class="dato-valor"><?php
                $inicio = new DateTime($proceso['fecha_inicio']);
                echo $inicio->diff(new DateTime())->days . ' días';
            ?></div>
        </div>
        <div class="dato-item span3">
            <div class="dato-label">Portal de Consulta</div>
            <div class="dato-valor">
                <span class="fuente-badge fuente-<?= $proceso['fuente_consulta'] ?? 'ninguna' ?>">
                    <?= $fuenteLabel ?>
                </span>
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
        <?php if (!empty($clienteData['tipo_identificacion'])): ?>
        <div class="dato-item">
            <div class="dato-label">Identificación</div>
            <div class="dato-valor" style="font-weight:400"><?= htmlspecialchars($clienteData['tipo_identificacion'] . ' ' . $clienteData['numero_identificacion']) ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($clienteData['email'])): ?>
        <div class="dato-item">
            <div class="dato-label">Email</div>
            <div class="dato-valor" style="font-weight:400"><?= htmlspecialchars($clienteData['email']) ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($clienteData['telefono'])): ?>
        <div class="dato-item">
            <div class="dato-label">Teléfono</div>
            <div class="dato-valor" style="font-weight:400"><?= htmlspecialchars($clienteData['telefono']) ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Honorarios (toggle) -->
<div id="seccionHonorarios" style="<?= $mostrarHon ? '' : 'display:none' ?>">
<div class="seccion">
    <div class="seccion-titulo">💰 Información Financiera</div>
    <div class="kpi-grid">
        <div class="kpi-box cobrado">
            <div class="kpi-num" style="color:#2980b9"><?= fmtCOP($totalCobrado) ?></div>
            <div class="kpi-label">Total cobrado</div>
        </div>
        <div class="kpi-box pagado">
            <div class="kpi-num" style="color:#27ae60"><?= fmtCOP($totalPagado) ?></div>
            <div class="kpi-label">Pagado</div>
        </div>
        <div class="kpi-box pendiente">
            <div class="kpi-num" style="color:#f39c12"><?= fmtCOP($totalPendiente) ?></div>
            <div class="kpi-label">Pendiente</div>
        </div>
    </div>
    <?php if (!empty($honorarios)): ?>
    <table class="tabla-honorarios">
        <thead>
            <tr>
                <th>Concepto</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Causación</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($honorarios as $h): ?>
            <tr>
                <td><?= htmlspecialchars($h['concepto']) ?>
                    <?php if ($h['observaciones']): ?>
                    <br><small style="color:#95a5a6"><?= htmlspecialchars($h['observaciones']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($h['tipo']) ?></td>
                <td style="font-weight:700"><?= fmtCOP($h['valor']) ?></td>
                <td><?= $h['fecha_causacion'] ? date('d/m/Y', strtotime($h['fecha_causacion'])) : '—' ?></td>
                <td><span class="badge-estado badge-<?= $h['estado'] ?>"><?= $h['estado'] ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="sin-actuaciones">No hay cobros registrados</div>
    <?php endif; ?>
</div>
</div>

<!-- Actuaciones -->
<div class="seccion">
    <div class="seccion-titulo">⚡ Historial de Actuaciones</div>
    <div class="resumen-num">📄 <?= $totalActuaciones ?> actuación<?= $totalActuaciones != 1 ? 'es' : '' ?> registrada<?= $totalActuaciones != 1 ? 's' : '' ?></div>
    <?php if (empty($actuaciones)): ?>
        <div class="sin-actuaciones">No hay actuaciones registradas para este proceso</div>
    <?php else: ?>
    <table class="tabla-actuaciones">
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-fecha">Fecha</th>
                <th class="col-act">Actuación</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($actuaciones as $i => $a): ?>
            <tr>
                <td class="col-num"><?= $totalActuaciones - $i ?></td>
                <td class="col-fecha"><?= date('d/m/Y', strtotime($a['fecha'])) ?></td>
                <td class="col-act"><?= htmlspecialchars($a['actuacion']) ?></td>
                <td><?= htmlspecialchars($a['observaciones'] ?? '—') ?></td>
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

<script>
function toggleHonorarios() {
    const sec = document.getElementById('seccionHonorarios');
    const btn = document.getElementById('btnToggleHon');
    const visible = sec.style.display !== 'none';
    sec.style.display = visible ? 'none' : '';
    btn.textContent = visible ? '💰 Mostrar honorarios' : '💰 Ocultar honorarios';
}
</script>
</body>
</html>