<?php
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    // 1️⃣ Actualizamos la entrevista y ponemos COMPLTETADA
    $sql = "UPDATE entrevistas_falta SET 
            motivo = ?, 
            estatus_entrevista = 'COMPLETADA'
            WHERE id_entrevista = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['motivo'],
        $data['id_entrevista']
    ]);

    // 2️⃣ Obtenemos datos del empleado y turno/linea
    $sql2 = "SELECT e.id_empleado, e.id_turno, e.id_linea 
             FROM empleados e 
             INNER JOIN entrevistas_falta ef ON ef.id_empleado = e.id_empleado
             WHERE ef.id_entrevista = ?";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([$data['id_entrevista']]);
    $emp = $stmt2->fetch(PDO::FETCH_ASSOC);

    if ($emp && !empty($data['fecha_suspension'])) {
        // 3️⃣ Verificamos si ya existe suspensión para esta entrevista
        $sql3 = "SELECT id_suspension FROM suspensiones 
                 WHERE referencia_id = ? AND origen = 'ENTREVISTA'";
        $stmt3 = $pdo->prepare($sql3);
        $stmt3->execute([$data['id_entrevista']]);
        $susp = $stmt3->fetch(PDO::FETCH_ASSOC);

        if ($susp) {
            // Actualizamos
            $sql4 = "UPDATE suspensiones SET 
                     fecha = ?, 
                     turno = ?, 
                     linea = ?, 
                     motivo = ?
                     WHERE id_suspension = ?";
            $stmt4 = $pdo->prepare($sql4);
            $stmt4->execute([
                $data['fecha_suspension'],
                $emp['id_turno'],
                $emp['id_linea'],
                $data['motivo'],
                $susp['id_suspension']
            ]);
        } else {
            // Insertamos
            $sql5 = "INSERT INTO suspensiones 
                     (id_empleado, fecha, turno, linea, origen, referencia_id, motivo) 
                     VALUES (?,?,?,?,?,?,?)";
            $stmt5 = $pdo->prepare($sql5);
            $stmt5->execute([
                $emp['id_empleado'],
                $data['fecha_suspension'],
                $emp['id_turno'],
                $emp['id_linea'],
                'ENTREVISTA',
                $data['id_entrevista'],
                $data['motivo']
            ]);
        }
    }

    echo json_encode(['success'=>true]);

} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
