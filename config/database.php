<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u223322817_trad');
define('DB_USER', 'u223322817_trad');
define('DB_PASS', 'Fratlas@1997');

function getDB() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database connection error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed', 'details' => $e->getMessage()]);
        exit;
    }
}

// JWT secret (à stocker en variable d'environnement pour la production)
define('JWT_SECRET', 'votre_cle_secrete_tres_longue_et_aleatoire');
define('JWT_EXPIRY', 86400); // 1 jour
