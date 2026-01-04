<?php

require_once __DIR__ . '/../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

$fecha = $data['fecha'];

$turno = $data['turno'];

$dep = $data['dep'];

$sql = "SELECT e.id_empleado, e.nombre_completo, e.id_turno, e.id_departamento, 

               c.hora as checada, 

               te.nombre_evento as evento_rh

        FROM empleados e

        LEFT JOIN checadas c ON e.id_empleado = c.id_empleado AND c.fecha = :fecha

        LEFT JOIN asistencia_diaria ad ON e.id_empleado = ad.id_empleado AND ad.fecha = :fecha

        LEFT JOIN asistencia_eventos ae ON ad.id_asistencia = ae.id_asistencia

        LEFT JOIN tipo_evento_asistencia te ON ae.id_tipo_evento = te.id_tipo_evento

        WHERE 1=1";

if ($turno) $sql .= " AND e.id_turno = :turno";

if ($dep) $sql .= " AND e.id_departamento = :dep";

$stmt = $pdo->prepare($sql);

$params = [':fecha' => $fecha];

if ($turno) $params[':turno'] = $turno;

if ($dep) $params[':dep'] = $dep;

$stmt->execute($params);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {

    $asistencia = !is_null($r['checada']);

    $estatus_reloj = $asistencia 

        ? '<span class="badge bg-success">'.$r['checada'].'</span>' 

        : '<span class="badge bg-danger">NO CHECÓ</span>';

    $estatus_rh = $r['evento_rh'] ?? ($asistencia ? 'PRESENTE' : '<b class="text-danger">FALTA</b>');

    echo "<tr>
<td>{$r['id_empleado']}<br><small>{$r['nombre_completo']}</small></td>
<td>T-{$r['id_turno']}<br><small>{$r['id_departamento']}</small></td>
<td>$estatus_reloj</td>
<td>$estatus_rh</td>
<td><button class='btn btn-sm btn-outline-primary' onclick='abrirModal({$r['id_empleado']})'>Clasificar</button></td>
</tr>";

}
 