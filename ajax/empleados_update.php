<?php
require_once '../config/db.php';
$d = json_decode(file_get_contents("php://input"), true);
if ($d) {
    $sql = "UPDATE empleados SET nombre_completo=?, id_turno=?, id_departamento=?, id_linea=?, fecha_ingreso=? WHERE id_empleado=?";
    $pdo->prepare($sql)->execute([
        $d['nombre_completo'], $d['id_turno'], $d['id_departamento'], 
        $d['id_linea'], $d['fecha_ingreso'], $d['id_empleado']
    ]);
    echo json_encode(["status" => "ok"]);
}