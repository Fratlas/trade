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

function kt_icon(string $name): string {
    $paths = [
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/>',
        'book' => '<path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v17H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 21.5A2.5 2.5 0 0 1 6.5 19H20v3H6.5"/>',
        'chart' => '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/>',
        'check' => '<path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9"/>',
        'chat' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20.5l1.4-4.9A8 8 0 1 1 21 12z"/>',
        'spark' => '<path d="M12 3l1.9 5.6L19.5 10l-5.6 1.9L12 17.5l-1.9-5.6L4.5 10l5.6-1.4z"/><path d="M19 17l.8 2.2L22 20l-2.2.8L19 23l-.8-2.2L16 20l2.2-.8z"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8"/><path d="M18 14.2a6.5 6.5 0 0 1 3.5 5.8"/>',
        'folder' => '<path d="M3 6.5A1.5 1.5 0 0 1 4.5 5H9l2 2.5h8.5A1.5 1.5 0 0 1 21 9v9.5a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.5z"/>',
        'card' => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 10h19"/><path d="M6.5 15h4"/>',
        'layers' => '<path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/>',
        'logout' => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 17l-5-5 5-5"/><path d="M5 12h11"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
    ];
    return '<svg class="kt-icon" viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

function kt_initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $letters ?: 'K';
}

function kt_page_start($title, $role, $active, $user) {
    $nav = $role === 'admin'
        ? [
            ['dashboard.php', 'Dashboard', 'home'],
            ['students.php', 'Etudiants', 'users'],
            ['courses.php', 'Formations', 'book'],
            ['resources.php', 'Ressources', 'folder'],
            ['quizzes.php', 'Examens', 'check'],
            ['payments.php', 'Paiements', 'card'],
            ['modules.php', 'Modules', 'layers'],
            ['messages.php', 'Messagerie', 'chat'],
            ['assistant.php', 'Assistant IA', 'spark']
        ]
        : [
            ['dashboard.php', 'Tableau de bord', 'home'],
            ['course.php', 'Mes formations', 'book'],
            ['progress.php', 'Ma progression', 'chart'],
            ['quiz.php', 'Examens', 'check'],
            ['messages.php', 'Messagerie', 'chat'],
            ['assistant.php', 'Assistant IA', 'spark']
        ];
    $base = '/' . $role . '/';
    $name = (string)($user['name'] ?? '');
    $brand = '<span class="kt-logo">K</span><span>Katsuki<small>' . kt_h($role === 'admin' ? 'Admin' : 'Academy') . '</small></span>';
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="theme-color" content="#08080a">';
    echo '<title>' . kt_h($title) . ' | Katsuki</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="/assets/css/shared.css">';
    // Core helpers load first so inline page scripts can use KTApi / KTUi right away.
    echo '<script src="/assets/js/api.js"></script><script src="/assets/js/auth.js"></script><script src="/assets/js/ui.js"></script>';
    echo '</head><body>';
    echo '<header class="kt-mobilebar"><a class="kt-brand" href="' . $base . 'dashboard.php">' . $brand . '</a>';
    echo '<button class="kt-btn sm" type="button" aria-label="Ouvrir le menu" aria-controls="sidebar" onclick="document.getElementById(\'sidebar\').classList.toggle(\'open\')">' . kt_icon('menu') . 'Menu</button></header>';
    echo '<div class="kt-shell"><aside id="sidebar" class="kt-sidebar"><a class="kt-brand" href="' . $base . 'dashboard.php">' . $brand . '</a>';
    echo '<div class="kt-user"><span class="kt-avatar">' . kt_h(kt_initials($name)) . '</span><span style="min-width:0"><strong>' . kt_h($name) . '</strong><span>' . kt_h($role === 'admin' ? 'Administrateur' : 'Etudiant') . '</span></span></div>';
    echo '<p class="kt-nav-label">Navigation</p><nav class="kt-nav">';
    foreach ($nav as $item) {
        $isActive = $active === $item[0];
        echo '<a class="' . ($isActive ? 'active' : '') . '"' . ($isActive ? ' aria-current="page"' : '') . ' href="' . $base . $item[0] . '">' . kt_icon($item[2]) . '<span>' . kt_h($item[1]) . '</span></a>';
    }
    echo '</nav><nav class="kt-nav kt-sidebar-foot"><a href="?logout=1">' . kt_icon('logout') . '<span>Deconnexion</span></a></nav></aside>';
    echo '<div class="kt-scrim" onclick="document.getElementById(\'sidebar\').classList.remove(\'open\')"></div><main class="kt-main">';
}

function kt_page_end() {
    echo '</main></div><script src="/assets/js/openrouter.js"></script><script src="/assets/js/messages.js"></script></body></html>';
}
