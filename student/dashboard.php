<?php
require_once __DIR__ . '/../src/views/bootstrap.php';
kt_logout_if_requested();
$user = kt_require_role('student');
$courses = Course::getAll();
$progressRows = Progress::getByUser($user['id']);
$contents = Content::getAllVisible();
$results = kt_exam_results($user['id']);

$progressByCourse = [];
foreach ($progressRows as $row) {
    $courseId = (int)$row['course_id'];
    $progressByCourse[$courseId] = $progressByCourse[$courseId] ?? ['completed' => 0];
    if ((int)$row['completed'] === 1) {
        $progressByCourse[$courseId]['completed']++;
    }
}
$totalModules = array_sum(array_map(fn($course) => (int)$course['modules_count'], $courses));
$doneModules = array_sum(array_map(fn($row) => (int)$row['completed'], $progressByCourse));
$globalPct = kt_percent($doneModules, $totalModules);

// Prochain module à suivre : premier module non terminé d'une formation accessible.
$doneIds = [];
foreach ($progressRows as $row) {
    if ((int)$row['completed'] === 1) {
        $doneIds[(int)$row['module_id']] = true;
    }
}
$resume = null;
foreach ($courses as $course) {
    if (!kt_user_can_access_course((int)$user['id'], (int)$course['id'])) {
        continue;
    }
    $mods = Module::getByCourseId((int)$course['id']);
    foreach ($mods as $i => $mod) {
        if (empty($doneIds[(int)$mod['id']])) {
            $resume = ['course' => $course, 'module' => $mod, 'index' => $i, 'total' => count($mods)];
            break 2;
        }
    }
}
$passedExams = count(array_filter($results, fn($r) => (int)$r['passed'] === 1));
kt_page_start('Tableau de bord etudiant', 'student', 'dashboard.php', $user);
?>
<div class="kt-topbar">
  <div>
    <span class="kt-eyebrow">Espace étudiant</span>
    <h1 class="kt-title">Bonjour <?= kt_h(explode(' ', $user['name'])[0]) ?></h1>
    <p class="kt-muted">Reprends la formation là où tu t'es arrêté.</p>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a class="kt-btn" href="/student/messages.php">Messagerie</a>
    <a class="kt-btn" href="/student/assistant.php">Assistant IA</a>
  </div>
</div>
<?php if ($resume):
  $resumeTitle = preg_replace('/^CH\s*\d+\s*[—–-]\s*/u', '', $resume['module']['title']);
?>
<a class="kt-card kt-resume" href="/student/course.php?courseId=<?= (int)$resume['course']['id'] ?>&moduleId=<?= (int)$resume['module']['id'] ?>">
  <div>
    <span class="kt-eyebrow"><?= kt_h($resume['course']['title']) ?> · Module <?= $resume['index'] + 1 ?>/<?= $resume['total'] ?></span>
    <h2><?= kt_h($resumeTitle) ?></h2>
    <p class="kt-muted">Leçon, cartes de révision, quiz et outils pratiques t'attendent.</p>
  </div>
  <span class="kt-btn primary">Continuer →</span>
</a>
<?php endif; ?>
<section class="kt-grid cols-3" style="margin-top:18px">
  <article class="kt-card kt-card-pad kt-stat-row">
    <div class="kt-ring" style="--size:76px"><svg viewBox="0 0 64 64"><circle class="track" cx="32" cy="32" r="27"/><circle class="bar" cx="32" cy="32" r="27" stroke-dasharray="169.6" stroke-dashoffset="<?= round(169.6 * (1 - $globalPct / 100), 1) ?>"/></svg><b><?= $globalPct ?>%</b></div>
    <div><span class="kt-muted">Progression globale</span><div class="kt-stat-value kt-num"><?= $doneModules ?><small>/<?= $totalModules ?></small></div><span class="kt-muted kt-small">modules terminés</span></div>
  </article>
  <article class="kt-card kt-card-pad"><span class="kt-muted">Dernière note</span><div class="kt-stat-value kt-num"><?= $results ? (int)$results[0]['percentage'] . '<small>/100</small>' : '—' ?></div><span class="kt-muted kt-small"><?= $results ? kt_h($results[0]['exam_title']) : 'Aucun résultat pour le moment.' ?></span></article>
  <article class="kt-card kt-card-pad"><span class="kt-muted">Examens réussis</span><div class="kt-stat-value kt-num"><?= $passedExams ?><small>/<?= count($results) ?></small></div><a class="kt-gold kt-small" href="/student/quiz.php">Passer un examen →</a></article>
