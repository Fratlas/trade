<?php
require_once __DIR__ . '/../src/views/bootstrap.php';
kt_logout_if_requested();
$user = kt_require_role('admin');
$prefillStudent = (int)($_GET['studentId'] ?? 0);
kt_page_start('Messagerie etudiants', 'admin', 'messages.php', $user);
?>
<div class="kt-topbar">
  <div>
    <button class="kt-btn kt-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">Menu</button>
    <h1 class="kt-title">Messagerie</h1>
    <p class="kt-muted">Conversations avec les etudiants.</p>
  </div>
</div>
<div class="kt-inbox">
  <aside class="kt-card kt-card-pad">
    <h2 style="margin-top:0">Etudiants</h2>
    <div id="inbox" class="kt-inbox-list"></div>
  </aside>
  <section class="kt-card kt-msg-panel">
    <header id="chatHeader" class="kt-card-pad" style="border-bottom:1px solid var(--border)">
      <span class="kt-muted">Selectionnez un etudiant</span>
    </header>
    <div id="feed" class="kt-msg-feed"></div>
    <form id="compose" class="kt-msg-compose" style="display:none">
      <textarea id="body" class="kt-textarea" rows="3" placeholder="Votre reponse..."></textarea>
      <div id="preview"></div>
      <div class="kt-msg-compose-actions">
        <label class="kt-btn kt-file-btn">Ajouter une image<input type="file" id="image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
        <button class="kt-btn primary" type="submit">Envoyer</button>
      </div>
    </form>
  </section>
</div>
<script>
  const inbox = document.getElementById("inbox");
  const feed = document.getElementById("feed");
  const header = document.getElementById("chatHeader");
  const form = document.getElementById("compose");
  const bodyInput = document.getElementById("body");
  const imageInput = document.getElementById("image");
  const preview = document.getElementById("preview");
  const adminId = <?= (int)$user['id'] ?>;
  let activeStudentId = <?= $prefillStudent ?>;
  let threadId = null;

  KTMessages.bindPreview(imageInput, preview);

  function renderInbox(threads, students) {
    const items = [];
    (threads || []).forEach((t) => {
      const previewText = t.last_body || (t.last_image ? "[Image]" : "Nouvelle conversation");
      const unread = Number(t.unread) > 0 ? `<span class="kt-unread">${t.unread}</span>` : "";
      items.push(`<button type="button" class="kt-inbox-item ${Number(activeStudentId) === Number(t.student_id) ? "active" : ""}" data-id="${t.student_id}"><strong>${KTMessages.escapeHtml(t.student_name)} ${unread}</strong><small>${KTMessages.escapeHtml(previewText)}</small></button>`);
    });
    (students || []).forEach((s) => {
      items.push(`<button type="button" class="kt-inbox-item ${Number(activeStudentId) === Number(s.id) ? "active" : ""}" data-id="${s.id}"><strong>${KTMessages.escapeHtml(s.name)}</strong><small>Nouveau contact</small></button>`);
    });
    inbox.innerHTML = items.join("") || '<p class="kt-muted">Aucun etudiant.</p>';
    inbox.querySelectorAll("[data-id]").forEach((btn) => btn.addEventListener("click", () => openChat(btn.dataset.id)));
  }

  async function loadInbox() {
    const data = await KTApi.get("/messages");
    renderInbox(data.threads, data.students);
    if (activeStudentId) await openChat(activeStudentId, true);
  }

  async function openChat(studentId, silent) {
    activeStudentId = Number(studentId);
    const data = await KTApi.get("/messages/" + studentId);
    threadId = data.thread_id;
    header.innerHTML = `<strong>${KTMessages.escapeHtml(data.student.name)}</strong><span class="kt-muted"> — ${KTMessages.escapeHtml(data.student.email)}</span>`;
    form.style.display = "grid";
    KTMessages.renderMessages(feed, data.messages || [], adminId);
    if (!silent) await loadInbox();
    inbox.querySelectorAll("[data-id]").forEach((btn) => btn.classList.toggle("active", Number(btn.dataset.id) === activeStudentId));
  }

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (!activeStudentId) return;
    const text = bodyInput.value.trim();
    const file = imageInput.files && imageInput.files[0];
    if (!text && !file) return;
    const fd = new FormData();
    fd.append("student_id", String(activeStudentId));
    if (text) fd.append("body", text);
    if (file) fd.append("image", file);
    await KTApi.postForm("/messages", fd);
    bodyInput.value = "";
    imageInput.value = "";
    preview.innerHTML = "";
    await openChat(activeStudentId, true);
    await loadInbox();
  });

  loadInbox().catch((error) => KTUi.toast(error.message));
  setInterval(() => {
    if (!activeStudentId) {
      loadInbox().catch(() => {});
      return;
    }
    openChat(activeStudentId, true).catch(() => {});
  }, 5000);
</script>
<?php kt_page_end(); ?>
