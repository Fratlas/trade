<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDB();
    $sql = file_get_contents(__DIR__ . '/../sql/migration_enrollment.sql');
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $query) {
        if ($query !== '' && stripos($query, 'ALTER TABLE modules') === false) {
            $pdo->exec($query);
        }
    }
    foreach (['audio_url', 'pdf_url'] as $col) {
        try {
            $after = $col === 'audio_url' ? 'video_url' : 'audio_url';
            $pdo->exec("ALTER TABLE modules ADD COLUMN {$col} VARCHAR(500) NULL AFTER {$after}");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
        }
    }
    echo json_encode(['ok' => true, 'message' => 'Tables inscriptions et colonnes modules pretes.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
