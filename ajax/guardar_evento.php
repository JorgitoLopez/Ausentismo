<?php

require_once __DIR__ . '/../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

try {

    $pdo->beginTransaction();

    // 1. Obtener o crear asistencia_diaria

    $stmt = $pdo->prepare("SELECT id_asistencia FROM asistencia_diaria WHERE id_empleado = ? AND fecha = ?");

    $stmt->execute([$data['id_emp'], $data['fecha']]);

    $res = $stmt->fetch();

    if ($res) {

        $id_asistencia = $res['id_asistencia'];

    } else {

        $stmtEmp = $pdo->prepare("SELECT id_turno, id_departamento, id_linea FROM empleados WHERE id_empleado = ?");

        $stmtEmp->execute([$data['id_emp']]);

        $e = $stmtEmp->fetch();

        $ins = $pdo->prepare("INSERT INTO asistencia_diaria (id_empleado, fecha, id_turno, id_departamento, id_linea, estatus_base) VALUES (?,?,?,?,?,'FALTA')");

        $ins->execute([$data['id_emp'], $data['fecha'], $e['id_turno'], $e['id_departamento'], $e['id_linea']]);

        $id_asistencia = $pdo->lastInsertId();

    }

    // 2. Vincular evento

    $pdo->prepare("DELETE FROM asistencia_eventos WHERE id_asistencia = ?")->execute([$id_asistencia]);

    $insEv = $pdo->prepare("INSERT INTO asistencia_eventos (id_asistencia, id_tipo_evento, observaciones) VALUES (?,?,?)");

    $insEv->execute([$id_asistencia, $data['id_tipo'], $data['obs']]);

    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {

    $pdo->rollBack();

    echo json_encode(['success' => false, 'error' => $e->getMessage()]);

}
 