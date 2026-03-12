<?php

require_once __DIR__ . '/config.php';

$host = envValue('DB_HOST', '127.0.0.1');
$port = envValue('DB_PORT', '3306');
$db   = envValue('DB_DATABASE', 'agenda_db');
$user = envValue('DB_USERNAME', 'agenda');
$pass = envValue('DB_PASSWORD', 'agenda123');

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Erro de conexão com o banco: ' . $e->getMessage());
}