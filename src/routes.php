<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '\\');
if ($scriptDir !== '' && $scriptDir !== '/' && $scriptDir !== '\\' && strpos($path, $scriptDir) === 0) {
    $path = substr($path, strlen($scriptDir));
}
$requestUri = rtrim(str_replace('/api', '', $path), '/');
if ($requestUri === '') {
    $requestUri = '/';
} elseif (strpos($requestUri, '/') !== 0) {
    $requestUri = '/' . $requestUri;
}

$requestMethod = $_SERVER['REQUEST_METHOD'];

// Debug and error handling
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

set_exception_handler(function($e) {
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error', 'details' => $e->getMessage()]);
    exit;
});

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($requestMethod == 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (in_array($requestMethod, ['POST', 'PUT'])) {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $isMultipart = strpos($contentType, 'multipart/form-data') !== false;
    $isMessagesSend = ($requestUri === '/messages/send' || $requestUri === '/messages');
    if (!$isMultipart && !$isMessagesSend) {
        $body = file_get_contents('php://input');
        if ($body === false || trim($body) === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Corps JSON vide ou absent']);
            exit;
        }
    }
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
elseif ($requestMethod == 'PUT' && $requestUri == '/user') {
    $payload = AuthMiddleware::authenticate();
    $data = json_decode(file_get_contents('php://input'), true);
    AuthController::updateProfile($payload['user_id'], $data);
}
elseif ($requestMethod == 'GET' && $requestUri == '/subjects') {
    CourseController::getSubjects();
}
elseif ($requestMethod == 'GET' && preg_match('/\/subjects\/(\d+)\/courses/', $requestUri, $matches)) {
    CourseController::getCoursesBySubject($matches[1]);
}
elseif ($requestMethod == 'GET' && $requestUri == '/courses') {
    CourseController::getAll();
}
elseif ($requestMethod == 'GET' && preg_match('/\/content\/type\/(\w+)$/', $requestUri, $matches)) {
    AdminController::getContentByType($matches[1]);
}
elseif ($requestMethod == 'GET' && $requestUri == '/content') {
    AdminController::getVisibleContent();
}
elseif ($requestMethod == 'GET' && preg_match('/\/content\/(\d+)$/', $requestUri, $matches)) {
    AdminController::getContentById($matches[1]);
}
elseif ($requestMethod == 'GET' && preg_match('/\/courses\/(\d+)\/modules/', $requestUri, $matches)) {
    CourseController::getModules($matches[1]);
}
elseif ($requestMethod == 'GET' && preg_match('/\/courses\/(\d+)\/exams/', $requestUri, $matches)) {
    CourseController::getCourseExams($matches[1]);
}
elseif ($requestMethod == 'GET' && preg_match('/\/exams\/(\d+)$/', $requestUri, $matches)) {
    CourseController::getExam($matches[1]);
}
elseif ($requestMethod == 'GET' && $requestUri == '/admin/students') {
    $payload = AuthMiddleware::authenticate(true);
    AdminController::getStudents();
}
elseif ($requestMethod == 'GET' && preg_match('/\/admin\/students\/(\d+)\/progress/', $requestUri, $matches)) {
    $payload = AuthMiddleware::authenticate(true);
    AdminController::getStudentProgress($matches[1]);
}
elseif ($requestMethod == 'GET' && preg_match('/\/admin\/students\/(\d+)\/exams/', $requestUri, $matches)) {
    $payload = AuthMiddleware::authenticate(true);
    AdminController::getStudentExamResults($matches[1]);
}
elseif ($requestMethod == 'GET' && $requestUri == '/admin/progress') {
    $payload = AuthMiddleware::authenticate(true);
    AdminController::getAllProgress();
}
elseif ($requestMethod == 'GET' && $requestUri == '/admin/exam-results') {
    $payload = AuthMiddleware::authenticate(true);
    AdminController::getAllExamResults();
}
elseif ($requestMethod == 'GET' && $requestUri == '/admin/content') {
    $payload = AuthMiddleware::authenticate(true);
    AdminController::getAllContent();
}
elseif ($requestMethod == 'POST' && $requestUri == '/admin/content') {
    $payload = AuthMiddleware::authenticate(true);
    $data = json_decode(file_get_contents('php://input'), true);
    AdminController::addContent($data, $payload['user_id']);
}
elseif ($requestMethod == 'PUT' && preg_match('/\/admin\/content\/(\d+)$/', $requestUri, $matches)) {
    $payload = AuthMiddleware::authenticate(true);
    $data = json_decode(file_get_contents('php://input'), true);
    AdminController::updateContent($matches[1], $data);
}
elseif ($requestMethod == 'DELETE' && preg_match('/\/admin\/content\/(\d+)$/', $requestUri, $matches)) {
    $payload = AuthMiddleware::authenticate(true);
    AdminController::deleteContent($matches[1]);
}
elseif ($requestMethod == 'POST' && $requestUri == '/ai/realtime-session') {
    $payload = AuthMiddleware::authenticate();
    $data    = json_decode(file_get_contents('php://input'), true) ?? [];
    $role    = isset($data['role']) && $data['role'] === 'admin' ? 'admin' : 'student';
    RealtimeController::createSession((int)$payload['user_id'], $role);
}
elseif ($requestMethod == 'POST' && $requestUri == '/ai/lesson') {
    $payload = AuthMiddleware::authenticate();
    $data = json_decode(file_get_contents('php://input'), true);
    AIController::lesson($payload['user_id'], $data);
}
elseif ($requestMethod == 'POST' && $requestUri == '/ai/questions') {
    $payload = AuthMiddleware::authenticate();
    $data = json_decode(file_get_contents('php://input'), true);
    AIController::questions($payload['user_id'], $data);
}
elseif ($requestMethod == 'POST' && preg_match('/\/exams\/(\d+)\/submit/', $requestUri, $matches)) {
    $payload = AuthMiddleware::authenticate();
    $data = json_decode(file_get_contents('php://input'), true);
    CourseController::submitExam($payload['user_id'], $matches[1], $data);
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
elseif ($requestMethod == 'GET' && $requestUri == '/messages') {
    $payload = AuthMiddleware::authenticate();
    ChatController::inbox($payload);
}
elseif ($requestMethod == 'GET' && preg_match('/\/messages\/(\d+)$/', $requestUri, $matches)) {
    $payload = AuthMiddleware::authenticate();
    ChatController::conversation($payload, (int)$matches[1]);
}
elseif ($requestMethod == 'POST' && ($requestUri == '/messages' || $requestUri == '/messages/send')) {
    $payload = AuthMiddleware::authenticate();
    ChatController::send($payload);
}
else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint non trouvé']);
}