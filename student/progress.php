<?php
require_once __DIR__ . '/../src/views/bootstrap.php';
kt_logout_if_requested();
$user = kt_require_role('student');
$progress = kt_student_progress_detail($user['id']);
$results = kt_exam_results($user['id']);
$done = count(array_filter($progress, fn($row) => (int)$row['completed'] === 1));
$total = count($progress);
kt_page_start('Ma progression', 'student', 'progress.php', $user);
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<div class="kt-topbar"><div><button class="kt-btn kt-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">Menu</button><h1 class="kt-title">Ma progression</h1></div><button id="certBtn" class="kt-btn primary">Telecharger mon certificat</button></div>
<section class="kt-grid cols-2">
  <article class="kt-card kt-card-pad"><h2>Modules</h2><canvas id="pie"></canvas></article>
  <article class="kt-card kt-card-pad"><h2>Notes</h2><canvas id="line"></canvas></article>
</section>
<section class="kt-card kt-card-pad" style="margin-top:22px">
  <h2>Detail par formation</h2>
  <table class="kt-table"><thead><tr><th>Formation</th><th>Module</th><th>Statut</th><th>Completion</th></tr></thead><tbody>
    <?php foreach ($progress as $row): ?><tr><td><?= kt_h($row['course_title']) ?></td><td><?= kt_h($row['module_title']) ?></td><td><?= (int)$row['completed'] ? 'Termine' : 'A faire' ?></td><td><?= kt_h($row['completed_at'] ?? '-') ?></td></tr><?php endforeach; ?>
  </tbody></table>
</section>
<script>
new Chart(document.getElementById("pie"), { type: "doughnut", data: { labels: ["Termines", "Restants"], datasets: [{ data: [<?= $done ?>, <?= max($total - $done, 0) ?>], backgroundColor: ["#c9a84c", "#242424"] }] }, options: { plugins: { legend: { labels: { color: "#fff" } } } } });
new Chart(document.getElementById("line"), { type: "line", data: { labels: <?= json_encode(array_map(fn($r) => $r['exam_title'], $results)) ?>, datasets: [{ label: "Note", data: <?= json_encode(array_map(fn($r) => (int)$r['percentage'], $results)) ?>, borderColor: "#c9a84c" }] }, options: { scales: { x: { ticks: { color: "#a0a0a0" } }, y: { ticks: { color: "#a0a0a0" } } }, plugins: { legend: { labels: { color: "#fff" } } } } });
document.getElementById("certBtn").addEventListener("click", () => {
  const doc = new jspdf.jsPDF({ orientation: "landscape" });
  doc.setFillColor(10, 10, 10); doc.rect(0, 0, 297, 210, "F");
  doc.setDrawColor(201, 168, 76); doc.setLineWidth(2); doc.rect(12, 12, 273, 186);
  doc.setTextColor(201, 168, 76); doc.setFontSize(30); doc.text("Certificat Katsuki Trading", 148, 58, { align: "center" });
  doc.setTextColor(255, 255, 255); doc.setFontSize(18); doc.text("Certifie que <?= kt_h($user['name']) ?> a suivi la formation Katsuki Trading.", 148, 102, { align: "center" });
  doc.save("certificat-katsuki.pdf");
});
</script>
<?php kt_page_end(); ?>
