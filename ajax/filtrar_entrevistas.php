<?php
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

$sql = "SELECT ent.id_entrevista, ent.id_empleado, ent.fecha_falta, ent.motivo, ent.estatus_entrevista,
               e.nombre_completo, e.id_turno, e.id_linea, 
               s.fecha AS fecha_suspension
        FROM entrevistas_falta ent
        INNER JOIN empleados e ON ent.id_empleado = e.id_empleado
        LEFT JOIN suspensiones s ON s.referencia_id = ent.id_entrevista AND s.origen = 'ENTREVISTA'
        WHERE 1=1";

$params = [];

if (!empty($data['fecha']) && empty($data['suspension'])) {
    $sql .= " AND ent.fecha_falta = :fecha";
    $params[':fecha'] = $data['fecha'];
}

if (!empty($data['estatus'])) {
    $sql .= " AND ent.estatus_entrevista = :estatus";
    $params[':estatus'] = $data['estatus'];
}

if (!empty($data['suspension'])) {
    $sql .= " AND s.fecha = :suspension";
    $params[':suspension'] = $data['suspension'];
}

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
        $motivo_js = str_replace(["\r", "\n"], ' ', addslashes($row['motivo'] ?? ''));
        $suspension = $row['fecha_suspension'] ?? 'N/A';
        $linea_val = $row['id_linea'] ?? 'N/A';

        echo "<tr>
<td class='text-center text-muted fw-bold'>".($i+1)."</td>
<td>
<div class='fw-bold'>{$row['nombre_completo']}</div>
<div class='small text-muted'>ID: {$row['id_empleado']} | Turno: {$row['id_turno']} | Línea: $linea_val</div>
</td>
<td class='text-center'>{$row['fecha_falta']}</td>
<td class='text-center'><span class='badge $badge'>{$row['estatus_entrevista']}</span></td>
<td class='text-center fw-bold text-primary'>$suspension</td>
<td class='text-center'>
<button class='btn btn-dark btn-sm' onclick=\"abrirModal({$row['id_entrevista']}, '$motivo_js', '{$row['id_turno']}', '$linea_val')\">
<i class='bi bi-pencil-square'></i> Editar
</button>
</td>
</tr>";
    }
} else {
    echo "<tr class='no-data'><td colspan='6' class='text-center py-5 text-muted'>No se encontraron entrevistas con estos filtros.</td></tr>";
}