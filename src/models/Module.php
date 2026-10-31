<?php
require_once __DIR__ . '/../../config/database.php';

class Module {
    public static function getByCourseId($courseId) {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT * FROM modules WHERE course_id = ? ORDER BY order_index');
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT * FROM modules WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function update(int $id, array $data): bool {
        $pdo = getDB();
        $stmt = $pdo->prepare('UPDATE modules SET title = ?, order_index = ?, video_url = ?, audio_url = ?, pdf_url = ?, content = ? WHERE id = ?');
        return $stmt->execute([
            $data['title'],
            (int)$data['order_index'],
            $data['video_url'] ?: null,
            $data['audio_url'] ?: null,
            $data['pdf_url'] ?: null,
            $data['content'] ?: null,
            $id
        ]);
    }

    public static function create(int $courseId, string $title, int $orderIndex): int {
        $pdo = getDB();
        $stmt = $pdo->prepare('INSERT INTO modules (course_id, title, order_index, content) VALUES (?, ?, ?, ?)');
        $stmt->execute([$courseId, $title, $orderIndex, 'Contenu a completer.']);
        return (int)$pdo->lastInsertId();
    }
}
