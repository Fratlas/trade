<?php
require_once __DIR__ . '/../../config/database.php';

class Progress {
    public static function getByUser($userId) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT up.module_id, up.completed, up.score, m.course_id 
                               FROM user_progress up 
                               JOIN modules m ON up.module_id = m.id 
                               WHERE up.user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function setCompleted($userId, $moduleId, $completed, $score = null) {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO user_progress (user_id, module_id, completed, completed_at, score) 
                               VALUES (?, ?, ?, NOW(), ?) 
                               ON DUPLICATE KEY UPDATE completed = ?, completed_at = IF(completed = 1, completed_at, NOW()), score = ?");
        $stmt->execute([$userId, $moduleId, $completed, $score, $completed, $score]);
        return true;
    }

    public static function getCourseProgress($userId, $courseId) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(up.completed) as completed 
                               FROM modules m 
                               LEFT JOIN user_progress up ON m.id = up.module_id AND up.user_id = ? 
                               WHERE m.course_id = ?");
        $stmt->execute([$userId, $courseId]);
        $res = $stmt->fetch();
        return ['total' => (int)$res['total'], 'completed' => (int)$res['completed']];
    }
}