<?php
require_once __DIR__ . '/../../config/database.php';

class User {
    public static function findByEmail($email) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public static function findById($id) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, email, name, phone, bio, avatar_url, role, subscription_type, subscription_expiry, status, created_at, last_login FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create($email, $password, $name, $phone = null, $bio = null, $avatarUrl = null) {
        $pdo = getDB();
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password, name, phone, bio, avatar_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$email, $hashed, $name, $phone, $bio, $avatarUrl]);
        return $pdo->lastInsertId();
    }

    public static function updateLastLogin($id) {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function updateProfile($id, $name, $phone = null, $bio = null, $avatarUrl = null) {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, bio = ?, avatar_url = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $bio, $avatarUrl, $id]);
        return $stmt->rowCount();
    }
}