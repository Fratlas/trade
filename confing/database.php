<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'katsuki_trading');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB() {
    try {
        $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
}

// JWT secret (à stocker en variable d'environnement pour la production)
define('JWT_SECRET', 'votre_cle_secrete_tres_longue_et_aleatoire');
define('JWT_EXPIRY', 86400); // 1 jour