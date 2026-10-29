<?php
require_once __DIR__ . '/../utils/jwthandler.php';

class AuthMiddleware {
    public static function authenticate() {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (!$authHeader && function_exists('getallheaders')) { foreach (getallheaders() as $key=>$value) { if (strtolower($key)==='authorization') $authHeader=$value; } }
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $payload = JwtHandler::decode($token);
            if ($payload) {
                return $payload; // contient user_id, email, role
            }
        }
        http_response_code(401);
        echo json_encode(['error' => 'Non authentifié']);
        exit;
    }
}