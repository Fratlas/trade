<?php
require_once __DIR__ . '/../utils/JwtHandler.php';

class AuthMiddleware {
    public static function authenticate() {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
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