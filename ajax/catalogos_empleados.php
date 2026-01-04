<?php
require_once '../config/db.php';
echo json_encode([
'turnos'=>$pdo->query("SELECT DISTINCT id_turno FROM empleados")->fetchAll(PDO::FETCH_COLUMN),
'departamentos'=>$pdo->query("SELECT DISTINCT id_departamento FROM empleados WHERE id_departamento IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN)
]);
