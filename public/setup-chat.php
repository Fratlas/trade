<?php
/**
 * Installation des tables de messagerie (a executer une seule fois).
 * Visitez : /api/setup-chat.php
 */
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDB();
    $sql = file_get_contents(__DIR__ . '/../sql/migration_chat.sql');
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $query) {
        if ($query !== '') {
            $pdo->exec($query);
        }
    }
    echo json_encode(['ok' => true, 'message' => 'Tables chat_threads et chat_messages creees.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
