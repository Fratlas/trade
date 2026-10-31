<?php
require_once __DIR__ . '/../models/Progress.php';

class ProgressController {
    public static function getByUser($userId) {
        $progress = Progress::getByUser($userId);
        echo json_encode($progress);
    }

    public static function update($userId, $data) {
        $moduleId = (int)($data['module_id'] ?? 0);
        $completed = (bool)($data['completed'] ?? false);
        $score = isset($data['score']) ? (int)$data['score'] : null;
        if (!$moduleId) {
            http_response_code(400);
            echo json_encode(['error' => 'module_id requis']);
            return;
        }
        Progress::setCompleted($userId, $moduleId, $completed, $score);
        echo json_encode(['success' => true]);
    }

    public static function getCourseProgress($userId, $courseId) {
        $progress = Progress::getCourseProgress($userId, $courseId);
        echo json_encode($progress);
    }
}