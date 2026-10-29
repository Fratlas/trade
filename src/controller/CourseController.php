<?php
require_once __DIR__ . '/../models/Module.php';
class CourseController {
    public static function getAll(): void {
        echo json_encode(Course::getAll(), JSON_UNESCAPED_UNICODE);
    }
    public static function getModules(int $courseId): void {
        echo json_encode(Module::getByCourseId($courseId), JSON_UNESCAPED_UNICODE);
    }
}
