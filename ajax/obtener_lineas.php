<?php
require_once '../config/db.php';
// Recibimos el departamento (puede ser string o null)
$dep = $_GET['dep'] ?? '';
$sql = "SELECT DISTINCT id_linea FROM empleados WHERE id_linea IS NOT NULL AND id_linea != ''";
$params = [];
if ($dep !== '') {
   $sql .= " AND id_departamento = ?";
   $params[] = $dep;
}
$sql .= " ORDER BY id_linea ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lineas = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($lineas);