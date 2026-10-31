<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Progress.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../models/Content.php';
require_once __DIR__ . '/../../config/database.php';

class AdminController {
    public static function getStudents() {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT u.id, u.email, u.name, u.role, u.subscription_type, u.subscription_expiry, u.created_at, u.last_login,
                                    COALESCE((SELECT COUNT(*) FROM user_progress up WHERE up.user_id = u.id AND up.completed = 1), 0) AS completed_modules,
                                    COALESCE((SELECT COUNT(*) FROM user_progress up WHERE up.user_id = u.id), 0) AS total_progress_entries,
                                    COALESCE((SELECT COUNT(*) FROM exam_results er WHERE er.user_id = u.id), 0) AS exam_attempts,
                                    COALESCE((SELECT SUM(er.passed) FROM exam_results er WHERE er.user_id = u.id), 0) AS exams_passed
                             FROM users u
                             WHERE u.role = 'student'
                             ORDER BY u.name");
        $students = $stmt->fetchAll();
        echo json_encode($students);
    }

    public static function getStudentProgress($studentId) {
        $studentId = (int)$studentId;
        if ($studentId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID étudiant invalide']);
            return;
        }

        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT u.id AS user_id, u.email, u.name, c.id AS course_id, c.title AS course_title,
                                      m.id AS module_id, m.title AS module_title, m.order_index, m.course_id,
                                      COALESCE(up.completed, 0) AS completed, up.score, up.completed_at
                               FROM users u
                               JOIN modules m ON 1=1
                               JOIN courses c ON c.id = m.course_id
                               LEFT JOIN user_progress up ON up.module_id = m.id AND up.user_id = ?
                               WHERE u.id = ? AND u.role = 'student'
                               ORDER BY c.id, m.order_index");
        $stmt->execute([$studentId, $studentId]);
        $progress = $stmt->fetchAll();
        echo json_encode($progress);
    }

    public static function getStudentExamResults($studentId) {
        $studentId = (int)$studentId;
        if ($studentId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID étudiant invalide']);
            return;
        }

        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT er.id, er.exam_id, e.title AS exam_title, c.title AS course_title, er.score, er.max_score, er.percentage, er.passed, er.taken_at
                               FROM exam_results er
                               JOIN exams e ON e.id = er.exam_id
                               JOIN courses c ON c.id = e.course_id
                               WHERE er.user_id = ?
                               ORDER BY er.taken_at DESC");
        $stmt->execute([$studentId]);
        $results = $stmt->fetchAll();
        echo json_encode($results);
    }

    public static function getAllProgress() {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT up.user_id, u.name AS student_name, u.email, m.id AS module_id, m.title AS module_title, c.title AS course_title,
                                    up.completed, up.score, up.completed_at
                             FROM user_progress up
                             JOIN users u ON u.id = up.user_id
                             JOIN modules m ON m.id = up.module_id
                             JOIN courses c ON c.id = m.course_id
                             ORDER BY u.name, c.id, m.order_index");
        $progress = $stmt->fetchAll();
        echo json_encode($progress);
    }

    public static function getAllExamResults() {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT er.id, er.user_id, u.name AS student_name, u.email, e.id AS exam_id, e.title AS exam_title, c.title AS course_title,
                                    er.score, er.max_score, er.percentage, er.passed, er.taken_at
                             FROM exam_results er
                             JOIN users u ON u.id = er.user_id
                             JOIN exams e ON e.id = er.exam_id
                             JOIN courses c ON c.id = e.course_id
                             ORDER BY er.taken_at DESC");
        $results = $stmt->fetchAll();
        echo json_encode($results);
    }

    public static function getVisibleContent() {
        echo json_encode(Content::getAllVisible());
    }

    public static function getContentById($contentId) {
        $contentId = (int)$contentId;
        if ($contentId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de contenu invalide']);
            return;
        }
        $content = Content::findById($contentId);
        if (!$content || !$content['visible']) {
            http_response_code(404);
            echo json_encode(['error' => 'Contenu introuvable']);
            return;
        }
        echo json_encode($content);
    }

    public static function getContentByType($type) {
        $type = trim($type);
        $validTypes = ['video', 'book', 'photo', 'post', 'webinar', 'article', 'podcast', 'report'];
        if (!$type || !in_array($type, $validTypes, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Type de contenu invalide']);
            return;
        }
        echo json_encode(Content::getByType($type));
    }

    public static function getAllContent() {
        echo json_encode(Content::getAll());
    }

    public static function addContent($data, $adminId) {
        $type = trim($data['type'] ?? '');
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $mediaUrl = trim($data['media_url'] ?? '');
        $thumbnailUrl = trim($data['thumbnail_url'] ?? '');
        $price = isset($data['price']) ? (float)$data['price'] : null;
        $meta = isset($data['meta']) && is_array($data['meta']) ? $data['meta'] : null;
        $visible = isset($data['visible']) ? (int)$data['visible'] : 1;

        $validTypes = ['video', 'book', 'photo', 'post', 'webinar', 'article', 'podcast', 'report'];
        if (!$type || !in_array($type, $validTypes, true) || !$title) {
            http_response_code(400);
            echo json_encode(['error' => 'Type de contenu invalide ou titre manquant']);
            return;
        }

        $contentId = Content::create($type, $title, $description, $mediaUrl ?: null, $thumbnailUrl ?: null, $price, $meta, $adminId, $visible);
        echo json_encode(['success' => true, 'id' => $contentId]);
    }

    public static function updateContent($contentId, $data) {
        $contentId = (int)$contentId;
        if ($contentId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de contenu invalide']);
            return;
        }

        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $mediaUrl = trim($data['media_url'] ?? '');
        $thumbnailUrl = trim($data['thumbnail_url'] ?? '');
        $price = isset($data['price']) ? (float)$data['price'] : null;
        $meta = isset($data['meta']) && is_array($data['meta']) ? $data['meta'] : null;
        $visible = isset($data['visible']) ? (int)$data['visible'] : 1;

        if (!$title) {
            http_response_code(400);
            echo json_encode(['error' => 'Titre requis']);
            return;
        }

        $updated = Content::update($contentId, $title, $description, $mediaUrl ?: null, $thumbnailUrl ?: null, $price, $meta, $visible);
        echo json_encode(['success' => true, 'updated' => $updated]);
    }

    public static function deleteContent($contentId) {
        $contentId = (int)$contentId;
        if ($contentId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de contenu invalide']);
            return;
        }

        $deleted = Content::delete($contentId);
        echo json_encode(['success' => true, 'deleted' => $deleted]);
    }
}
