<?php
require_once '../config/db.php';
$data = json_decode(file_get_contents('php://input'), true);
$stmt = $pdo->prepare("DELETE FROM suspensiones WHERE id_suspension = ?");
$res = $stmt->execute([$data['id']]);
echo json_encode(['success' => $res]);