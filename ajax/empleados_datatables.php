<?php
require_once '../config/db.php';

$draw   = intval($_POST['draw'] ?? 0);
$start  = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$search = $_POST['search']['value'] ?? '';

// 1. Conteo total
$total = $pdo->query("SELECT COUNT(*) FROM empleados")->fetchColumn();

// 2. Filtro
$where = "";
$params = [];
if ($search != "") {
    $where = " WHERE id_empleado LIKE ? OR nombre_completo LIKE ? OR id_departamento LIKE ? ";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$stmtF = $pdo->prepare("SELECT COUNT(*) FROM empleados $where");
$stmtF->execute($params);
$filtered = $stmtF->fetchColumn();

// 3. Datos
$sql = "SELECT * FROM empleados $where LIMIT $start, $length";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = $row['id_empleado'];
    $row['acciones'] = '
        <div class="text-center">
            <button class="btn btn-sm btn-outline-primary me-1" onclick="editar(\''.$id.'\')"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-sm btn-outline-danger" onclick="eliminar(\''.$id.'\')"><i class="bi bi-trash"></i></button>
        </div>';
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode([
    "draw" => $draw,
    "recordsTotal" => $total,
    "recordsFiltered" => $filtered,
    "data" => $data
]);