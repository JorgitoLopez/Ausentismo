<?php
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    // 1️⃣ Actualizamos la entrevista
    $sql = "UPDATE entrevistas_falta SET 
            motivo = ?, 
            fecha_suspension = ?, 
            estatus_entrevista = ? 
            WHERE id_entrevista = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['motivo'],
        !empty($data['fecha_suspension']) ? $data['fecha_suspension'] : null,
        $data['estatus_entrevista'],
        $data['id_entrevista']
    ]);

    // 2️⃣ Insertamos en suspensiones si hay fecha
    if(!empty($data['fecha_suspension'])){
        // Primero obtenemos datos del empleado, turno y linea
        $sql2 = "SELECT id_empleado, turno, linea FROM empleados 
                 WHERE id_empleado = (SELECT id_empleado FROM entrevistas_falta WHERE id_entrevista = ?)";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([$data['id_entrevista']]);
        $emp = $stmt2->fetch(PDO::FETCH_ASSOC);

        if($emp){
            $sql3 = "INSERT INTO suspensiones 
                     (id_empleado, fecha, turno, linea, origen, referencia_id, motivo) 
                     VALUES (?,?,?,?,?,?,?)";
            $stmt3 = $pdo->prepare($sql3);
            $stmt3->execute([
                $emp['id_empleado'],
                $data['fecha_suspension'],
                $emp['turno'],
                $emp['linea'],
                'ENTREVISTA',
                $data['id_entrevista'],
                $data['motivo']
            ]);
        }
    }

    echo json_encode(['success'=>true]);

} catch(Exception $e){
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
