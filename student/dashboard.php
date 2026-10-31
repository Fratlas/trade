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
kt_page_start('Tableau de bord etudiant', 'student', 'dashboard.php', $user);
?>
<div class="kt-topbar">
  <div>
    <button class="kt-btn kt-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">Menu</button>
    <h1 class="kt-title">Bonjour <?= kt_h(explode(' ', $user['name'])[0]) ?></h1>
    <p class="kt-muted">Reprenez la formation la ou vous vous etes arrete.</p>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a class="kt-btn" href="/student/messages.php">Messagerie</a>
    <a class="kt-btn primary" href="/student/assistant.php">Assistant IA</a>
  </div>
</div>
<section class="kt-grid cols-3">
  <article class="kt-card kt-card-pad"><span class="kt-badge">Progression globale</span><h2 style="font-size:44px;margin:18px 0 10px"><?= $globalPct ?>%</h2><div class="kt-progress"><span style="width:<?= $globalPct ?>%"></span></div></article>
  <article class="kt-card kt-card-pad"><span class="kt-badge">Modules termines</span><h2 style="font-size:44px;margin:18px 0 10px"><?= $doneModules ?>/<?= $totalModules ?></h2><p class="kt-muted">Sur tous les parcours disponibles.</p></article>
  <article class="kt-card kt-card-pad"><span class="kt-badge">Derniere note</span><h2 style="font-size:32px;margin:18px 0 10px"><?= $results ? (int)$results[0]['percentage'] . '/100' : '-' ?></h2><p class="kt-muted"><?= $results ? kt_h($results[0]['exam_title']) : 'Aucun resultat pour le moment.' ?></p></article>
</section>
<section style="margin-top:22px">
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
      <article class="kt-card kt-card-pad">
        <span class="kt-badge"><?= kt_h($course['level']) ?> · <?= kt_h($badge) ?></span>
        <h3><?= kt_h($course['title']) ?></h3>
        <p class="kt-muted"><?= kt_h(mb_substr($course['full_desc'], 0, 150)) ?>...</p>
        <?php if (!kt_course_is_free($course)): ?><p class="kt-gold"><?= kt_h($course['price']) ?> EUR</p><?php endif; ?>
        <div class="kt-progress"><span style="width:<?= $pct ?>%"></span></div>
        <p class="kt-muted"><?= $pct ?>% complete</p>
        <a class="kt-btn primary" href="<?= $ctaHref ?>"><?= kt_h($ctaLabel) ?></a>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<section class="kt-grid cols-2" style="margin-top:22px">
  <article class="kt-card kt-card-pad">
    <h2>Dernieres notes</h2>
    <table class="kt-table"><thead><tr><th>Examen</th><th>Note</th><th>Statut</th></tr></thead><tbody>
      <?php if (!$results): ?><tr><td colspan="3" class="kt-muted">Aucun resultat.</td></tr><?php endif; ?>
      <?php foreach (array_slice($results, 0, 5) as $result): ?><tr><td><?= kt_h($result['exam_title']) ?></td><td><?= (int)$result['percentage'] ?>/100</td><td><?= (int)$result['passed'] ? 'Reussi' : 'Echoue' ?></td></tr><?php endforeach; ?>
    </tbody></table>
  </article>
  <article class="kt-card kt-card-pad">
    <h2>Ressources recentes</h2>
    <div class="kt-grid">
      <?php if (!$contents): ?><p class="kt-muted">Aucune ressource publiee.</p><?php endif; ?>
      <?php foreach (array_slice($contents, 0, 3) as $item): ?><a class="kt-card kt-card-pad" href="<?= kt_h($item['media_url'] ?: '#') ?>"><strong><?= kt_h($item['title']) ?></strong><span class="kt-muted"> <?= kt_h($item['type']) ?></span></a><?php endforeach; ?>
    </div>
  </article>
</section>
<?php kt_page_end(); ?>
