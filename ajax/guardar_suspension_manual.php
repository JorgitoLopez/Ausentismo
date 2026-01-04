<?php
require_once '../config/db.php';
$data = json_decode(file_get_contents('php://input'), true);

try {
    if(!empty($data['id'])) {
        // Update
        $sql = "UPDATE suspensiones SET fecha=?, motivo=?, id_empleado=?, turno=?, linea=? WHERE id_suspension=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data['fecha'], $data['motivo'], $data['id_empleado'], $data['turno'], $data['linea'], $data['id']]);
    } else {
        // Insert
        $sql = "INSERT INTO suspensiones (id_empleado, fecha, turno, linea, origen, motivo) VALUES (?,?,?,?,'OTRO',?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data['id_empleado'], $data['fecha'], $data['turno'], $data['linea'], $data['motivo']]);
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}