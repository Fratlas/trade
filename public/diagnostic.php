<?php
/**
 * Script de diagnostic - Vérifie la connexion à la base de données et l'état de l'API
 * Accédez à: https://projet.stockflowrdc.com/api/diagnostic.php
 */

header('Content-Type: application/json; charset=UTF-8');

$diagnostic = [
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => [],
    'database' => [],
    'api_endpoints' => [],
    'recommendations' => []
];

// 1. Vérifier l'environnement PHP
$diagnostic['environment']['php_version'] = phpversion();
$diagnostic['environment']['pdo_available'] = extension_loaded('pdo');
$diagnostic['environment']['pdo_mysql_available'] = extension_loaded('pdo_mysql');
$diagnostic['environment']['openssl_available'] = extension_loaded('openssl');

// 2. Vérifier la connexion à la base de données
try {
    require_once __DIR__ . '/../config/database.php';
    
    $pdo = getDB();
    $diagnostic['database']['connected'] = true;
    $diagnostic['database']['host'] = DB_HOST;
    $diagnostic['database']['name'] = DB_NAME;
    $diagnostic['database']['user'] = DB_USER;
    
    // Vérifier les tables
    $tables = ['users', 'courses', 'subjects', 'exams', 'modules', 'contents', 'user_progress'];
    $diagnostic['database']['tables'] = [];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table");
            $stmt->execute();
            $result = $stmt->fetch();
            $diagnostic['database']['tables'][$table] = [
                'exists' => true,
                'rows' => $result['count']
            ];
        } catch (PDOException $e) {
            $diagnostic['database']['tables'][$table] = [
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Vérifier la table users spécifiquement
    try {
        $stmt = $pdo->prepare("DESCRIBE users");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        $diagnostic['database']['users_columns'] = array_map(function($col) {
            return $col['Field'];
        }, $columns);
    } catch (PDOException $e) {
        $diagnostic['database']['users_columns'] = null;
        $diagnostic['database']['users_error'] = $e->getMessage();
    }
    
} catch (Exception $e) {
    $diagnostic['database']['connected'] = false;
    $diagnostic['database']['error'] = $e->getMessage();
}

// 3. Tester les endpoints
$diagnostic['api_endpoints'] = [
    'register' => [
        'method' => 'POST',
        'path' => '/api/register',
        'expected_parameters' => ['email', 'password', 'name'],
        'status' => 'À tester manuellement'
    ],
    'login' => [
        'method' => 'POST',
        'path' => '/api/login',
        'expected_parameters' => ['email', 'password'],
        'status' => 'À tester manuellement'
    ],
    'user' => [
        'method' => 'GET',
        'path' => '/api/user',
        'requires_auth' => true,
        'status' => 'À tester manuellement'
    ]
];

// 4. Recommandations
$diagnostic['recommendations'] = [];

if (!isset($diagnostic['database']['connected']) || !$diagnostic['database']['connected']) {
    $diagnostic['recommendations'][] = '⚠️ La base de données n\'est pas accessible. Vérifiez les identifiants.';
} else {
    if (!$diagnostic['database']['tables']['users']['exists']) {
        $diagnostic['recommendations'][] = '⚠️ La table "users" n\'existe pas. Exécutez: sql/init_u223322817_trad.sql';
    } else {
        $diagnostic['recommendations'][] = '✓ La table "users" existe avec ' . $diagnostic['database']['tables']['users']['rows'] . ' lignes.';
    }
}

if (!$diagnostic['environment']['pdo_available']) {
    $diagnostic['recommendations'][] = '⚠️ PDO n\'est pas disponible. Installez l\'extension PDO.';
} else {
    $diagnostic['recommendations'][] = '✓ PDO est disponible.';
}

if (!$diagnostic['environment']['pdo_mysql_available']) {
    $diagnostic['recommendations'][] = '⚠️ PDO MySQL n\'est pas disponible. Installez l\'extension pdo_mysql.';
} else {
    $diagnostic['recommendations'][] = '✓ PDO MySQL est disponible.';
}

echo json_encode($diagnostic, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
