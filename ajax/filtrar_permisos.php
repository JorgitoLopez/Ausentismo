<?php

require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

$fecha = $data['fecha'] ?? date('Y-m-d');

// Consulta base: Solo los que RH clasificó como llegada tarde

$sql = "SELECT ad.*, e.nombre_completo, c.hora_reloj

        FROM asistencia_diaria ad

        INNER JOIN empleados e ON ad.id_empleado = e.id_empleado

        LEFT JOIN (

            SELECT id_empleado, MIN(hora) as hora_reloj 

            FROM checadas 

            WHERE fecha = :f_ch 

            GROUP BY id_empleado

        ) c ON ad.id_empleado = c.id_empleado

        WHERE ad.fecha = :fecha 

        AND ad.estatus_real = 'permiso de llegada tarde'";

$params = [':fecha' => $fecha, ':f_ch' => $fecha];

// Filtro de Turno

if (!empty($data['turno'])) {

    $sql .= " AND ad.id_turno = :t"; // Usamos ad.id_turno de la tabla asistencia

    $params[':t'] = $data['turno'];

}

// Filtro de Departamento

if (!empty($data['departamento'])) {

    $sql .= " AND ad.id_departamento = :d"; // Usamos ad.id_departamento de la tabla asistencia

    $params[':d'] = $data['departamento'];

}

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) > 0) {

    foreach ($rows as $i => $row) {

        echo "<tr>
<td class='text-center text-muted fw-bold'>".($i+1)."</td>
<td>
<div class='fw-bold'>{$row['nombre_completo']}</div>
<div class='small text-muted'>ID: {$row['id_empleado']} | Turno: {$row['id_turno']}</div>
</td>
<td class='text-center'>
<span class='badge bg-success'>{$row['estatus']}</span>
</td>
<td class='text-center text-primary fw-bold small'>
<i class='bi bi-check2-circle'></i> LLEGADA TARDE
</td>
<td class='text-center'>
<div class='badge bg-light text-dark border p-2'>
<i class='bi bi-clock'></i> " . ($row['hora_reloj'] ?? '--:--') . "
</div>
</td>
</tr>";

    }

} else {

    // Si no hay datos con esos filtros, mostrar mensaje vacío

    echo "<tr class='no-data'><td colspan='5' class='text-center p-5 text-muted'>
<i class='bi bi-info-circle display-6 d-block mb-3'></i>

        No se encontraron permisos con los filtros seleccionados.
</td></tr>";

}
 