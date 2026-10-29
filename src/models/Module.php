<?php
// Course.php
require_once __DIR__ . '/../../config/database.php';

class Course {
    public static function getAll() {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT * FROM courses ORDER BY id");
        return $stmt->fetchAll();
    }
}

// Module.php
class Module {
    public static function getByCourseId($courseId) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM modules WHERE course_id = ? ORDER BY order_index");
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }
}