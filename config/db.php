<?php
// config/db.php

$host = 'localhost';
$db   = 'RH_System';
$user = 'root';      // cambia si aplica
$pass = '';          // cambia si aplica
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
    http_response_code(500);
    die('Error de conexión a base de datos');
}
