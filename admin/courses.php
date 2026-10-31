<?php
require_once __DIR__ . '/../src/views/bootstrap.php';
kt_logout_if_requested();
$user = kt_require_role('admin');
$courses = Course::getAll();
kt_page_start('Formations', 'admin', 'courses.php', $user);
?>
<div class="kt-topbar">
  <div>
    <button class="kt-btn kt-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">Menu</button>
    <h1 class="kt-title">Formations</h1>
    <p class="kt-muted">Lecture des formations publiees dans la base.</p>
  </div>
</div>
<section class="kt-grid cols-3">
  <?php foreach ($courses as $course): ?>
    <article class="kt-card kt-card-pad">
      <span class="kt-badge"><?= kt_h($course['level']) ?></span>
      <h2><?= kt_h($course['title']) ?></h2>
      <p class="kt-muted"><?= kt_h($course['full_desc'] ?? '') ?></p>
      <p><?= (int)$course['modules_count'] ?> modules - <?= kt_h($course['price']) ?> EUR</p>
      <a class="kt-btn primary" href="/student/course.php?courseId=<?= (int)$course['id'] ?>">Previsualiser</a>
    </article>
  <?php endforeach; ?>
  <?php if (!$courses): ?><p class="kt-muted">Aucune formation.</p><?php endif; ?>
</section>
<?php kt_page_end(); ?>
