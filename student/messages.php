<?php
require_once __DIR__ . '/../src/views/bootstrap.php';
kt_logout_if_requested();
$user = kt_require_role('student');
kt_page_start('Messagerie', 'student', 'messages.php', $user);
?>
<div class="kt-topbar">
  <div>
    <button class="kt-btn kt-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">Menu</button>
    <h1 class="kt-title">Messagerie</h1>
    <p class="kt-muted">Echangez avec l equipe Katsuki (texte et images).</p>
  </div>
</div>
<section class="kt-card kt-msg-panel">
  <header class="kt-card-pad" style="border-bottom:1px solid var(--border)">
    <strong>Equipe Katsuki</strong>
    <span class="kt-muted"> — Support formation</span>
  </header>
  <div id="feed" class="kt-msg-feed"></div>
  <form id="compose" class="kt-msg-compose">
    <textarea id="body" class="kt-textarea" rows="3" placeholder="Votre message..."></textarea>
    <div id="preview"></div>
    <div class="kt-msg-compose-actions">
      <label class="kt-btn kt-file-btn">Ajouter une image<input type="file" id="image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
      <button class="kt-btn primary" type="submit">Envoyer</button>
    </div>
  </form>
</section>
<script>
  const feed = document.getElementById("feed");
  const form = document.getElementById("compose");
  const bodyInput = document.getElementById("body");
  const imageInput = document.getElementById("image");
  const preview = document.getElementById("preview");
  const userId = <?= (int)$user['id'] ?>;
  let threadId = null;

  KTMessages.bindPreview(imageInput, preview);

  async function load() {
    const data = await KTApi.get("/messages");
    threadId = data.thread_id;
    KTMessages.renderMessages(feed, data.messages || [], userId);
  }

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const text = bodyInput.value.trim();
    const file = imageInput.files && imageInput.files[0];
    if (!text && !file) return;
    const fd = new FormData();
    if (text) fd.append("body", text);
    if (file) fd.append("image", file);
    await KTApi.postForm("/messages", fd);
    bodyInput.value = "";
    imageInput.value = "";
    preview.innerHTML = "";
    await load();
  });

  load().catch((error) => KTUi.toast(error.message));
  setInterval(() => load().catch(() => {}), 5000);
</script>
<?php kt_page_end(); ?>
