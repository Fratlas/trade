<?php
// Main API Entrypoint for Katsuki Trading API
// Routes everything to src/routes.php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function($e) {
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['error' => 'Erreur interne du serveur']);
    exit;
});

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/utils/jwthandler.php';
require_once __DIR__ . '/../src/middleware/authmiddleware.php';
require_once __DIR__ . '/../src/controller/AuthController.php';
require_once __DIR__ . '/../src/controller/CourseController.php';
require_once __DIR__ . '/../src/controller/ProgressController.php';
require_once __DIR__ . '/../src/controller/AIController.php';
require_once __DIR__ . '/../src/controller/AdminController.php';
require_once __DIR__ . '/../src/controller/ChatController.php';
require_once __DIR__ . '/../src/controller/RealtimeController.php';
require_once __DIR__ . '/../src/controller/CopyTradingController.php';
require_once __DIR__ . '/../src/routes.php';
