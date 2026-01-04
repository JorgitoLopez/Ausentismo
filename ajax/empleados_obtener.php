<?php
require_once '../config/db.php';
$id = $_GET['id'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM empleados WHERE id_empleado = ?");
$stmt->execute([$id]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));