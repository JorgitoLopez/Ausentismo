<?php
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$anio  = intval($data['anio']);
$mes   = intval($data['mes']);
$turno = intval($data['turno']);
$linea = isset($data['linea']) ? $data['linea'] : '';

$fecha_inicio = sprintf("%04d-%02d-01", $anio, $mes);
$fecha_fin    = sprintf("%04d-%02d-%02d", $anio, $mes, cal_days_in_month(CAL_GREGORIAN, $mes, $anio));

$result = [];

// 1. Suspensiones del turno
$stmtTurno = $pdo->prepare("
    SELECT fecha, COUNT(*) AS total_turno
    FROM suspensiones
    WHERE fecha BETWEEN :inicio AND :fin
      AND turno = :turno
    GROUP BY fecha
");
$stmtTurno->execute(['inicio'=>$fecha_inicio, 'fin'=>$fecha_fin, 'turno'=>$turno]);
$turnoData = $stmtTurno->fetchAll(PDO::FETCH_KEY_PAIR); 

// 2. Suspensiones de la línea en ese turno
$stmtLinea = $pdo->prepare("
    SELECT fecha, COUNT(*) AS total_linea
    FROM suspensiones
    WHERE fecha BETWEEN :inicio AND :fin
      AND turno = :turno
      AND linea = :linea
    GROUP BY fecha
");
$stmtLinea->execute(['inicio'=>$fecha_inicio, 'fin'=>$fecha_fin, 'turno'=>$turno, 'linea'=>$linea]);
$lineaData = $stmtLinea->fetchAll(PDO::FETCH_KEY_PAIR);

$days = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
for($d=1; $d<=$days; $d++){
    $fecha = sprintf("%04d-%02d-%02d", $anio, $mes, $d);
    $result[] = [
        'fecha' => $fecha,
        'total_turno' => isset($turnoData[$fecha]) ? intval($turnoData[$fecha]) : 0,
        'total_linea' => isset($lineaData[$fecha]) ? intval($lineaData[$fecha]) : 0
    ];
}

header('Content-Type: application/json');
echo json_encode($result);