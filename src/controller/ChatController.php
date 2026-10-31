<?php
require_once __DIR__ . '/../models/Chat.php';
require_once __DIR__ . '/../models/User.php';

class ChatController {
    private static function uploadImage(array $file): string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Erreur lors de l envoi de l image.');
        }
        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new RuntimeException('Image trop volumineuse (max 5 Mo).');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Format image non autorise (JPG, PNG, GIF, WEBP).');
        }

        $dir = __DIR__ . '/../../uploads/chat/' . date('Y/m');
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException('Impossible de creer le dossier upload.');
        }

        $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Echec de l enregistrement de l image.');
        }

        return '/uploads/chat/' . date('Y/m') . '/' . $name;
    }

    public static function inbox(array $payload): void {
        $userId = (int)$payload['user_id'];
        $role = $payload['role'] ?? 'student';

        if ($role === 'admin') {
            echo json_encode([
                'threads' => Chat::getThreadsForAdmin(),
                'students' => Chat::getStudentsWithoutThread()
            ]);
            return;
        }

        $threadId = Chat::getOrCreateThread($userId);
        Chat::markRead($threadId, $userId);
        echo json_encode([
            'thread_id' => $threadId,
            'messages' => Chat::getMessages($threadId),
            'counterpart' => ['name' => 'Equipe Katsuki', 'role' => 'admin']
        ]);
    }

    public static function conversation(array $payload, int $studentId): void {
        if (($payload['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Acces reserve aux administrateurs']);
            return;
        }

        $student = User::findById($studentId);
        if (!$student || $student['role'] !== 'student') {
            http_response_code(404);
            echo json_encode(['error' => 'Etudiant introuvable']);
            return;
        }

        $threadId = Chat::getOrCreateThread($studentId);
        Chat::markRead($threadId, (int)$payload['user_id']);

        echo json_encode([
            'thread_id' => $threadId,
            'student' => ['id' => (int)$student['id'], 'name' => $student['name'], 'email' => $student['email']],
            'messages' => Chat::getMessages($threadId)
        ]);
    }

    public static function send(array $payload): void {
        $userId = (int)$payload['user_id'];
        $role = $payload['role'] ?? 'student';

        $isMultipart = isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;
        $json = [];
        if (!$isMultipart) {
            $json = json_decode(file_get_contents('php://input'), true) ?: [];
        }
        $body = trim($isMultipart ? ($_POST['body'] ?? '') : ($json['body'] ?? ''));
        $studentId = (int)($isMultipart ? ($_POST['student_id'] ?? 0) : ($json['student_id'] ?? 0));

        if ($role === 'student') {
            $studentId = $userId;
        } elseif (!$studentId) {
            http_response_code(400);
            echo json_encode(['error' => 'student_id requis']);
            return;
        }

        $imageUrl = null;
        if ($isMultipart && !empty($_FILES['image']['name'])) {
            try {
                $imageUrl = self::uploadImage($_FILES['image']);
            } catch (Throwable $e) {
                http_response_code(400);
                echo json_encode(['error' => $e->getMessage()]);
                return;
            }
        }

        if ($body === '' && !$imageUrl) {
            http_response_code(400);
            echo json_encode(['error' => 'Message ou image requis']);
            return;
        }

        try {
            $threadId = Chat::getOrCreateThread($studentId);
            if (!Chat::canAccessThread($threadId, $userId, $role)) {
                http_response_code(403);
                echo json_encode(['error' => 'Acces refuse']);
                return;
            }
            $message = Chat::sendMessage($threadId, $userId, $body, $imageUrl);
            echo json_encode(['message' => $message]);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
