<?php
require_once __DIR__ . '/../../config/database.php';

class Chat {
    public static function getOrCreateThread(int $studentId): int {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT id FROM chat_threads WHERE student_id = ?');
        $stmt->execute([$studentId]);
        $row = $stmt->fetch();
        if ($row) {
            return (int)$row['id'];
        }
        $stmt = $pdo->prepare('INSERT INTO chat_threads (student_id) VALUES (?)');
        $stmt->execute([$studentId]);
        return (int)$pdo->lastInsertId();
    }

    public static function getThreadsForAdmin(): array {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT t.id AS thread_id, t.student_id, t.updated_at,
                                    u.name AS student_name, u.email AS student_email,
                                    (SELECT body FROM chat_messages m WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1) AS last_body,
                                    (SELECT image_url FROM chat_messages m WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1) AS last_image,
                                    (SELECT created_at FROM chat_messages m WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1) AS last_at,
                                    (SELECT COUNT(*) FROM chat_messages m WHERE m.thread_id = t.id AND m.sender_id = t.student_id AND m.read_at IS NULL) AS unread
                             FROM chat_threads t
                             JOIN users u ON u.id = t.student_id
                             ORDER BY t.updated_at DESC");
        return $stmt->fetchAll();
    }

    public static function getStudentsWithoutThread(): array {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT u.id, u.name, u.email
                             FROM users u
                             WHERE u.role = 'student' AND u.status = 'active'
                               AND u.id NOT IN (SELECT student_id FROM chat_threads)
                             ORDER BY u.name");
        return $stmt->fetchAll();
    }

    public static function getMessages(int $threadId, int $limit = 200): array {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT m.id, m.thread_id, m.sender_id, m.body, m.image_url, m.read_at, m.created_at,
                                      u.name AS sender_name, u.role AS sender_role
                               FROM chat_messages m
                               JOIN users u ON u.id = m.sender_id
                               WHERE m.thread_id = ?
                               ORDER BY m.id ASC
                               LIMIT " . (int)$limit);
        $stmt->execute([$threadId]);
        return $stmt->fetchAll();
    }

    public static function getThreadByStudent(int $studentId): ?array {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT * FROM chat_threads WHERE student_id = ?');
        $stmt->execute([$studentId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function sendMessage(int $threadId, int $senderId, ?string $body, ?string $imageUrl): array {
        $pdo = getDB();
        $body = $body !== null ? trim($body) : null;
        if (($body === '' || $body === null) && !$imageUrl) {
            throw new InvalidArgumentException('Message vide');
        }
        $stmt = $pdo->prepare('INSERT INTO chat_messages (thread_id, sender_id, body, image_url) VALUES (?, ?, ?, ?)');
        $stmt->execute([$threadId, $senderId, $body ?: null, $imageUrl]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE chat_threads SET updated_at = NOW() WHERE id = ?')->execute([$threadId]);
        $stmt = $pdo->prepare("SELECT m.id, m.thread_id, m.sender_id, m.body, m.image_url, m.read_at, m.created_at,
                                      u.name AS sender_name, u.role AS sender_role
                               FROM chat_messages m JOIN users u ON u.id = m.sender_id WHERE m.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function markRead(int $threadId, int $readerId): void {
        $pdo = getDB();
        $stmt = $pdo->prepare('UPDATE chat_messages SET read_at = NOW() WHERE thread_id = ? AND sender_id != ? AND read_at IS NULL');
        $stmt->execute([$threadId, $readerId]);
    }

    public static function canAccessThread(int $threadId, int $userId, string $role): bool {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT student_id FROM chat_threads WHERE id = ?');
        $stmt->execute([$threadId]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }
        if ($role === 'admin') {
            return true;
        }
        return (int)$row['student_id'] === $userId;
    }
}
