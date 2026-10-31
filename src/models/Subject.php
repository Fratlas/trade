<?php
require_once __DIR__ . '/../../config/database.php';

class Subject {
    public static function getAll() {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT * FROM subjects ORDER BY id");
        return $stmt->fetchAll();
    }

    public static function findById($id) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getCourses($subjectId) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT c.* FROM courses c
                               JOIN course_subjects cs ON c.id = cs.course_id
                               WHERE cs.subject_id = ?
                               ORDER BY c.id");
        $stmt->execute([$subjectId]);
        $courses = $stmt->fetchAll();
        foreach ($courses as &$course) {
            $course['features'] = json_decode($course['features'], true);
        }
        return $courses;
    }
}
