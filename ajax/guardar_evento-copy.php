<?php
// ajax/guardar_evento.php
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

$id_asistencia = $data['id_asistencia'] ?? null;
$id_tipo_evento = $data['id_evento'] ?? null;

if (!$id_asistencia || !$id_tipo_evento) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO asistencia_eventos 
    (id_asistencia, id_tipo_evento, capturado_por, fecha_captura)
    VALUES (?, ?, 'RH', NOW())
");
$stmt->execute([$id_asistencia, $id_tipo_evento]);

// Si es falta injustificada → agenda entrevista
if ($id_tipo_evento == 1) {
    $stmt = $pdo->prepare("
        INSERT INTO entrevistas_falta
        (id_evento, fecha_entrevista)
        VALUES (LAST_INSERT_ID(), CURDATE())
    ");
    $stmt->execute();
}

echo json_encode(['success' => true]);
