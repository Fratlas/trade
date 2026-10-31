<?php
require_once __DIR__ . '/../src/views/bootstrap.php';
kt_logout_if_requested();
$user = kt_require_role('student');
$exams = kt_all_exams();
$quizId = (int)($_GET['quizId'] ?? 0);
kt_page_start('Examens', 'student', 'quiz.php', $user);
?>
<div class="kt-topbar">
  <div>
    <button class="kt-btn kt-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">Menu</button>
    <h1 class="kt-title">Examens</h1>
    <p class="kt-muted">Une question a la fois, sans retour arriere.</p>
  </div>
  <span id="timer" class="kt-badge">Pret</span>
</div>
<section id="screen" class="kt-card kt-card-pad"></section>
<script>
const screen = document.getElementById("screen");
const exams = <?= json_encode(array_values($exams)) ?>;
const startQuizId = <?= $quizId ?>;
let exam = null, index = 0, answers = {}, remaining = 0, tick = null;

function loadList() {
  screen.innerHTML = `<h2>Choisir un examen</h2><div class="kt-grid cols-3">${exams.map((item) => `<article class="kt-card kt-card-pad"><h3>${KTUi.escapeHtml(item.title)}</h3><p class="kt-muted">Seuil: ${item.passing_score}% - Duree: ${item.duration_minutes} min</p><button class="kt-btn primary" data-id="${item.id}">Commencer</button></article>`).join("") || '<p class="kt-muted">Aucun examen disponible.</p>'}</div>`;
  screen.querySelectorAll("button[data-id]").forEach((button) => button.addEventListener("click", () => start(button.dataset.id)));
}

async function start(id) {
  exam = await KTApi.get(`/exams/${id}`);
  index = 0;
  answers = {};
  remaining = Number(exam.duration_minutes || 30) * 60;
  clearInterval(tick);
  tick = setInterval(() => {
    remaining -= 1;
    document.getElementById("timer").textContent = Math.floor(remaining / 60) + ":" + String(remaining % 60).padStart(2, "0");
    if (remaining <= 0) submit();
  }, 1000);
  renderQuestion();
}

function renderQuestion() {
  const q = exam.questions[index];
  const opts = Array.isArray(q.options) ? q.options : JSON.parse(q.options || "[]");
  screen.innerHTML = `<div class="kt-progress"><span style="width:${KTUi.percent(index + 1, exam.questions.length)}%"></span></div><p class="kt-muted">Question ${index + 1}/${exam.questions.length}</p><h2>${KTUi.escapeHtml(q.question)}</h2><form id="qForm" class="kt-form">${opts.map((opt) => `<label class="kt-card kt-card-pad"><input required type="radio" name="answer" value="${KTUi.escapeHtml(opt)}"> ${KTUi.escapeHtml(opt)}</label>`).join("")}<button class="kt-btn primary">${index + 1 === exam.questions.length ? "Terminer" : "Question suivante"}</button></form>`;
  document.getElementById("qForm").addEventListener("submit", (event) => {
    event.preventDefault();
    answers[q.id] = new FormData(event.currentTarget).get("answer");
    if (index + 1 === exam.questions.length) submit(); else { index += 1; renderQuestion(); }
  });
}

async function submit() {
  clearInterval(tick);
  try {
    const result = await KTApi.post(`/exams/${exam.id}/submit`, { answers });
    screen.innerHTML = `<h2>${result.passed ? "REUSSI" : "ECHOUE"}</h2><div style="font-size:64px;font-weight:900;color:var(--gold)">${result.percentage}/100</div><p class="kt-muted">Score: ${result.score}/${result.max_score}</p><a class="kt-btn primary" href="/student/assistant.php?context=quiz">Revoir les erreurs avec l'assistant IA</a>`;
  } catch (error) { KTUi.toast(error.message); }
}

startQuizId ? start(startQuizId) : loadList();
</script>
<?php kt_page_end(); ?>
