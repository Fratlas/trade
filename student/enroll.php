<?php
require_once __DIR__ . '/../src/views/bootstrap.php';
require_once __DIR__ . '/../src/utils/FileUpload.php';
kt_logout_if_requested();
$user = kt_require_role('student');

$courseId = (int)($_GET['courseId'] ?? 0);
$course = $courseId ? Course::findById($courseId) : null;
if (!$course) {
    header('Location: /student/dashboard.php');
    exit;
}

if (kt_course_is_free($course) || kt_user_can_access_course((int)$user['id'], $courseId)) {
    header('Location: /student/course.php?courseId=' . $courseId);
    exit;
}

$error = '';
$enrollment = Enrollment::find((int)$user['id'], $courseId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($_FILES['proof']['name'])) {
            throw new RuntimeException('Veuillez joindre une preuve de paiement (image ou PDF).');
        }
        $proofUrl = FileUpload::save($_FILES['proof'], 'payments', FileUpload::proofTypes(), 10485760);
        Enrollment::submit((int)$user['id'], $courseId, $proofUrl, trim($_POST['payment_note'] ?? ''));
        header('Location: /student/enroll.php?courseId=' . $courseId . '&sent=1');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
    $enrollment = Enrollment::find((int)$user['id'], $courseId);
}

kt_page_start('Inscription formation', 'student', 'course.php', $user);
?>
<div class="kt-topbar">
  <div>
    <button class="kt-btn kt-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">Menu</button>
    <h1 class="kt-title"><?= kt_h($course['title']) ?></h1>
    <p class="kt-muted">Formation payante — <?= kt_h($course['price']) ?> EUR</p>
  </div>
</div>
<section class="kt-card kt-card-pad" style="max-width:640px">
  <?php if (isset($_GET['sent']) || ($enrollment && $enrollment['status'] === 'pending')): ?>
    <h2>Demande envoyee</h2>
    <p class="kt-muted">Votre preuve de paiement est en cours de verification par l equipe Katsuki. Vous serez notifie par <a class="kt-gold" href="/student/messages.php">messagerie</a> des validation.</p>
    <?php if ($enrollment && $enrollment['proof_url']): ?>
      <p><a class="kt-gold" href="<?= kt_h($enrollment['proof_url']) ?>" target="_blank">Voir ma preuve</a></p>
    <?php endif; ?>
    <a class="kt-btn" href="/student/dashboard.php">Retour au tableau de bord</a>
  <?php elseif ($enrollment && $enrollment['status'] === 'rejected'): ?>
    <h2>Paiement refuse</h2>
    <p class="kt-muted"><?= kt_h($enrollment['admin_note'] ?? 'Veuillez renvoyer une preuve valide.') ?></p>
    <?php if ($error): ?><p style="color:#fecaca"><?= kt_h($error) ?></p><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="kt-form" style="margin-top:16px">
      <input type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" required>
      <textarea class="kt-textarea" name="payment_note" placeholder="Reference de paiement, date, mode..."></textarea>
      <button class="kt-btn primary" type="submit">Renvoyer la preuve</button>
    </form>
  <?php else: ?>
    <h2>Envoyer une preuve de paiement</h2>
    <p class="kt-muted">Pour acceder a cette formation, effectuez le paiement de <strong><?= kt_h($course['price']) ?> EUR</strong> puis televersez une capture ou un recu (JPG, PNG, PDF).</p>
    <?php if ($error): ?><p style="color:#fecaca"><?= kt_h($error) ?></p><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="kt-form" style="margin-top:16px">
      <label class="kt-muted">Preuve de paiement *</label>
      <input type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" required>
      <textarea class="kt-textarea" name="payment_note" placeholder="Reference, date, mode de paiement (Mobile Money, virement...)"></textarea>
      <button class="kt-btn primary" type="submit">Envoyer pour validation</button>
    </form>
  <?php endif; ?>
</section>
<?php kt_page_end(); ?>
