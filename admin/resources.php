<?php
require_once __DIR__ . '/../src/views/bootstrap.php';
kt_logout_if_requested();
$user = kt_require_role('admin');
$saved = isset($_GET['saved']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'article';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $mediaUrl = trim($_POST['media_url'] ?? '');
    if ($title !== '') {
        Content::create($type, $title, $description, $mediaUrl, null, 0, null, $user['id'], 1);
        header('Location: /admin/resources.php?saved=1');
        exit;
    }
}
$items = Content::getAll();
kt_page_start('Ressources', 'admin', 'resources.php', $user);
?>
<div class="kt-topbar">
  <div>
    <button class="kt-btn kt-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">Menu</button>
    <h1 class="kt-title">Ressources</h1>
  </div>
</div>
<?php if ($saved): ?><p class="kt-badge" style="margin-bottom:16px">Ressource publiee.</p><?php endif; ?>
<section class="kt-grid cols-2">
  <form method="post" class="kt-card kt-card-pad kt-form">
    <h2>Uploader une ressource</h2>
    <select class="kt-select" name="type">
      <option value="book">PDF / Livre</option>
      <option value="video">Video</option>
      <option value="article">Article</option>
      <option value="photo">Image</option>
      <option value="webinar">Webinar</option>
    </select>
    <input class="kt-input" name="title" placeholder="Titre" required>
    <textarea class="kt-textarea" name="description" placeholder="Description"></textarea>
    <input class="kt-input" name="media_url" placeholder="URL du fichier ou lien">
    <button class="kt-btn primary">Publier</button>
  </form>
  <section class="kt-card kt-card-pad">
    <h2>Bibliotheque</h2>
    <div class="kt-grid">
      <?php foreach ($items as $item): ?>
        <article class="kt-card kt-card-pad">
          <span class="kt-badge"><?= kt_h($item['type']) ?></span>
          <h3><?= kt_h($item['title']) ?></h3>
          <p class="kt-muted"><?= kt_h($item['description'] ?? '') ?></p>
        </article>
      <?php endforeach; ?>
      <?php if (!$items): ?><p class="kt-muted">Aucune ressource.</p><?php endif; ?>
    </div>
  </section>
</section>
<?php kt_page_end(); ?>
