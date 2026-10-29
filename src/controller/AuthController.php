<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/jwthandler.php';

class AuthController {
    public static function register($data) {
        $email = filter_var(strtolower(trim((string)($data['email'] ?? ''))), FILTER_VALIDATE_EMAIL);
        $password = $data['password'] ?? '';
        $name = trim($data['name'] ?? '');

        if (!$email || strlen($password) < 8 || strlen($name) < 2) {
            http_response_code(400);
            echo json_encode(['error' => 'Champs invalides (email, mot de passe 8+ caractères, nom 2+ caractères)']);
            return;
        }

        if (User::findByEmail($email)) {
            http_response_code(409);
            echo json_encode(['error' => 'Email déjà utilisé']);
            return;
        }

        try { $userId = User::create($email, $password, $name); } catch (PDOException $e) { if ($e->getCode() === '23000') { http_response_code(409); echo json_encode(['error'=>'Email déjà utilisé']); return; } throw $e; }
        $token = JwtHandler::encode(['user_id' => $userId, 'email' => $email, 'role' => 'student']);
        echo json_encode(['token' => $token, 'user' => ['id' => $userId, 'email' => $email, 'name' => $name, 'role' => 'student']]);
    }

    public static function login($data) {
        $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $data['password'] ?? '';
        if (!$email || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Email et mot de passe requis']);
            return;
        }

        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Identifiants incorrects']);
            return;
        }

        User::updateLastLogin($user['id']);
        $token = JwtHandler::encode(['user_id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']]);
        echo json_encode([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role'],
                'subscription_type' => $user['subscription_type']
            ]
        ]);
    }

    public static function me($userId) {
        $user = User::findById($userId);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'Utilisateur non trouvé']);
            return;
        }
        echo json_encode($user);
    }
}