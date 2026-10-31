<?php
require_once __DIR__ . '/../../config/database.php';

class Exam {
    public static function getByCourse($courseId) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, title, passing_score, duration_minutes, instructions, created_at
                               FROM exams WHERE course_id = ? ORDER BY id");
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }

    public static function findById($examId) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT e.*, c.title AS course_title FROM exams e
                               JOIN courses c ON e.course_id = c.id
                               WHERE e.id = ?");
        $stmt->execute([$examId]);
        return $stmt->fetch();
    }

    public static function getQuestions($examId) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, question, options, points FROM exam_questions WHERE exam_id = ? ORDER BY id");
        $stmt->execute([$examId]);
        $questions = $stmt->fetchAll();
        foreach ($questions as &$question) {
            $question['options'] = json_decode($question['options'], true);
            $question['points'] = (int)$question['points'];
        }
        return $questions;
    }

    public static function submit($userId, $examId, $answers) {
        $exam = self::findById($examId);
        if (!$exam) {
            return null;
        }

        $questions = self::getQuestions($examId);
        $totalScore = 0;
        $earnedScore = 0;

        foreach ($questions as $question) {
            $totalScore += $question['points'];
            $selected = isset($answers[$question['id']]) ? $answers[$question['id']] : null;
            if ($selected !== null && $selected === self::getCorrectOption($question['id'])) {
                $earnedScore += $question['points'];
            }
        }

        $percentage = $totalScore ? round(($earnedScore / $totalScore) * 100) : 0;
        $passed = $percentage >= (int)$exam['passing_score'] ? 1 : 0;

        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO exam_results (user_id, exam_id, score, max_score, percentage, passed, answers, taken_at)
                               VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $userId,
            $examId,
            $earnedScore,
            $totalScore,
            $percentage,
            $passed,
            json_encode($answers)
        ]);

        return [
            'exam_id' => (int)$examId,
            'user_id' => (int)$userId,
            'score' => (int)$earnedScore,
            'max_score' => (int)$totalScore,
            'percentage' => (int)$percentage,
            'passed' => (bool)$passed,
            'passing_score' => (int)$exam['passing_score'],
            'taken_at' => date('Y-m-d H:i:s')
        ];
    }

    private static function getCorrectOption($questionId) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT correct_option FROM exam_questions WHERE id = ?");
        $stmt->execute([$questionId]);
        $result = $stmt->fetch();
        return $result ? $result['correct_option'] : null;
    }
}
