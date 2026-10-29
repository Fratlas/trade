<?php
declare(strict_types=1);
class AdminController {
    public static function dashboard(int $userId): void {
        $db=getDB();
        $q=$db->prepare('SELECT role FROM users WHERE id=?');
        $q->execute([$userId]);
        if ($q->fetchColumn() !== 'admin') {
            http_response_code(403);
            echo json_encode(['error'=>'Accès réservé aux administrateurs']);
            return;
        }
        $users=(int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $students=(int)$db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
        $admins=(int)$db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
        $recent=$db->query('SELECT id,name,email,role,created_at FROM users ORDER BY id DESC LIMIT 30')->fetchAll();
        echo json_encode(['users'=>$users,'students'=>$students,'admins'=>$admins,'recent_users'=>$recent],JSON_UNESCAPED_UNICODE);
    }
}