</section>
<section style="margin-top:30px">
  <h2>Mes formations</h2>
  <div class="kt-grid cols-3">
    <?php foreach ($courses as $course):
      $done = (int)($progressByCourse[(int)$course['id']]['completed'] ?? 0);
      $pct = kt_percent($done, (int)$course['modules_count']);
      $cid = (int)$course['id'];
      $canAccess = kt_user_can_access_course((int)$user['id'], $cid);
      $badge = kt_enrollment_badge((int)$user['id'], $cid, $course);
      if ($canAccess) {
        $ctaHref = '/student/course.php?courseId=' . $cid;
        $ctaLabel = 'Continuer';
      } elseif (!kt_course_is_free($course)) {
        $ctaHref = '/student/enroll.php?courseId=' . $cid;
        $ctaLabel = $badge === 'En attente' ? 'En attente' : ($badge === 'Refuse' ? 'Renvoyer preuve' : 'S inscrire');
      } else {
        $ctaHref = '/student/course.php?courseId=' . $cid;
        $ctaLabel = 'Commencer';
      }
    ?>
      <article class="kt-card kt-card-pad kt-course-card">
        <div style="display:flex;gap:8px;flex-wrap:wrap"><span class="kt-badge neutral"><?= kt_h($course['level']) ?></span><span class="kt-badge <?= $canAccess ? 'success' : '' ?>"><?= kt_h($badge) ?></span></div>
        <h3><?= kt_h($course['title']) ?></h3>
        <p class="kt-muted"><?= kt_h(mb_substr($course['full_desc'], 0, 140)) ?>…</p>
        <?php if (!kt_course_is_free($course)): ?><p class="kt-gold kt-num" style="margin:0;font-weight:700"><?= kt_h($course['price']) ?> EUR</p><?php endif; ?>
        <div>
          <div class="kt-progress"><span style="width:<?= $pct ?>%"></span></div>
          <p class="kt-muted kt-small" style="margin:6px 0 0"><?= $pct ?> % terminé · <?= (int)$course['modules_count'] ?> modules</p>
        </div>
        <a class="kt-btn <?= $ctaLabel === 'En attente' ? '' : 'primary' ?>" href="<?= $ctaHref ?>"><?= kt_h($ctaLabel) ?></a>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<section class="kt-grid cols-2" style="margin-top:22px">
  <article class="kt-card kt-card-pad">
    <h2>Dernieres notes</h2>
    <table class="kt-table"><thead><tr><th>Examen</th><th>Note</th><th>Statut</th></tr></thead><tbody>
      <?php if (!$results): ?><tr><td colspan="3" class="kt-muted">Aucun resultat.</td></tr><?php endif; ?>
      <?php foreach (array_slice($results, 0, 5) as $result): ?><tr><td><?= kt_h($result['exam_title']) ?></td><td class="kt-num"><?= (int)$result['percentage'] ?>/100</td><td><span class="kt-badge <?= (int)$result['passed'] ? 'success' : 'danger' ?>"><?= (int)$result['passed'] ? 'Réussi' : 'Échoué' ?></span></td></tr><?php endforeach; ?>
    </tbody></table>
  </article>
  <article class="kt-card kt-card-pad">
    <h2>Ressources recentes</h2>
    <div class="kt-grid">
      <?php if (!$contents): ?><p class="kt-muted">Aucune ressource publiee.</p><?php endif; ?>
      <?php foreach (array_slice($contents, 0, 3) as $item): ?><a class="kt-card kt-card-pad kt-resource" href="<?= kt_h($item['media_url'] ?: '#') ?>"><strong><?= kt_h($item['title']) ?></strong><span class="kt-badge neutral"><?= kt_h($item['type']) ?></span></a><?php endforeach; ?>
    </div>
  </article>
</section>
<?php kt_page_end(); ?>
