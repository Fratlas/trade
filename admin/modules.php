<?php
require_once __DIR__ . '/../src/views/bootstrap.php';
require_once __DIR__ . '/../src/utils/FileUpload.php';
kt_logout_if_requested();
$user = kt_require_role('admin');

$courses = Course::getAll();
$courseId = (int)($_GET['courseId'] ?? ($courses[0]['id'] ?? 0));
$course = $courseId ? Course::findById($courseId) : null;
$saved = isset($_GET['saved']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $moduleId = (int)($_POST['module_id'] ?? 0);
    $module = $moduleId ? Module::findById($moduleId) : null;
    if ($module) {
        $data = [
            'title' => trim($_POST['title'] ?? $module['title']),
            'order_index' => (int)($_POST['order_index'] ?? $module['order_index']),
            'video_url' => trim($_POST['video_url'] ?? '') ?: ($module['video_url'] ?? null),
            'audio_url' => trim($_POST['audio_url'] ?? '') ?: ($module['audio_url'] ?? null),
            'pdf_url' => trim($_POST['pdf_url'] ?? '') ?: ($module['pdf_url'] ?? null),
            'content' => trim($_POST['content'] ?? '') ?: ($module['content'] ?? null),
        ];
        try {
            if (!empty($_FILES['video_file']['name'])) {
                $data['video_url'] = FileUpload::save($_FILES['video_file'], 'modules/video', FileUpload::videoTypes());
            }
            if (!empty($_FILES['audio_file']['name'])) {
                $data['audio_url'] = FileUpload::save($_FILES['audio_file'], 'modules/audio', FileUpload::audioTypes());
            }
            if (!empty($_FILES['pdf_file']['name'])) {
                $data['pdf_url'] = FileUpload::save($_FILES['pdf_file'], 'modules/pdf', FileUpload::pdfTypes());
            }
            Module::update($moduleId, $data);
            header('Location: /admin/modules.php?courseId=' . (int)$module['course_id'] . '&saved=1');
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

if (isset($_GET['add']) && $courseId) {
    $modules = Module::getByCourseId($courseId);
    $next = count($modules) + 1;
    Module::create($courseId, 'Nouveau module ' . $next, $next);
    header('Location: /admin/modules.php?courseId=' . $courseId);
    exit;
}

$modules = $courseId ? Module::getByCourseId($courseId) : [];
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? Module::findById($editId) : null;
kt_page_start('Modules de formation', 'admin', 'modules.php', $user);
?>
<div class="kt-topbar">
  <div>
    <button class="kt-btn kt-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">Menu</button>
    <h1 class="kt-title">Contenu des modules</h1>
    <p class="kt-muted">Video, audio, PDF et texte pour chaque lecon.</p>
  </div>
  <?php if ($courseId): ?><a class="kt-btn primary" href="?courseId=<?= $courseId ?>&add=1">Ajouter un module</a><?php endif; ?>
</div>
<form method="get" style="margin-bottom:16px">
  <select class="kt-select" name="courseId" onchange="this.form.submit()">
    <?php foreach ($courses as $c): ?>
      <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $courseId ? 'selected' : '' ?>><?= kt_h($c['title']) ?></option>
    <?php endforeach; ?>
  </select>
</form>
<?php if (!empty($error)): ?><p class="kt-card kt-card-pad" style="border-color:rgba(239,68,68,.4);color:#fecaca;margin-bottom:16px"><?= kt_h($error) ?></p><?php endif; ?>
<?php if ($saved): ?><p class="kt-badge" style="margin-bottom:16px">Module enregistre.</p><?php endif; ?>
<div class="kt-grid cols-2">
  <section class="kt-card kt-card-pad">
    <h2>Modules — <?= kt_h($course['title'] ?? '') ?></h2>
    <table class="kt-table">
      <thead><tr><th>#</th><th>Titre</th><th>Medias</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($modules as $m): ?>
          <tr>
            <td><?= (int)$m['order_index'] ?></td>
            <td><?= kt_h($m['title']) ?></td>
            <td class="kt-muted"><?= !empty($m['video_url']) ? 'Video ' : '' ?><?= !empty($m['audio_url']) ? 'Audio ' : '' ?><?= !empty($m['pdf_url']) ? 'PDF ' : '' ?><?= !empty($m['content']) ? 'Texte' : '' ?></td>
            <td><a class="kt-btn" href="?courseId=<?= $courseId ?>&edit=<?= (int)$m['id'] ?>">Modifier</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$modules): ?><tr><td colspan="4" class="kt-muted">Aucun module.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </section>
  <section class="kt-card kt-card-pad">
    <?php if ($edit): ?>
      <h2>Modifier le module</h2>
      <form method="post" enctype="multipart/form-data" class="kt-form">
        <input type="hidden" name="module_id" value="<?= (int)$edit['id'] ?>">
        <input class="kt-input" name="title" value="<?= kt_h($edit['title']) ?>" required>
        <input class="kt-input" name="order_index" type="number" value="<?= (int)$edit['order_index'] ?>" min="1">
        <label class="kt-muted">Video (URL YouTube/Vimeo ou fichier MP4)</label>
        <input class="kt-input" name="video_url" value="<?= kt_h($edit['video_url'] ?? '') ?>" placeholder="https://...">
        <input type="file" name="video_file" accept="video/mp4,video/webm">
        <label class="kt-muted">Audio (URL ou fichier MP3/WAV)</label>
        <input class="kt-input" name="audio_url" value="<?= kt_h($edit['audio_url'] ?? '') ?>">
        <input type="file" name="audio_file" accept="audio/*">
        <label class="kt-muted">PDF (URL ou fichier)</label>
        <input class="kt-input" name="pdf_url" value="<?= kt_h($edit['pdf_url'] ?? '') ?>">
        <input type="file" name="pdf_file" accept="application/pdf">
        <label class="kt-muted">Texte de la lecon</label>
        <textarea class="kt-textarea" name="content" rows="8"><?= kt_h($edit['content'] ?? '') ?></textarea>
        <button class="kt-btn primary" type="submit">Enregistrer</button>
      </form>
    <?php else: ?>
      <p class="kt-muted">Selectionnez un module a modifier pour ajouter video, audio, PDF et texte.</p>
    <?php endif; ?>
  </section>
</div>
<?php kt_page_end(); ?>
