<?php
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

$anio  = (int)$data['anio'];
$mes   = (int)$data['mes'];
$linea = $data['linea'];

$sql = "
SELECT 
    ef.fecha_suspension AS fecha,
    COUNT(*) AS total,
    SUM(CASE WHEN e.id_linea = ? THEN 1 ELSE 0 END) AS linea
FROM entrevistas_falta ef
JOIN empleados e ON e.id_empleado = ef.id_empleado
WHERE MONTH(ef.fecha_suspension) = ?
AND YEAR(ef.fecha_suspension) = ?
GROUP BY ef.fecha_suspension
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$linea, $mes, $anio]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
