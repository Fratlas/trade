<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

if (preg_match('/^\/(admin|student)\/(.+)\.html$/', $path, $m)) {
    $php = __DIR__ . '/' . $m[1] . '/' . $m[2] . '.php';
    if (is_file($php)) {
        header('Location: /' . $m[1] . '/' . $m[2] . '.php', true, 301);
        exit;
    }
}

if ($path !== '/' && is_file($file)) {
    return false;
}

if (strpos($path, '/api/') === 0 || $path === '/api') {
    require __DIR__ . '/public/index.php';
    return true;
}

$target = $path === '/' ? __DIR__ . '/index.html' : $file;
if (is_file($target)) {
    return false;
}

require __DIR__ . '/index.html';
return true;
