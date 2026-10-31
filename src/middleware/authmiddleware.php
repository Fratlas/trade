<?php
require_once __DIR__ . '/../utils/jwthandler.php';

class AuthMiddleware {
    public static function authenticate($requireAdmin = false) {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        if (!$authHeader && isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        }

        $token = '';
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } elseif (!empty($_COOKIE['katsuki_token'])) {
            $token = $_COOKIE['katsuki_token'];
        }

        if ($token) {
            $payload = JwtHandler::decode($token);
            if ($payload) {
                if ($requireAdmin && (!isset($payload['role']) || $payload['role'] !== 'admin')) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Accès administrateur requis']);
                    exit;
                }
                return $payload; // contient user_id, email, role
            }
        }
        http_response_code(401);
        echo json_encode(['error' => 'Non authentifié']);
        exit;
    }
}
