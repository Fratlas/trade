<?php
require_once __DIR__ . '/../src/views/bootstrap.php';
kt_logout_if_requested();
$user = kt_require_role('student');
$exams = kt_all_exams();
$quizId = (int)($_GET['quizId'] ?? 0);

// Meilleur score par examen pour l'élève connecté.
$best = [];
foreach (kt_exam_results($user['id']) as $r) {
    $id = (int)$r['exam_id'];
    $best[$id] = max($best[$id] ?? 0, (int)$r['percentage']);
}
foreach ($exams as &$exam) {
    $exam['best'] = $best[(int)$exam['id']] ?? null;
}
unset($exam);

kt_page_start('Examens', 'student', 'quiz.php', $user);
?>
<link rel="stylesheet" href="/assets/css/lesson.css">
<div class="kt-topbar">
  <div>
    <span class="kt-eyebrow">Validation</span>
    <h1 class="kt-title">Examens</h1>
    <p class="kt-muted">Une question à la fois, sans retour en arrière. Réponds avec la souris ou les touches 1 à 4.</p>
  </div>
</div>
<section id="screen"></section>
<script>
(function () {
  const screen = document.getElementById("screen");
  const exams = <?= json_encode(array_values($exams), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
  const startQuizId = <?= $quizId ?>;
  const esc = KTUi.escapeHtml;
  let exam = null, index = 0, answers = {}, remaining = 0, total = 0, tick = null, choice = null, busy = false;

  function ring(pct, size, cls) {
    return `<div class="kt-ring ${cls || ""}" style="--size:${size}px"><svg viewBox="0 0 64 64"><circle class="track" cx="32" cy="32" r="27"/><circle class="bar" cx="32" cy="32" r="27" stroke-dasharray="169.6" stroke-dashoffset="${169.6 * (1 - pct / 100)}"/></svg><b>${pct}%</b></div>`;
  }

  function loadList() {
    screen.innerHTML = exams.length ? `<div class="kt-exam-list">${exams.map((item) => `
      <article class="kt-card kt-card-pad kt-exam-card">
        <span class="kt-eyebrow" style="margin:0">${esc(item.course_title)}</span>
        <h3>${esc(item.title)}</h3>
        <div class="kt-exam-facts">
          <span class="kt-badge neutral">${item.duration_minutes} min</span>
          <span class="kt-badge neutral">Seuil ${item.passing_score} %</span>
          ${item.best !== null ? `<span class="kt-badge ${item.best >= item.passing_score ? "success" : "danger"}">Meilleur : ${item.best} %</span>` : ""}
        </div>
        ${item.instructions ? `<p class="kt-muted" style="margin:0">${esc(item.instructions)}</p>` : ""}
        <button class="kt-btn primary" data-id="${item.id}">${item.best !== null ? "Repasser l'examen" : "Commencer"}</button>
      </article>`).join("")}</div>`
      : '<div class="kt-empty kt-card"><strong>Aucun examen disponible</strong><p class="kt-muted">Les examens apparaîtront ici dès leur publication.</p></div>';
    screen.querySelectorAll("button[data-id]").forEach((b) => b.addEventListener("click", () => intro(b.dataset.id)));
  }

  async function intro(id) {
    try { exam = await KTApi.get(`/exams/${id}`); } catch (e) { KTUi.toast(e.message); return; }
    const n = (exam.questions || []).length;
    screen.innerHTML = `<div class="kt-exam-stage"><div class="kt-card kt-card-pad">
      <span class="kt-eyebrow">${esc(exam.course_title || "")}</span>
      <h2 style="font-size:26px">${esc(exam.title)}</h2>
      <div class="kt-kpis" style="margin:16px 0">
        <div class="kt-kpi"><span>Questions</span><strong class="kt-num">${n}</strong></div>
        <div class="kt-kpi"><span>Durée</span><strong class="kt-num">${exam.duration_minutes} min</strong></div>
        <div class="kt-kpi accent"><span>Pour réussir</span><strong class="kt-num">${exam.passing_score} %</strong></div>
      </div>
      ${exam.instructions ? `<p class="kt-muted">${esc(exam.instructions)}</p>` : ""}
      <p class="kt-small kt-muted">Le chronomètre démarre dès que tu cliques. Une réponse validée ne peut plus être modifiée.</p>
      <div class="kt-finish-actions" style="margin-top:16px"><button class="kt-btn" data-back>Retour</button><button class="kt-btn primary" data-go ${n ? "" : "disabled"}>Démarrer l'examen</button></div>
    </div></div>`;
    screen.querySelector("[data-back]").onclick = loadList;
    screen.querySelector("[data-go]").onclick = start;
  }

  function start() {
    index = 0; answers = {};
    total = remaining = Number(exam.duration_minutes || 30) * 60;
    clearInterval(tick);
    tick = setInterval(() => {
      remaining -= 1;
      const t = document.getElementById("timer");
      if (t) {
        t.textContent = "⏱ " + Math.floor(remaining / 60) + ":" + String(remaining % 60).padStart(2, "0");
        t.classList.toggle("warn", remaining <= 60);
      }
      if (remaining <= 0) submit();
    }, 1000);
    addEventListener("beforeunload", guard);
    renderQuestion();
  }
  const guard = (e) => { e.preventDefault(); e.returnValue = ""; };

  function renderQuestion() {
    const q = exam.questions[index];
    const opts = Array.isArray(q.options) ? q.options : JSON.parse(q.options || "[]");
    const n = exam.questions.length;
    choice = null;
    screen.innerHTML = `<div class="kt-exam-stage">
      <div class="kt-exam-top">
        <span class="kt-small kt-muted kt-num">${index + 1} / ${n}</span>
        <div class="kt-progress"><span style="width:${index / n * 100}%"></span></div>
        <span id="timer" class="kt-timer">⏱ ${Math.floor(remaining / 60)}:${String(remaining % 60).padStart(2, "0")}</span>
      </div>
      <div class="kt-card kt-card-pad">
        <p class="kt-exam-q">${esc(q.question)}</p>
        <div class="kt-options">${opts.map((o, i) => `<button type="button" class="kt-option" data-value="${esc(o)}"><kbd>${i + 1}</kbd>${esc(o)}</button>`).join("")}</div>
        <div class="kt-exam-foot">
          <span class="kt-small kt-muted">${q.points ? q.points + " point" + (q.points > 1 ? "s" : "") : ""}</span>
          <button type="button" class="kt-btn primary" id="nextBtn" disabled>${index + 1 === n ? "Terminer l'examen" : "Valider et continuer"}</button>
        </div>
      </div></div>`;
    screen.querySelectorAll(".kt-option").forEach((b) => b.addEventListener("click", () => pick(b)));
    document.getElementById("nextBtn").addEventListener("click", next);
  }

  function pick(b) {
    screen.querySelectorAll(".kt-option").forEach((x) => x.classList.toggle("selected", x === b));
    choice = b.dataset.value;
    document.getElementById("nextBtn").disabled = false;
  }

  function next() {
    if (choice === null) return;
    answers[exam.questions[index].id] = choice;
    if (index + 1 === exam.questions.length) submit(); else { index += 1; renderQuestion(); }
  }

  document.addEventListener("keydown", (e) => {
    if (!exam || !screen.querySelector(".kt-option") || e.target.closest("input, textarea")) return;
    if (/^[1-9]$/.test(e.key)) { const b = screen.querySelectorAll(".kt-option")[+e.key - 1]; if (b) pick(b); }
    if (e.key === "Enter" && choice !== null) { e.preventDefault(); next(); }
  });

  async function submit() {
    if (busy) return;
    busy = true;
    clearInterval(tick);
    removeEventListener("beforeunload", guard);
    if (!Object.keys(answers).length) { busy = false; KTUi.toast("Temps écoulé sans réponse."); loadList(); return; }
    screen.innerHTML = '<div class="kt-exam-stage"><div class="kt-card kt-card-pad kt-exam-result"><p class="kt-muted">Correction en cours…</p></div></div>';
    try {
      const r = await KTApi.post(`/exams/${exam.id}/submit`, { answers });
      const used = total - Math.max(remaining, 0);
      screen.innerHTML = `<div class="kt-exam-stage"><div class="kt-card kt-exam-result ${r.passed ? "kt-quiz-result pass" : "kt-quiz-result fail"}" style="display:block">
        ${ring(r.percentage, 120)}
        <h2>${r.passed ? "Examen réussi !" : "Pas encore validé"}</h2>
        <p class="kt-muted">Score : <b class="kt-num">${r.score} / ${r.max_score}</b> · seuil ${r.passing_score} % · temps ${Math.floor(used / 60)} min ${used % 60} s</p>
        <p class="kt-muted">${r.passed ? "Ta progression est enregistrée. Tu peux télécharger ton certificat dans « Ma progression »." : "Revois les modules concernés, puis retente ta chance."}</p>
        <div class="kt-finish-actions" style="justify-content:center;margin-top:18px">
          <a class="kt-btn" href="/student/assistant.php?context=quiz">Revoir mes erreurs avec l'assistant IA</a>
          ${r.passed ? '<a class="kt-btn primary" href="/student/progress.php">Voir ma progression</a>' : `<button class="kt-btn primary" data-retry>Réessayer</button>`}
        </div></div></div>`;
      const retry = screen.querySelector("[data-retry]");
      if (retry) retry.onclick = () => intro(exam.id);
    } catch (error) { KTUi.toast(error.message); loadList(); }
    busy = false;
  }

  startQuizId ? intro(startQuizId) : loadList();
})();
</script>
<?php kt_page_end(); ?>
