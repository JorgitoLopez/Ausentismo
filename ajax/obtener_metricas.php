<?php

require_once '../config/db.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) exit(json_encode(['error' => 'No data']));

$inicio = $input['inicio'];

$fin    = $input['fin'];

$turno  = $input['turno'];

$deptos = $input['deptos'] ?? [];

// Construcción de WHERE con lógica posicional pura (?)

$where = "WHERE fecha BETWEEN ? AND ?";

$params = [$inicio, $fin];

if (!empty($turno)) {

    $where .= " AND id_turno = ?";

    $params[] = $turno;

}

if (!empty($deptos)) {

    $placeholders = implode(',', array_fill(0, count($deptos), '?'));

    $where .= " AND id_departamento IN ($placeholders)";

    foreach($deptos as $d) $params[] = $d;

}

// 1. STATS GLOBALES

$sql_stats = "SELECT 

    COUNT(*) as total,

    SUM(CASE WHEN estatus = 'JUSTIFICADO' AND estatus_real = 'asistencia' THEN 1 ELSE 0 END) as asist,

    SUM(CASE WHEN estatus = 'INJUSTIFICADO' THEN 1 ELSE 0 END) as faltas,

    SUM(CASE WHEN estatus_real != 'asistencia' AND estatus_real IS NOT NULL THEN 1 ELSE 0 END) as incidencias

    FROM asistencia_diaria $where";

$stmt = $pdo->prepare($sql_stats);

$stmt->execute($params);

$s = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. PARETO (Causas)

$sql_p = "SELECT estatus_real, COUNT(*) as cant FROM asistencia_diaria $where 

          AND estatus_real != 'asistencia' AND estatus_real IS NOT NULL 

          GROUP BY estatus_real ORDER BY cant DESC";

$stmt_p = $pdo->prepare($sql_p);

$stmt_p->execute($params);

$rows_p = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

$labels_p = []; $vals_p = []; $sum = 0;

foreach($rows_p as $r) { $labels_p[] = $r['estatus_real']; $vals_p[] = (int)$r['cant']; $sum += $r['cant']; }

$acum = []; $curr = 0;

foreach($vals_p as $v) { $curr += $v; $acum[] = ($sum > 0) ? round(($curr / $sum) * 100, 1) : 0; }

// 3. TENDENCIA

$sql_t = "SELECT fecha, 

          SUM(CASE WHEN estatus = 'INJUSTIFICADO' THEN 1 ELSE 0 END) as f,

          SUM(CASE WHEN estatus = 'JUSTIFICADO' AND estatus_real != 'asistencia' THEN 1 ELSE 0 END) as j

          FROM asistencia_diaria $where GROUP BY fecha ORDER BY fecha ASC";

$stmt_t = $pdo->prepare($sql_t);

$stmt_t->execute($params);

$rows_t = $stmt_t->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([

    'stats' => [

        'total' => (int)$s['total'],

        'porc_asistencia' => $s['total'] > 0 ? round(($s['asist']/$s['total'])*100, 1) : 0,

        'porc_ausente' => $s['total'] > 0 ? round(($s['faltas']/$s['total'])*100, 1) : 0,

        'incidencias' => (int)$s['incidencias']

    ],

    'pastel' => [

        'labels' => ['Asistencia', 'Faltas', 'Justificados'],

        'values' => [(int)$s['asist'], (int)$s['faltas'], (int)($s['total'] - $s['asist'] - $s['faltas'])]

    ],

    'pareto' => ['labels' => $labels_p, 'values' => $vals_p, 'acumulado' => $acum],

    'lineal' => [

        'labels' => array_column($rows_t, 'fecha'),

        'faltas' => array_column($rows_t, 'f'),

        'justificados' => array_column($rows_t, 'j')

    ]

]);
 