<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Course.php';
require_once __DIR__ . '/Chat.php';

class Enrollment {
    public static function find(int $userId, int $courseId): ?array {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT * FROM course_enrollments WHERE user_id = ? AND course_id = ?');
        $stmt->execute([$userId, $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function isApproved(int $userId, int $courseId): bool {
        $course = Course::findById($courseId);
        if (!$course) {
            return false;
        }
        if ((float)$course['price'] <= 0) {
            return true;
        }
        $row = self::find($userId, $courseId);
        return $row && $row['status'] === 'approved';
    }

    public static function submit(int $userId, int $courseId, string $proofUrl, ?string $note = null): void {
        $pdo = getDB();
        $existing = self::find($userId, $courseId);
        if ($existing && $existing['status'] === 'approved') {
            throw new RuntimeException('Formation deja validee.');
        }
        if ($existing) {
            $stmt = $pdo->prepare("UPDATE course_enrollments SET status = 'pending', proof_url = ?, payment_note = ?, admin_note = NULL, reviewed_by = NULL, reviewed_at = NULL, created_at = NOW() WHERE id = ?");
            $stmt->execute([$proofUrl, $note, $existing['id']]);
            return;
        }
        $stmt = $pdo->prepare("INSERT INTO course_enrollments (user_id, course_id, status, proof_url, payment_note) VALUES (?, ?, 'pending', ?, ?)");
        $stmt->execute([$userId, $courseId, $proofUrl, $note]);
    }

    public static function findById(int $id): ?array {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT e.*, c.title AS course_title
                               FROM course_enrollments e
                               JOIN courses c ON c.id = e.course_id
                               WHERE e.id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function setStatus(int $enrollmentId, string $status, int $adminId, ?string $adminNote = null): bool {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return false;
        }
        $enrollment = self::findById($enrollmentId);
        if (!$enrollment) {
            return false;
        }
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE course_enrollments SET status = ?, admin_note = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $adminNote, $adminId, $enrollmentId]);
        if ($stmt->rowCount() > 0) {
            self::notifyPaymentDecision($enrollment, $status, $adminId, $adminNote);
        }
        return $stmt->rowCount() > 0;
    }

    private static function notifyPaymentDecision(array $enrollment, string $status, int $adminId, ?string $adminNote): void {
        $studentId = (int)$enrollment['user_id'];
        $courseId = (int)$enrollment['course_id'];
        $title = $enrollment['course_title'] ?? 'votre formation';

        if ($status === 'approved') {
            $body = "Bonne nouvelle ! Votre paiement pour la formation « {$title} » a été validé.\n\n"
                . "Vous pouvez accéder au cours dès maintenant :\n"
                . "/student/course.php?courseId={$courseId}";
        } else {
            $reason = $adminNote ? "\n\nMotif : {$adminNote}" : '';
            $body = "Votre paiement pour la formation « {$title} » n'a pas pu être validé.{$reason}\n\n"
                . "Veuillez renvoyer une preuve de paiement :\n"
                . "/student/enroll.php?courseId={$courseId}";
        }

        try {
            $threadId = Chat::getOrCreateThread($studentId);
            Chat::sendMessage($threadId, $adminId, $body, null);
        } catch (Throwable $e) {
            error_log('Enrollment notification: ' . $e->getMessage());
        }
    }

    public static function listForAdmin(?string $status = null): array {
        $pdo = getDB();
        $where = $status ? 'WHERE e.status = ?' : '';
        $stmt = $pdo->prepare("SELECT e.*, u.name AS student_name, u.email AS student_email, c.title AS course_title, c.price AS course_price
                               FROM course_enrollments e
                               JOIN users u ON u.id = e.user_id
                               JOIN courses c ON c.id = e.course_id
                               {$where}
                               ORDER BY FIELD(e.status, 'pending', 'approved', 'rejected'), e.created_at DESC");
        $stmt->execute($status ? [$status] : []);
        return $stmt->fetchAll();
    }
}
