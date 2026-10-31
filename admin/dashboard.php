<?php
require_once __DIR__ . '/../src/views/bootstrap.php';
kt_logout_if_requested();
$user = kt_require_role('admin');
$students = kt_admin_students();
$courses = Course::getAll();
$progress = kt_admin_progress();
$results = kt_exam_results();
$completed = count(array_filter($progress, fn($row) => (int)$row['completed'] === 1));
$avgCompletion = $progress ? (int)round(($completed / count($progress)) * 100) : 0;
$avgGrade = $results ? (int)round(array_sum(array_map(fn($r) => (int)$r['percentage'], $results)) / count($results)) : 0;
$topStudents = $students;
usort($topStudents, fn($a, $b) => (int)$b['completed_modules'] <=> (int)$a['completed_modules']);
$topStudents = array_slice($topStudents, 0, 5);
$chartLabels = array_map(fn($s) => kt_format_date($s['created_at']), array_slice($students, 0, 8));
$courseLabels = array_map(fn($c) => $c['title'], $courses);
kt_page_start('Dashboard admin', 'admin', 'dashboard.php', $user);
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="kt-topbar">
  <div>
    <button class="kt-btn kt-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">Menu</button>
    <h1 class="kt-title">Dashboard admin</h1>
    <p class="kt-muted">Pilotage des formations et des etudiants.</p>
  </div>
</div>
<section class="kt-grid cols-4">
  <article class="kt-card kt-card-pad"><span class="kt-badge">Etudiants inscrits</span><h2 style="font-size:42px"><?= count($students) ?></h2></article>
  <article class="kt-card kt-card-pad"><span class="kt-badge">Formations actives</span><h2 style="font-size:42px"><?= count($courses) ?></h2></article>
  <article class="kt-card kt-card-pad"><span class="kt-badge">Completion moyenne</span><h2 style="font-size:42px"><?= $avgCompletion ?>%</h2></article>
  <article class="kt-card kt-card-pad"><span class="kt-badge">Note moyenne</span><h2 style="font-size:42px"><?= $avgGrade ?>/100</h2></article>
</section>
<section class="kt-grid cols-2" style="margin-top:22px">
  <article class="kt-card kt-card-pad"><h2>Inscriptions</h2><canvas id="bar"></canvas></article>
  <article class="kt-card kt-card-pad"><h2>Taux de completion</h2><canvas id="line"></canvas></article>
</section>
<section class="kt-card kt-card-pad" style="margin-top:22px">
  <h2>Top etudiants</h2>
  <table class="kt-table"><tbody>
    <?php foreach ($topStudents as $student): ?>
      <tr><td><?= kt_h($student['name']) ?></td><td><?= (int)$student['completed_modules'] ?> modules</td><td><?= (int)$student['exams_passed'] ?> examens reussis</td></tr>
    <?php endforeach; ?>
    <?php if (!$topStudents): ?><tr><td colspan="3" class="kt-muted">Aucun etudiant.</td></tr><?php endif; ?>
  </tbody></table>
</section>
<script>
new Chart(document.getElementById("bar"), { type: "bar", data: { labels: <?= json_encode($chartLabels) ?>, datasets: [{ label: "Inscriptions", data: <?= json_encode(array_fill(0, count($chartLabels), 1)) ?>, backgroundColor: "#c9a84c" }] }, options: { plugins: { legend: { labels: { color: "#fff" } } }, scales: { x: { ticks: { color: "#a0a0a0" } }, y: { ticks: { color: "#a0a0a0" } } } } });
new Chart(document.getElementById("line"), { type: "line", data: { labels: <?= json_encode($courseLabels) ?>, datasets: [{ label: "Completion", data: <?= json_encode(array_fill(0, count($courses), $avgCompletion)) ?>, borderColor: "#c9a84c" }] }, options: { plugins: { legend: { labels: { color: "#fff" } } }, scales: { x: { ticks: { color: "#a0a0a0" } }, y: { ticks: { color: "#a0a0a0" } } } } });
</script>
<?php kt_page_end(); ?>
