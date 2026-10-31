<?php

class FileUpload {
    public static function save(array $file, string $subdir, array $allowed, int $maxBytes = 52428800): string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Erreur lors de l envoi du fichier.');
        }
        if (($file['size'] ?? 0) > $maxBytes) {
            throw new RuntimeException('Fichier trop volumineux.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Type de fichier non autorise.');
        }

        $dir = __DIR__ . '/../../uploads/' . trim($subdir, '/') . '/' . date('Y/m');
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException('Impossible de creer le dossier upload.');
        }

        $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Echec de l enregistrement du fichier.');
        }

        return '/uploads/' . trim($subdir, '/') . '/' . date('Y/m') . '/' . $name;
    }

    public static function proofTypes(): array {
        return [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf'
        ];
    }

    public static function videoTypes(): array {
        return [
            'video/mp4' => 'mp4',
            'video/webm' => 'webm'
        ];
    }

    public static function audioTypes(): array {
        return [
            'audio/mpeg' => 'mp3',
            'audio/mp3' => 'mp3',
            'audio/wav' => 'wav',
            'audio/x-wav' => 'wav',
            'audio/ogg' => 'ogg'
        ];
    }

    public static function pdfTypes(): array {
        return ['application/pdf' => 'pdf'];
    }
}
