<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../utils/jwthandler.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Module.php';
require_once __DIR__ . '/../models/Progress.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../models/Content.php';
require_once __DIR__ . '/../models/Enrollment.php';

function kt_course_is_free(array $course): bool {
    return (float)($course['price'] ?? 0) <= 0;
}

function kt_user_can_access_course(int $userId, int $courseId): bool {
    return Enrollment::isApproved($userId, $courseId);
}

function kt_embed_video_url(string $url): string {
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w-]+)/', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1];
    }
    return $url;
}

function kt_enrollment_badge(int $userId, int $courseId, array $course): string {
    if (kt_course_is_free($course)) {
        return 'Gratuit';
    }
    $row = Enrollment::find($userId, $courseId);
    if (!$row) {
        return 'A payer';
    }
    return match ($row['status']) {
        'approved' => 'Acces valide',
        'rejected' => 'Refuse',
        default => 'En attente'
    };
}

function kt_h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function kt_token() {
    return $_COOKIE['katsuki_token'] ?? '';
}

function kt_current_payload() {
    $token = kt_token();
    return $token ? JwtHandler::decode($token) : null;
}

function kt_current_user() {
    $payload = kt_current_payload();
    if (!$payload || empty($payload['user_id'])) {
        return null;
    }
    return User::findById((int)$payload['user_id']);
}

function kt_require_role($role) {
    $user = kt_current_user();
    if (!$user) {
        header('Location: /auth/login.php');
        exit;
    }
    if ($role && $user['role'] !== $role) {
        header('Location: ' . ($user['role'] === 'admin' ? '/admin/dashboard.php' : '/student/dashboard.php'));
        exit;
    }
    return $user;
}

function kt_logout_if_requested() {
    if (isset($_GET['logout'])) {
        setcookie('katsuki_token', '', time() - 3600, '/');
        header('Location: /auth/login.php');
        exit;
    }
}

function kt_percent($done, $total) {
    return $total > 0 ? (int)round(((int)$done / (int)$total) * 100) : 0;
}

function kt_table_count($table, $where = '1=1') {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM {$table} WHERE {$where}");
    $row = $stmt->fetch();
    return (int)($row['total'] ?? 0);
}

function kt_admin_students() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT u.id, u.email, u.name, u.role, u.subscription_type, u.subscription_expiry, u.created_at, u.last_login,
                                COALESCE((SELECT COUNT(*) FROM user_progress up WHERE up.user_id = u.id AND up.completed = 1), 0) AS completed_modules,
                                COALESCE((SELECT COUNT(*) FROM exam_results er WHERE er.user_id = u.id), 0) AS exam_attempts,
                                COALESCE((SELECT SUM(er.passed) FROM exam_results er WHERE er.user_id = u.id), 0) AS exams_passed
                         FROM users u
                         WHERE u.role = 'student'
                         ORDER BY u.name");
    return $stmt->fetchAll();
}

function kt_admin_progress() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT up.user_id, u.name AS student_name, m.id AS module_id, m.title AS module_title,
                                c.title AS course_title, up.completed, up.score, up.completed_at
                         FROM user_progress up
                         JOIN users u ON u.id = up.user_id
                         JOIN modules m ON m.id = up.module_id
                         JOIN courses c ON c.id = m.course_id
                         ORDER BY u.name, c.id, m.order_index");
    return $stmt->fetchAll();
}

function kt_all_exams() {
    $courses = Course::getAll();
    $exams = [];
    foreach ($courses as $course) {
        foreach (Exam::getByCourse((int)$course['id']) as $exam) {
            $exam['course_title'] = $course['title'];
            $exams[] = $exam;
        }
    }
    return $exams;
}

function kt_format_date($value) {
    if (!$value) {
        return '-';
    }
    $time = strtotime($value);
    return $time ? date('d/m/Y', $time) : kt_h($value);
}

function kt_exam_results($userId = null) {
    $pdo = getDB();
    $where = $userId ? 'WHERE er.user_id = ?' : '';
    $stmt = $pdo->prepare("SELECT er.id, er.user_id, u.name AS student_name, e.id AS exam_id, e.title AS exam_title,
                                  c.title AS course_title, er.score, er.max_score, er.percentage, er.passed, er.taken_at
                           FROM exam_results er
                           JOIN users u ON u.id = er.user_id
                           JOIN exams e ON e.id = er.exam_id
                           JOIN courses c ON c.id = e.course_id
                           {$where}
                           ORDER BY er.taken_at DESC");
    $stmt->execute($userId ? [$userId] : []);
    return $stmt->fetchAll();
}

function kt_student_progress_detail($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT c.id AS course_id, c.title AS course_title, m.id AS module_id, m.title AS module_title,
                                  m.order_index, COALESCE(up.completed, 0) AS completed, up.score, up.completed_at
                           FROM modules m
                           JOIN courses c ON c.id = m.course_id
                           LEFT JOIN user_progress up ON up.module_id = m.id AND up.user_id = ?
                           ORDER BY c.id, m.order_index");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function kt_page_start($title, $role, $active, $user) {
    $nav = $role === 'admin'
        ? [
            ['dashboard.php', 'Dashboard'],
            ['students.php', 'Etudiants'],
            ['courses.php', 'Formations'],
            ['resources.php', 'Ressources'],
            ['quizzes.php', 'Examens'],
            ['payments.php', 'Paiements'],
            ['modules.php', 'Modules'],
            ['messages.php', 'Messagerie'],
            ['assistant.php', 'Assistant IA']
        ]
        : [
            ['dashboard.php', 'Tableau de bord'],
            ['course.php', 'Mes formations'],
            ['progress.php', 'Ma progression'],
            ['quiz.php', 'Examens'],
            ['messages.php', 'Messagerie'],
            ['assistant.php', 'Assistant IA']
        ];
    $base = '/' . $role . '/';
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . kt_h($title) . ' | Katsuki</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800;900&display=swap" rel="stylesheet"><link rel="stylesheet" href="/assets/css/shared.css"></head><body>';
    echo '<div class="kt-shell"><aside id="sidebar" class="kt-sidebar"><div class="kt-brand"><span class="kt-logo">K</span><span>Katsuki<small>' . kt_h($role === 'admin' ? 'Admin' : 'Student') . '</small></span></div>';
    echo '<p class="kt-muted" style="margin-top:18px">' . kt_h($user['name'] ?? '') . '</p><nav class="kt-nav">';
    foreach ($nav as $item) {
        $class = $active === $item[0] ? 'active' : '';
        echo '<a class="' . $class . '" href="' . $base . $item[0] . '">' . kt_h($item[1]) . '</a>';
    }
    echo '<a href="?logout=1">Deconnexion</a></nav></aside><main class="kt-main">';
}

function kt_page_end() {
    echo '</main></div><script src="/assets/js/api.js"></script><script src="/assets/js/auth.js"></script><script src="/assets/js/ui.js"></script><script src="/assets/js/openrouter.js"></script><script src="/assets/js/messages.js"></script></body></html>';
}
