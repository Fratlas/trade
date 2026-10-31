<?php
require_once __DIR__ . '/../../config/database.php';

class Content {
    public static function create($type, $title, $description, $mediaUrl, $thumbnailUrl, $price, $meta, $createdBy, $visible = 1) {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO contents (type, title, description, media_url, thumbnail_url, price, meta, created_by, visible) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $type,
            $title,
            $description,
            $mediaUrl,
            $thumbnailUrl,
            $price,
            $meta ? json_encode($meta) : null,
            $createdBy,
            $visible
        ]);
        return $pdo->lastInsertId();
    }

    public static function getAllVisible() {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT id, type, title, description, media_url, thumbnail_url, price, meta, created_by, visible, created_at FROM contents WHERE visible = 1 ORDER BY created_at DESC");
        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            $item['meta'] = $item['meta'] ? json_decode($item['meta'], true) : null;
        }
        return $items;
    }

    public static function getByType($type) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, type, title, description, media_url, thumbnail_url, price, meta, created_by, visible, created_at FROM contents WHERE type = ? AND visible = 1 ORDER BY created_at DESC");
        $stmt->execute([$type]);
        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            $item['meta'] = $item['meta'] ? json_decode($item['meta'], true) : null;
        }
        return $items;
    }

    public static function update($id, $title, $description, $mediaUrl, $thumbnailUrl, $price, $meta, $visible) {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE contents SET title = ?, description = ?, media_url = ?, thumbnail_url = ?, price = ?, meta = ?, visible = ? WHERE id = ?");
        $stmt->execute([
            $title,
            $description,
            $mediaUrl,
            $thumbnailUrl,
            $price,
            $meta ? json_encode($meta) : null,
            $visible,
            $id
        ]);
        return $stmt->rowCount();
    }

    public static function delete($id) {
        $pdo = getDB();
        $stmt = $pdo->prepare("DELETE FROM contents WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }

    public static function getAll() {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT id, type, title, description, media_url, thumbnail_url, price, meta, created_by, visible, created_at FROM contents ORDER BY created_at DESC");
        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            $item['meta'] = $item['meta'] ? json_decode($item['meta'], true) : null;
        }
        return $items;
    }

    public static function findById($id) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, type, title, description, media_url, thumbnail_url, price, meta, created_by, visible, created_at FROM contents WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if ($item) {
            $item['meta'] = $item['meta'] ? json_decode($item['meta'], true) : null;
        }
        return $item;
    }
}
