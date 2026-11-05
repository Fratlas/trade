<?php
/*
 * Charge les variables de configuration depuis un fichier .env (jamais versionné).
 * Emplacements lus, dans l'ordre :
 *   1. le dossier parent du site (ex. /home/uXXX/.env, hors de public_html) : le plus sûr ;
 *   2. la racine du site (/public_html/.env), bloquée côté web par .htaccess.
 * Une variable déjà définie par le serveur (SetEnv, panneau d'hébergement) n'est jamais écrasée.
 */
if (!function_exists('kt_load_env')) {
    function kt_load_env(): void {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;
        $root = dirname(__DIR__);
        foreach ([dirname($root) . '/.env', $root . '/.env'] as $file) {
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                    continue;
                }
                [$key, $value] = array_map('trim', explode('=', $line, 2));
                $key = preg_replace('/^export\s+/', '', $key);
                if (!preg_match('/^[A-Z_][A-Z0-9_]*$/', $key)) {
                    continue;
                }
                $len = strlen($value);
                if ($len >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[$len - 1] === $value[0]) {
                    $value = substr($value, 1, -1);
                }
                if (getenv($key) !== false && getenv($key) !== '') {
                    continue;
                }
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }
}
kt_load_env();
