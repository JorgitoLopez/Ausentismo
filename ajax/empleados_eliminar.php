<?php
require_once '../config/db.php';
$d = json_decode(file_get_contents("php://input"), true);
if (isset($d['id_empleado'])) {
    $stmt = $pdo->prepare("DELETE FROM empleados WHERE id_empleado = ?");
    $stmt->execute([$d['id_empleado']]);
    echo json_encode(["status" => "ok"]);
}