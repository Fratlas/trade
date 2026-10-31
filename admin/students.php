<?php
require_once __DIR__ . '/../src/views/bootstrap.php';
kt_logout_if_requested();
$user = kt_require_role('admin');
$students = kt_admin_students();
$search = trim($_GET['q'] ?? '');
$detailId = (int)($_GET['studentId'] ?? 0);
if ($search !== '') {
    $students = array_values(array_filter($students, fn($s) => stripos($s['name'] . $s['email'], $search) !== false));
}
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="etudiants.csv"');
    echo "name,email,completed_modules,exam_attempts\n";
    foreach (kt_admin_students() as $s) {
        echo '"' . str_replace('"', '""', $s['name']) . '","' . str_replace('"', '""', $s['email']) . '",' . (int)$s['completed_modules'] . ',' . (int)$s['exam_attempts'] . "\n";
    }
    exit;
}
$detailProgress = $detailId ? kt_student_progress_detail($detailId) : [];
$detailExams = $detailId ? kt_exam_results($detailId) : [];
kt_page_start('Etudiants', 'admin', 'students.php', $user);
?>
<div class="kt-topbar">
  <div>
    <button class="kt-btn kt-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">Menu</button>
    <h1 class="kt-title">Etudiants</h1>
  </div>
  <a class="kt-btn primary" href="?export=csv">Exporter CSV</a>
</div>
<form method="get" style="margin-bottom:16px">
  <input class="kt-input" name="q" value="<?= kt_h($search) ?>" placeholder="Rechercher par nom ou email">
</form>
<section class="kt-card kt-card-pad">
  <table class="kt-table">
    <thead><tr><th>Nom</th><th>Email</th><th>Progression</th><th>Derniere connexion</th><th>Examens</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($students as $student): ?>
        <tr>
          <td><?= kt_h($student['name']) ?></td>
          <td><?= kt_h($student['email']) ?></td>
          <td><?= (int)$student['completed_modules'] ?> modules</td>
          <td><?= kt_format_date($student['last_login']) ?></td>
          <td><?= (int)$student['exam_attempts'] ?></td>
          <td>
            <a class="kt-btn" href="?studentId=<?= (int)$student['id'] ?><?= $search ? '&q=' . urlencode($search) : '' ?>">Voir</a>
            <a class="kt-btn" href="/admin/messages.php?studentId=<?= (int)$student['id'] ?>">Message</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$students): ?><tr><td colspan="6" class="kt-muted">Aucun etudiant trouve.</td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
<?php if ($detailId): ?>
<section class="kt-card kt-card-pad" style="margin-top:22px">
  <h2>Profil etudiant</h2>
  <h3>Progression</h3>
  <table class="kt-table">
    <?php foreach (array_slice($detailProgress, 0, 20) as $row): ?>
      <tr><td><?= kt_h($row['course_title']) ?></td><td><?= kt_h($row['module_title']) ?></td><td><?= (int)$row['completed'] ? 'Termine' : 'A faire' ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$detailProgress): ?><tr><td colspan="3" class="kt-muted">Aucune progression.</td></tr><?php endif; ?>
  </table>
  <h3 style="margin-top:18px">Examens</h3>
  <table class="kt-table">
    <?php foreach ($detailExams as $exam): ?>
      <tr><td><?= kt_h($exam['exam_title']) ?></td><td><?= (int)$exam['percentage'] ?>/100</td><td><?= (int)$exam['passed'] ? 'Reussi' : 'Echoue' ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$detailExams): ?><tr><td class="kt-muted">Aucun resultat</td></tr><?php endif; ?>
  </table>
</section>
<?php endif; ?>
<?php kt_page_end(); ?>
