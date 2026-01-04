<?php
require_once '../config/db.php';
$data = json_decode(file_get_contents('php://input'), true);

$sql = "SELECT s.*, e.nombre_completo 
        FROM suspensiones s
        JOIN empleados e ON s.id_empleado = e.id_empleado
        WHERE 1=1";
$params = [];

if(!empty($data['mes'])) {
    $sql .= " AND s.fecha LIKE :mes";
    $params[':mes'] = $data['mes'] . '%';
}
if(!empty($data['turno'])) {
    $sql .= " AND s.turno = :turno";
    $params[':turno'] = $data['turno'];
}

$sql .= " ORDER BY s.fecha DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if($rows) {
    foreach($rows as $r) {
        $origen_class = ($r['origen'] == 'ENTREVISTA') ? 'bg-info' : 'bg-warning text-dark';
        echo "<tr>
            <td>
                <div class='fw-bold'>{$r['nombre_completo']}</div>
                <div class='small text-muted'>ID: {$r['id_empleado']}</div>
            </td>
            <td class='text-center'>T: {$r['turno']} / L: {$r['linea']}</td>
            <td class='text-center fw-bold'>{$r['fecha']}</td>
            <td class='text-center'><span class='badge $origen_class'>{$r['origen']}</span></td>
            <td>" . ($r['motivo'] ?? 'Sin motivo') . "</td>
            <td class='text-center'>
                <button class='btn btn-outline-dark btn-sm' onclick=\"editarRegistro({$r['id_suspension']}, '{$r['id_empleado']}', '{$r['motivo']}', '{$r['fecha']}', '{$r['turno']}', '{$r['linea']}')\">
                    <i class='bi bi-pencil'></i>
                </button>
            </td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='6' class='text-center py-4 text-muted'>No hay suspensiones registradas.</td></tr>";
}