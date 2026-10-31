<?php
// Single-file API for Hostinger deployment
// Copy this as index.php to /public_html/

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Database configuration
define('DB_HOST', 'projet.stockflowrdc.com');
define('DB_NAME', 'u223322817_trad');
define('DB_USER', 'u223322817_trad');
define('DB_PASS', 'Fratlas@1997');
define('JWT_SECRET', 'votre_cle_secrete_tres_longue_et_aleatoire');
define('JWT_EXPIRY', 86400);

// Setup error handler
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function($e) {
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['error' => 'Internal Server Error', 'details' => $e->getMessage()]);
    exit;
});

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection
function getDB() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database connection error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed', 'details' => $e->getMessage()]);
        exit;
    }
}

// JWT Handler
class JwtHandler {
    public static function encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['exp'] = time() + JWT_EXPIRY;
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function decode($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;
        $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlSignature));
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
        if (!hash_equals($signature, $expectedSignature)) return null;
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlPayload)), true);
        if (isset($payload['exp']) && $payload['exp'] < time()) return null;
        return $payload;
    }
}

// User model
class User {
    public static function findByEmail($email) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public static function findById($id) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, email, name, phone, bio, avatar_url, role, subscription_type, subscription_expiry, status, created_at, last_login FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create($email, $password, $name, $phone = null, $bio = null, $avatarUrl = null) {
        $pdo = getDB();
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password, name, phone, bio, avatar_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$email, $hashed, $name, $phone, $bio, $avatarUrl]);
        return $pdo->lastInsertId();
    }

    public static function updateLastLogin($id) {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }
}

// Auth Controller
class AuthController {
    public static function register($data) {
        $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $data['password'] ?? '';
        $name = trim($data['name'] ?? '');

        if (!$email || strlen($password) < 6 || strlen($name) < 2) {
            http_response_code(400);
            echo json_encode(['error' => 'Champs invalides']);
            return;
        }

        if (User::findByEmail($email)) {
            http_response_code(409);
            echo json_encode(['error' => 'Email déjà utilisé']);
            return;
        }

        try {
            $userId = User::create($email, $password, $name);
            $token = JwtHandler::encode(['user_id' => $userId, 'email' => $email, 'role' => 'student']);
            $user = User::findById($userId);
            if (!$user) {
                throw new Exception('User not found after creation');
            }
            echo json_encode(['token' => $token, 'user' => $user]);
        } catch (PDOException $e) {
            error_log('Register error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de l\'inscription', 'details' => $e->getMessage()]);
        } catch (Exception $e) {
            error_log('Register error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
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
        $userData = User::findById($user['id']);
        echo json_encode(['token' => $token, 'user' => $userData]);
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

// Course Controller
class CourseController {
    public static function getAll() {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT * FROM courses");
        $courses = $stmt->fetchAll();
        echo json_encode($courses);
    }

    public static function getModules($courseId) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM modules WHERE course_id = ? ORDER BY order_index");
        $stmt->execute([$courseId]);
        $modules = $stmt->fetchAll();
        echo json_encode($modules);
    }
}

// Request routing
$requestUri = str_replace('/api', '', $_SERVER['REQUEST_URI']);
$requestMethod = $_SERVER['REQUEST_METHOD'];

if (in_array($requestMethod, ['POST', 'PUT'])) {
    $body = file_get_contents('php://input');
    if ($body === false || trim($body) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Corps JSON vide ou absent']);
        exit;
    }
}

if ($requestMethod == 'POST' && $requestUri == '/register') {
    $data = json_decode(file_get_contents('php://input'), true);
    AuthController::register($data);
}
elseif ($requestMethod == 'POST' && $requestUri == '/login') {
    $data = json_decode(file_get_contents('php://input'), true);
    AuthController::login($data);
}
elseif ($requestMethod == 'GET' && $requestUri == '/courses') {
    CourseController::getAll();
}
else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint non trouvé']);
}
