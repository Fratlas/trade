<?php
require_once __DIR__ . '/../../config/database.php';

class Course {
    public static function getAll() {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT * FROM courses ORDER BY id");
        $courses = $stmt->fetchAll();
        foreach ($courses as &$course) {
            $course['features'] = json_decode($course['features'], true);
        }
        return $courses;
    }

    public static function findById($id) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$id]);
        $course = $stmt->fetch();
        if ($course) {
            $course['features'] = json_decode($course['features'], true);
        }
        return $course;
    }

    public static function getBySubject($subjectId) {
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
