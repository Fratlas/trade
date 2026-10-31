<?php
/**
 * Point d'entree web : page d'accueil HTML.
 * L'API est servie uniquement via /api/* (voir .htaccess -> public/index.php).
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (preg_match('#^/api(/|$)#', $path)) {
    require __DIR__ . '/public/index.php';
    exit;
}

$landing = __DIR__ . '/index.html';
if (!is_file($landing)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Page d\'accueil introuvable (index.html).';
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
readfile($landing);
