<?php
$requestUri = str_replace('/api', '', $_SERVER['REQUEST_URI']);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($requestMethod == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Router simple
if ($requestMethod == 'POST' && $requestUri == '/register') {
    $data = json_decode(file_get_contents('php://input'), true);
    AuthController::register($data);
}
elseif ($requestMethod == 'POST' && $requestUri == '/login') {
    $data = json_decode(file_get_contents('php://input'), true);
    AuthController::login($data);
}
elseif ($requestMethod == 'GET' && $requestUri == '/user') {
    $payload = AuthMiddleware::authenticate();
    AuthController::me($payload['user_id']);
}
elseif ($requestMethod == 'GET' && $requestUri == '/courses') {
    CourseController::getAll();
}
elseif ($requestMethod == 'GET' && preg_match('/\/courses\/(\d+)\/modules/', $requestUri, $matches)) {
    CourseController::getModules($matches[1]);
}
elseif ($requestMethod == 'GET' && $requestUri == '/progress') {
    $payload = AuthMiddleware::authenticate();
    ProgressController::getByUser($payload['user_id']);
}
elseif ($requestMethod == 'POST' && $requestUri == '/progress') {
    $payload = AuthMiddleware::authenticate();
    $data = json_decode(file_get_contents('php://input'), true);
    ProgressController::update($payload['user_id'], $data);
}
elseif ($requestMethod == 'GET' && preg_match('/\/courses\/(\d+)\/progress/', $requestUri, $matches)) {
    $payload = AuthMiddleware::authenticate();
    ProgressController::getCourseProgress($payload['user_id'], $matches[1]);
}
else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint non trouvé']);
}