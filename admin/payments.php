<?php
require_once __DIR__ . '/../src/views/bootstrap.php';
require_once __DIR__ . '/../src/utils/FileUpload.php';
kt_logout_if_requested();
$user = kt_require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['enrollment_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $note = trim($_POST['admin_note'] ?? '');
    if ($id && in_array($action, ['approved', 'rejected'], true)) {
        Enrollment::setStatus($id, $action, (int)$user['id'], $note);
        header('Location: /admin/payments.php?saved=1');
        exit;
    }
}

$filter = $_GET['status'] ?? '';
$enrollments = Enrollment::listForAdmin($filter ?: null);
kt_page_start('Validation des paiements', 'admin', 'payments.php', $user);
?>
<div class="kt-topbar">
  <div>
    <button class="kt-btn kt-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">Menu</button>
    <h1 class="kt-title">Paiements et inscriptions</h1>
    <p class="kt-muted">Validez les preuves de paiement. L etudiant sera notifie automatiquement par messagerie.</p>
  </div>
</div>
<?php if (isset($_GET['saved'])): ?><p class="kt-badge" style="margin-bottom:16px">Decision enregistree. Notification envoyee a l etudiant.</p><?php endif; ?>
<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
  <a class="kt-btn <?= $filter === '' ? 'primary' : '' ?>" href="?">Tous</a>
  <a class="kt-btn <?= $filter === 'pending' ? 'primary' : '' ?>" href="?status=pending">En attente</a>
  <a class="kt-btn <?= $filter === 'approved' ? 'primary' : '' ?>" href="?status=approved">Valides</a>
  <a class="kt-btn <?= $filter === 'rejected' ? 'primary' : '' ?>" href="?status=rejected">Refuses</a>
</div>
<section class="kt-card kt-card-pad">
  <table class="kt-table">
    <thead><tr><th>Etudiant</th><th>Formation</th><th>Prix</th><th>Statut</th><th>Preuve</th><th>Date</th><th>Action</th></tr></thead>
    <tbody>
      <?php foreach ($enrollments as $row): ?>
        <tr>
          <td><?= kt_h($row['student_name']) ?><br><span class="kt-muted"><?= kt_h($row['student_email']) ?></span></td>
          <td><?= kt_h($row['course_title']) ?></td>
          <td><?= kt_h($row['course_price']) ?> EUR</td>
          <td><span class="kt-badge"><?= kt_h($row['status']) ?></span></td>
          <td>
            <?php if ($row['proof_url']): ?>
              <a class="kt-gold" href="<?= kt_h($row['proof_url']) ?>" target="_blank" rel="noopener">Voir preuve</a>
            <?php else: ?>-<?php endif; ?>
            <?php if ($row['payment_note']): ?><br><span class="kt-muted"><?= kt_h($row['payment_note']) ?></span><?php endif; ?>
          </td>
          <td><?= kt_format_date($row['created_at']) ?></td>
          <td>
            <?php if ($row['status'] === 'pending'): ?>
              <form method="post" class="kt-form" style="min-width:200px">
                <input type="hidden" name="enrollment_id" value="<?= (int)$row['id'] ?>">
                <input class="kt-input" name="admin_note" placeholder="Note admin (optionnel)">
                <button class="kt-btn primary" name="action" value="approved" type="submit">Valider</button>
                <button class="kt-btn danger" name="action" value="rejected" type="submit">Refuser</button>
              </form>
            <?php else: ?>
              <span class="kt-muted"><?= kt_h($row['admin_note'] ?? '-') ?></span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$enrollments): ?><tr><td colspan="7" class="kt-muted">Aucune demande.</td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
<?php kt_page_end(); ?>
