<?php

require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

$sql = "SELECT ent.*, e.nombre_completo, e.id_turno 

        FROM entrevistas_falta ent

        INNER JOIN empleados e ON ent.id_empleado = e.id_empleado

        WHERE 1=1";

$params = [];

// Filtro Fecha de Falta (Solo si no se filtra por suspensión, para permitir ver históricos de suspensión)

if (!empty($data['fecha']) && empty($data['suspension'])) {

    $sql .= " AND ent.fecha_falta = :fecha";

    $params[':fecha'] = $data['fecha'];

}

// Filtro Estatus

if (!empty($data['estatus'])) {

    $sql .= " AND ent.estatus_entrevista = :estatus";

    $params[':estatus'] = $data['estatus'];

}

// Filtro Fecha Suspensión

if (!empty($data['suspension'])) {

    $sql .= " AND ent.fecha_suspension = :suspension";

    $params[':suspension'] = $data['suspension'];

}

// Filtro Turno

if (!empty($data['turno'])) {

    $sql .= " AND e.id_turno = :turno";

    $params[':turno'] = $data['turno'];

}

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) > 0) {

    foreach ($rows as $i => $row) {

        $badge = ($row['estatus_entrevista'] == 'PENDIENTE') ? 'bg-danger' : 'bg-success';

        // Limpiar texto para JS

        $motivo_js = str_replace(["\r", "\n"], ' ', addslashes($row['motivo'] ?? ''));

        $suspension = $row['fecha_suspension'] ?? 'N/A';

        echo "<tr>
<td class='text-center text-muted fw-bold'>".($i+1)."</td>
<td>
<div class='fw-bold'>{$row['nombre_completo']}</div>
<div class='small text-muted'>ID: {$row['id_empleado']} | Turno: {$row['id_turno']}</div>
</td>
<td class='text-center'>{$row['fecha_falta']}</td>
<td class='text-center'><span class='badge $badge'>{$row['estatus_entrevista']}</span></td>
<td class='text-center fw-bold text-primary'>$suspension</td>
<td class='text-center'>
<button class='btn btn-dark btn-sm' onclick=\"abrirModal({$row['id_entrevista']}, '$motivo_js', '$suspension', '{$row['estatus_entrevista']}')\">
<i class='bi bi-pencil-square'></i> Editar
</button>
</td>
</tr>";

    }

} else {

    echo "<tr class='no-data'><td colspan='6' class='text-center py-5 text-muted'>No se encontraron entrevistas con estos filtros.</td></tr>";

}
 