<?php
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Subject.php';
require_once __DIR__ . '/../models/Module.php';
require_once __DIR__ . '/../models/Exam.php';

class CourseController {
    public static function getAll() {
        echo json_encode(Course::getAll());
    }

    public static function getModules($courseId) {
        $courseId = (int)$courseId;
        if ($courseId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de cours invalide']);
            return;
        }
        echo json_encode(Module::getByCourseId($courseId));
    }

    public static function getSubjects() {
        echo json_encode(Subject::getAll());
    }

    public static function getCoursesBySubject($subjectId) {
        $subjectId = (int)$subjectId;
        if ($subjectId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de matière invalide']);
            return;
        }
        echo json_encode(Subject::getCourses($subjectId));
    }

    public static function getCourseExams($courseId) {
        $courseId = (int)$courseId;
        if ($courseId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de cours invalide']);
            return;
        }
        echo json_encode(Exam::getByCourse($courseId));
    }

    public static function getExam($examId) {
        $examId = (int)$examId;
        if ($examId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID d\'examen invalide']);
            return;
        }
        $exam = Exam::findById($examId);
        if (!$exam) {
            http_response_code(404);
            echo json_encode(['error' => 'Examen introuvable']);
            return;
        }
        $exam['questions'] = Exam::getQuestions($examId);
        echo json_encode($exam);
    }

    public static function submitExam($userId, $examId, $data) {
        $examId = (int)$examId;
        $answers = isset($data['answers']) && is_array($data['answers']) ? $data['answers'] : [];
        if ($examId <= 0 || empty($answers)) {
            http_response_code(400);
            echo json_encode(['error' => 'exam_id invalide ou réponses manquantes']);
            return;
        }

        $result = Exam::submit($userId, $examId, $answers);
        if ($result === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Examen introuvable']);
            return;
        }

        echo json_encode($result);
    }
}
