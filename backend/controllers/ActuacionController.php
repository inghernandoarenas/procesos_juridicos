<?php
require_once __DIR__ . '/../models/Actuacion.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/auth.php';

header('Content-Type: application/json');

$action    = $_POST['action'] ?? $_GET['action'] ?? '';
$actuacion = new Actuacion();

// ── list: actuaciones de un proceso ─────────────────────────
if ($action == 'list') {
    $proceso_id = (int)($_GET['proceso_id'] ?? 0);
    $data = $actuacion->getByProceso($proceso_id);

    $logFile = __DIR__ . '/../../logs/rama_sync.log';
    $ts      = date('H:i:s');
    $nulas_fecha = count(array_filter($data, fn($a) => empty($a['fecha'])));
    $nulos_id    = count(array_filter($data, fn($a) => empty($a['id_api'])));
    file_put_contents($logFile,
        "[$ts] list proceso_id=$proceso_id — filas BD: " . count($data) .
        " — nulas_fecha: $nulas_fecha — nulos_id: $nulos_id\n",
        FILE_APPEND);
    echo json_encode($data);
    exit;
}

// ── recientes: últimas actuaciones por fuente ────────────────
if ($action == 'recientes') {
    $fuente = $_GET['fuente'] ?? 'publicaciones';
    $limite = (int)($_GET['limite'] ?? 15);
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("
        SELECT a.*, p.numero_radicado, c.nombre, c.apellido, p.id as proceso_id
        FROM actuaciones a
        JOIN procesos p ON a.proceso_id = p.id
        JOIN clientes c ON p.cliente_id = c.id
        WHERE a.fuente = :fuente
        ORDER BY a.fecha DESC, a.created_at DESC
        LIMIT :lim
    ");
    $stmt->bindValue(':fuente', $fuente);
    $stmt->bindValue(':lim',    $limite, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ── nuevas_por_proceso: badge de publicaciones recientes ─────
if ($action == 'nuevas_por_proceso') {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("
        SELECT proceso_id, COUNT(*) as total
        FROM actuaciones
        WHERE fuente = 'publicaciones'
          AND fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY proceso_id
    ");
    $stmt->execute();
    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r)
        $map[$r['proceso_id']] = (int)$r['total'];
    echo json_encode($map);
    exit;
}

// ── create ───────────────────────────────────────────────────
if ($action == 'create') {
    $data = [
        ':proceso_id'    => $_POST['proceso_id'],
        ':id_api'        => $_POST['id_api']        ?? null,
        ':fuente'        => $_POST['fuente']         ?? 'manual',
        ':despacho'      => $_POST['despacho']       ?? null,
        ':fecha'         => $_POST['fecha'],
        ':actuacion'     => $_POST['actuacion'],
        ':observaciones' => $_POST['observaciones']  ?? null,
    ];
    echo json_encode(['success' => $actuacion->create($data)]);
    exit;
}

// ── delete ───────────────────────────────────────────────────
if ($action == 'delete') {
    echo json_encode(['success' => $actuacion->delete($_POST['id'] ?? 0)]);
    exit;
}
?>