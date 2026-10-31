<?php
require_once __DIR__ . '/../src/views/bootstrap.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (strlen($name) < 2 || !$email || strlen($password) < 6) {
        $error = 'Nom, email ou mot de passe invalide.';
    } elseif ($password !== $confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (User::findByEmail($email)) {
        $error = 'Email deja utilise.';
    } else {
        $userId = User::create($email, $password, $name);
        $token = JwtHandler::encode(['user_id' => $userId, 'email' => $email, 'role' => 'student']);
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie('katsuki_token', $token, [
            'expires' => time() + JWT_EXPIRY,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        header('Location: /student/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inscription | Katsuki Trading</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/shared.css">
</head>
<body>
  <main class="kt-auth">
    <section class="kt-card kt-auth-card">
      <a class="kt-brand" href="/"><span class="kt-logo">K</span><span>Katsuki<small>Trading</small></span></a>
      <h1 class="kt-title" style="margin-top:28px">Inscription</h1>
      <p class="kt-muted">Creation PHP directe d'un compte etudiant.</p>
      <?php if ($error): ?><p class="kt-card kt-card-pad" style="border-color:rgba(239,68,68,.4);color:#fecaca"><?= kt_h($error) ?></p><?php endif; ?>
      <form method="post" class="kt-form" style="margin-top:22px">
        <input class="kt-input" name="name" minlength="2" placeholder="Nom complet" required>
        <input class="kt-input" name="email" type="email" placeholder="Email" required>
        <input class="kt-input" name="password" type="password" minlength="6" placeholder="Mot de passe" required>
        <input class="kt-input" name="confirm" type="password" minlength="6" placeholder="Confirmer le mot de passe" required>
        <button class="kt-btn primary" type="submit">Creer mon compte</button>
      </form>
      <p class="kt-muted" style="margin-top:18px">Deja inscrit ? <a class="kt-gold" href="/auth/login.php">Se connecter</a></p>
    </section>
  </main>
</body>
</html>
