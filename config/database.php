<?php
declare(strict_types=1);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '3306';
    $db = getenv('DB_NAME') ?: 'katsuki_trading';
    $user = getenv('DB_USER') ?: '';
    $pass = getenv('DB_PASS') ?: '';
    try {
        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database connection error: ' . $e->getMessage());
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Service temporairement indisponible']);
        exit;
    }
}
$jwtSecret = getenv('JWT_SECRET');
if (!$jwtSecret || strlen($jwtSecret) < 32) {
    error_log('Missing JWT_SECRET environment variable (min 32 chars)');
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error'=>'Configuration serveur incomplète']);
    exit;
}
define('JWT_SECRET', $jwtSecret);
define('JWT_EXPIRY', 86400);
