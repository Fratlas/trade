<?php
require_once __DIR__ . '/../src/views/bootstrap.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $user = $email ? User::findByEmail($email) : null;

    if (!$user || !password_verify($password, $user['password'])) {
        $error = 'Identifiants incorrects.';
    } elseif ($user['status'] !== 'active') {
        $error = 'Compte desactive.';
    } else {
        User::updateLastLogin($user['id']);
        $token = JwtHandler::encode(['user_id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']]);
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie('katsuki_token', $token, [
            'expires' => time() + JWT_EXPIRY,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        header('Location: ' . ($user['role'] === 'admin' ? '/admin/dashboard.php' : '/student/dashboard.php'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion | Katsuki Trading</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/shared.css">
</head>
<body>
  <main class="kt-auth">
    <section class="kt-card kt-auth-card">
      <a class="kt-brand" href="/"><span class="kt-logo">K</span><span>Katsuki<small>Trading</small></span></a>
      <h1 class="kt-title" style="margin-top:28px">Connexion</h1>
      <p class="kt-muted">Connexion PHP directe avec la base Katsuki.</p>
      <?php if ($error): ?><p class="kt-card kt-card-pad" style="border-color:rgba(239,68,68,.4);color:#fecaca"><?= kt_h($error) ?></p><?php endif; ?>
      <form method="post" class="kt-form" style="margin-top:22px">
        <input class="kt-input" name="email" type="email" placeholder="Email" autocomplete="email" required>
        <input class="kt-input" name="password" type="password" placeholder="Mot de passe" autocomplete="current-password" required>
        <button class="kt-btn primary" type="submit">Se connecter</button>
      </form>
      <p class="kt-muted" style="margin-top:18px">Nouveau membre ? <a class="kt-gold" href="/auth/register.php">Creer un compte</a></p>
    </section>
  </main>
</body>
</html>
