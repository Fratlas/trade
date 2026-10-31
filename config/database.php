<?php
declare(strict_types=1);

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: '';
$dbUser = getenv('DB_USER') ?: '';
$dbPass = getenv('DB_PASS') ?: '';
$jwtSecret = getenv('JWT_SECRET') ?: '';

if ($dbName === '' || $dbUser === '' || $dbPass === '' || strlen($jwtSecret) < 32) {
    error_log('Database/JWT environment configuration is incomplete.');
    http_response_code(503);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['error' => 'Configuration serveur incomplète.']);
    exit;
}

define('DB_HOST', $dbHost);
define('DB_PORT', $dbPort);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('JWT_SECRET', $jwtSecret);
define('JWT_EXPIRY', 86400);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database connection error: ' . $e->getMessage());
        http_response_code(503);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => 'Service temporairement indisponible.']);
        exit;
    }
}
