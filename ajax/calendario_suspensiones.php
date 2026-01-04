<?php
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

$anio = $data['anio'];
$mes  = str_pad($data['mes'], 2, '0', STR_PAD_LEFT);
$turno = $data['turno'];
$linea = $data['linea'];

// Rango de fechas del mes
$inicio = "$anio-$mes-01";
$fin    = "$anio-$mes-31";

// Obtenemos total de suspensiones por día
$sql = "SELECT fecha, 
               COUNT(*) as total,
               SUM(CASE WHEN turno = :turno AND linea = :linea THEN 1 ELSE 0 END) as turno_linea
        FROM suspensiones
        WHERE fecha BETWEEN :inicio AND :fin
        GROUP BY fecha";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':inicio'=>$inicio,
    ':fin'=>$fin,
    ':turno'=>$turno,
    ':linea'=>$linea
]);

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($result);
