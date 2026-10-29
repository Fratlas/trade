<?php
declare(strict_types=1);
// CLI-only. Never publish administrator credentials or expose this script via HTTP.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
$email=strtolower(trim((string)(getenv('ADMIN_EMAIL')?:'')));
$name=trim((string)(getenv('ADMIN_NAME')?:'Administrateur Katsuki'));
$password=(string)(getenv('ADMIN_PASSWORD')?:'');
if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<12 || strlen($name)<2) {
    fwrite(STDERR,"Définir ADMIN_EMAIL, ADMIN_NAME et ADMIN_PASSWORD (12 caractères min.).\n");
    exit(1);
}
$db=getDB();
$hash=password_hash($password,PASSWORD_DEFAULT);
$db->beginTransaction();
try {
    $q=$db->prepare('SELECT id,role FROM users WHERE email=? FOR UPDATE');
    $q->execute([$email]);
    $existing=$q->fetch();
    if ($existing) {
        if ($existing['role']!=='admin') {
            fwrite(STDERR,"Ce compte existe déjà sans rôle administrateur : opération annulée.\n");
            $db->rollBack();exit(2);
        }
        $q=$db->prepare("UPDATE users SET password=?,name=? WHERE id=? AND role='admin'");
        $q->execute([$hash,$name,$existing['id']]);
        $id=$existing['id'];
    } else {
        $q=$db->prepare("INSERT INTO users (email,name,password,role) VALUES (?,?,?,'admin')");
        $q->execute([$email,$name,$hash]);
        $id=$db->lastInsertId();
    }
    $db->commit();
    echo "Administrateur configuré avec ID ".$id."\n";
} catch(Throwable $e) {
    if($db->inTransaction())$db->rollBack();
    fwrite(STDERR,"Échec de création; consulter les logs du serveur.\n");exit(3);
}
