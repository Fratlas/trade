<?php
declare(strict_types=1);
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$base = '/api';
$requestUri = str_starts_with($path, $base . '/') ? substr($path, strlen($base)) : $path;
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
header('Content-Type: application/json; charset=utf-8');
header('Vary: Origin');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigin = getenv('CORS_ALLOWED_ORIGIN') ?: '';
if ($origin && $allowedOrigin && hash_equals($allowedOrigin, $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
}
if ($requestMethod === 'OPTIONS') {
    http_response_code($origin && $origin === $allowedOrigin ? 204 : 403);
    exit;
}
$data = [];
if (in_array($requestMethod, ['POST','PATCH'], true)) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error'=>'Corps JSON invalide']);
        exit;
    }
}
try {
    if ($requestMethod==='POST' && $requestUri==='/register') AuthController::register($data);
    elseif ($requestMethod==='POST' && $requestUri==='/login') AuthController::login($data);
    elseif ($requestMethod==='GET' && $requestUri==='/user') {
        $auth=AuthMiddleware::authenticate(); AuthController::me((int)$auth['user_id']);
    }
    elseif ($requestMethod==='GET' && $requestUri==='/courses') CourseController::getAll();
    elseif ($requestMethod==='GET' && preg_match('#^/courses/(\\d+)/modules$#',$requestUri,$m)) CourseController::getModules((int)$m[1]);
    elseif ($requestMethod==='GET' && $requestUri==='/progress') {
        $auth=AuthMiddleware::authenticate(); ProgressController::getByUser((int)$auth['user_id']);
    }
    elseif ($requestMethod==='POST' && $requestUri==='/progress') {
        $auth=AuthMiddleware::authenticate(); ProgressController::update((int)$auth['user_id'],$data);
    }
    elseif ($requestMethod==='GET' && preg_match('#^/courses/(\\d+)/progress$#',$requestUri,$m)) {
        $auth=AuthMiddleware::authenticate(); ProgressController::getCourseProgress((int)$auth['user_id'],(int)$m[1]);
    }
    elseif ($requestMethod==='GET' && $requestUri==='/copy/traders') {
        AuthMiddleware::authenticate(); CopyTradingController::traders();
    }
    elseif ($requestMethod==='GET' && $requestUri==='/copy/subscriptions') {
        $auth=AuthMiddleware::authenticate(); CopyTradingController::mySubscriptions((int)$auth['user_id']);
    }
    elseif ($requestMethod==='POST' && $requestUri==='/copy/subscriptions') {
        $auth=AuthMiddleware::authenticate(); CopyTradingController::follow((int)$auth['user_id'],$data);
    }
    elseif ($requestMethod==='PATCH' && preg_match('#^/copy/subscriptions/(\\d+)$#',$requestUri,$m)) {
        $auth=AuthMiddleware::authenticate(); CopyTradingController::setStatus((int)$auth['user_id'],(int)$m[1],$data);
    }
    elseif ($requestMethod==='GET' && $requestUri==='/copy/events') {
        $auth=AuthMiddleware::authenticate(); CopyTradingController::events((int)$auth['user_id']);
    }
    else { http_response_code(404); echo json_encode(['error'=>'Endpoint non trouvé']); }
} catch (Throwable $e) {
    error_log('API: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['error'=>'Erreur interne']);
}
