<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/jwthandler.php';

class AuthController {
    public static function register($data) {
        $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $data['password'] ?? '';
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $bio = trim($data['bio'] ?? '');
        $avatarUrl = trim($data['avatar_url'] ?? '');

        if (!$email || strlen($password) < 6 || strlen($name) < 2) {
            http_response_code(400);
            echo json_encode(['error' => 'Champs invalides (email, mot de passe 6+ caractères, nom 2+ caractères)']);
            return;
        }

        if (User::findByEmail($email)) {
            http_response_code(409);
            echo json_encode(['error' => 'Email déjà utilisé']);
            return;
        }

        try {
            $userId = User::create($email, $password, $name, $phone ?: null, $bio ?: null, $avatarUrl ?: null);
        } catch (PDOException $e) {
            error_log('Register PDO error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erreur interne lors de l\'inscription']);
            return;
        }

        $token = JwtHandler::encode(['user_id' => $userId, 'email' => $email, 'role' => 'student']);
        self::setAuthCookie($token);
        $user = User::findById($userId);
        echo json_encode(['token' => $token, 'user' => $user]);
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

        if ($user['status'] !== 'active') {
            http_response_code(403);
            echo json_encode(['error' => 'Compte désactivé']);
            return;
        }

        User::updateLastLogin($user['id']);
        $token = JwtHandler::encode(['user_id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']]);
        self::setAuthCookie($token);
        $userData = User::findById($user['id']);
        echo json_encode([
            'token' => $token,
            'user' => $userData
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

    public static function updateProfile($userId, $data) {
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $bio = trim($data['bio'] ?? '');
        $avatarUrl = trim($data['avatar_url'] ?? '');

        if (!$name || strlen($name) < 2) {
            http_response_code(400);
            echo json_encode(['error' => 'Nom invalide']);
            return;
        }

        User::updateProfile($userId, $name, $phone ?: null, $bio ?: null, $avatarUrl ?: null);
        $user = User::findById($userId);
        echo json_encode($user);
    }

    private static function setAuthCookie($token) {
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie('katsuki_token', $token, [
            'expires' => time() + JWT_EXPIRY,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}
