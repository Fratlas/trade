<?php
require_once __DIR__ . '/../src/views/bootstrap.php';
kt_logout_if_requested();
$user = kt_require_role('admin');
$exams = kt_all_exams();
kt_page_start('Examens', 'admin', 'quizzes.php', $user);
?>
<div class="kt-topbar">
  <div>
    <button class="kt-btn kt-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">Menu</button>
    <h1 class="kt-title">Examens</h1>
  </div>
  <a class="kt-btn primary" href="/admin/assistant.php">Assistant IA</a>
</div>
<section class="kt-grid" style="grid-template-columns:1fr 360px">
  <article class="kt-card kt-card-pad">
    <table class="kt-table">
      <thead><tr><th>Titre</th><th>Formation</th><th>Seuil</th><th>Duree</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($exams as $exam): ?>
          <tr>
            <td><?= kt_h($exam['title']) ?></td>
            <td><?= kt_h($exam['course_title']) ?></td>
            <td><?= (int)$exam['passing_score'] ?>%</td>
            <td><?= (int)$exam['duration_minutes'] ?> min</td>
            <td><a class="kt-btn" href="/student/quiz.php?quizId=<?= (int)$exam['id'] ?>">Tester</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$exams): ?><tr><td colspan="5" class="kt-muted">Aucun examen.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </article>
  <aside class="kt-card kt-card-pad kt-form">
    <h2>Generateur IA</h2>
    <input id="topic" class="kt-input" placeholder="Sujet">
    <button id="gen" type="button" class="kt-btn primary">Generer 5 questions</button>
    <pre id="ai" class="kt-muted" style="white-space:pre-wrap"></pre>
  </aside>
</section>
<script>
document.getElementById("gen").addEventListener("click", async () => {
  const topic = document.getElementById("topic").value || "trading";
  try {
    const q = await KTAI.askQuestions(topic, "admin", 5);
    document.getElementById("ai").textContent = JSON.stringify(q, null, 2);
  } catch (error) {
    document.getElementById("ai").textContent = error.message;
  }
});
</script>
<?php kt_page_end(); ?>
