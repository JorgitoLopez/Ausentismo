<?php

require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['tipo'])) exit;

try {

    $pdo->beginTransaction();

    $tipo = $data['tipo'];

    $id_emp = $data['id_empleado'];

    $fecha = $data['fecha'];

    // REGLA: Solo falta injustificada es INJUSTIFICADO

    $estatus_final = ($tipo == 'falta injustificada') ? 'INJUSTIFICADO' : 'JUSTIFICADO';

    // 1. Actualizar tabla de asistencia

    $sql = "UPDATE asistencia_diaria SET estatus = ?, estatus_real = ? WHERE id_empleado = ? AND fecha = ?";

    $pdo->prepare($sql)->execute([$estatus_final, $tipo, $id_emp, $fecha]);

    // 2. Lógica de Entrevista: SOLO si es INJUSTIFICADO creamos la fila

    if ($estatus_final == 'INJUSTIFICADO') {

        $sql_e = "INSERT IGNORE INTO entrevistas_falta (id_empleado, fecha_falta, estatus_entrevista) VALUES (?, ?, 'PENDIENTE')";

        $pdo->prepare($sql_e)->execute([$id_emp, $fecha]);

    } else {

        // Si antes era injustificado y ahora lo cambiaste a algo justificado, borramos la entrevista

        $sql_d = "DELETE FROM entrevistas_falta WHERE id_empleado = ? AND fecha_falta = ?";

        $pdo->prepare($sql_d)->execute([$id_emp, $fecha]);

    }

    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {

    $pdo->rollBack();

    echo json_encode(['success' => false, 'error' => $e->getMessage()]);

}
 