<?php
require_once __DIR__ . '/../src/views/bootstrap.php';
kt_logout_if_requested();
$user = kt_require_role('student');
$courses = Course::getAll();
$courseId = (int)($_GET['courseId'] ?? ($courses[0]['id'] ?? 0));
$course = $courseId ? Course::findById($courseId) : null;

if ($course && !kt_course_is_free($course) && !kt_user_can_access_course((int)$user['id'], $courseId)) {
    header('Location: /student/enroll.php?courseId=' . $courseId);
    exit;
}

$modules = $courseId ? Module::getByCourseId($courseId) : [];
$progressRows = Progress::getByUser($user['id']);
$doneModules = [];
foreach ($progressRows as $row) {
    if ((int)$row['completed'] === 1) {
        $doneModules[(int)$row['module_id']] = true;
    }
}
$activeModuleId = (int)($_GET['moduleId'] ?? 0);
$active = null;
foreach ($modules as $module) {
    if ($activeModuleId && (int)$module['id'] === $activeModuleId) {
        $active = $module;
        break;
    }
    if (!$active && empty($doneModules[(int)$module['id']])) {
        $active = $module;
    }
}
$active = $active ?: ($modules[0] ?? null);

function kt_is_local_media(?string $url): bool {
    return $url && (strpos($url, '/uploads/') === 0 || preg_match('/\.(mp4|webm|mp3|wav|ogg|pdf)(\?|$)/i', $url));
}

kt_page_start('Lecteur de module', 'student', 'course.php', $user);
?>
<script src="/assets/js/tts.js"></script>
<div class="kt-topbar">
  <div>
    <button class="kt-btn kt-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">Menu</button>
    <h1 class="kt-title"><?= kt_h($course['title'] ?? 'Formation') ?></h1>
    <p class="kt-muted">Video, audio, PDF et lecture vocale du texte.</p>
  </div>
</div>
<div class="kt-grid kt-course-layout" style="align-items:start">
  <aside class="kt-card kt-card-pad">
    <h2>Modules</h2>
    <nav class="kt-nav">
      <?php foreach ($modules as $index => $module):
        $previousDone = $index === 0 || !empty($doneModules[(int)$modules[$index - 1]['id']]);
        $locked = !$previousDone;
        $done = !empty($doneModules[(int)$module['id']]);
      ?>
        <a class="<?= $active && (int)$active['id'] === (int)$module['id'] ? 'active' : '' ?>" href="<?= $locked ? '#' : '/student/course.php?courseId=' . (int)$courseId . '&moduleId=' . (int)$module['id'] ?>"><?= $done ? 'OK' : ($locked ? 'LOCK' : 'PLAY') ?> <?= kt_h($module['title']) ?></a>
      <?php endforeach; ?>
    </nav>
  </aside>
  <section class="kt-card kt-card-pad">
    <?php if ($active): ?>
      <?php if (!empty($active['video_url'])): ?>
        <div class="kt-media-video">
          <?php if (kt_is_local_media($active['video_url'])): ?>
            <video controls style="width:100%;border-radius:8px;max-height:420px" src="<?= kt_h($active['video_url']) ?>"></video>
          <?php else: ?>
            <iframe title="Video" src="<?= kt_h(kt_embed_video_url($active['video_url'])) ?>" style="width:100%;aspect-ratio:16/9;border:0;border-radius:8px" allowfullscreen></iframe>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($active['audio_url'])): ?>
        <div class="kt-media-block" style="margin-top:16px">
          <span class="kt-badge">Audio</span>
          <audio controls style="width:100%;margin-top:8px" src="<?= kt_h($active['audio_url']) ?>"></audio>
        </div>
      <?php endif; ?>

      <h2 class="kt-title" style="margin-top:22px"><?= kt_h($active['title']) ?></h2>

      <?php if (!empty($active['content'])): ?>
        <div class="kt-lesson-text" id="lessonText"><?= nl2br(kt_h($active['content'])) ?></div>
        <div style="margin:12px 0;display:flex;gap:10px;flex-wrap:wrap">
          <button type="button" class="kt-btn" onclick="KTTTS.read('lessonText')">Ecouter le texte</button>
          <button type="button" class="kt-btn" onclick="KTTTS.stop()">Arreter la lecture</button>
        </div>
      <?php else: ?>
        <p class="kt-muted">Contenu texte a venir.</p>
      <?php endif; ?>

      <?php if (!empty($active['pdf_url'])): ?>
        <div class="kt-media-block" style="margin-top:16px">
          <span class="kt-badge">PDF</span>
          <p style="margin-top:8px"><a class="kt-btn primary" href="<?= kt_h($active['pdf_url']) ?>" target="_blank" rel="noopener">Ouvrir le document PDF</a></p>
          <?php if (kt_is_local_media($active['pdf_url'])): ?>
            <iframe src="<?= kt_h($active['pdf_url']) ?>" style="width:100%;height:480px;border:1px solid var(--border);border-radius:8px;margin-top:8px"></iframe>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if (empty($active['video_url']) && empty($active['audio_url']) && empty($active['pdf_url']) && empty($active['content'])): ?>
        <p class="kt-muted">Le contenu de ce module sera bientot disponible.</p>
      <?php endif; ?>

      <textarea id="notes" class="kt-textarea" data-module="<?= (int)$active['id'] ?>" placeholder="Notes personnelles" style="margin-top:16px"></textarea>
      <button id="completeBtn" class="kt-btn primary" data-module="<?= (int)$active['id'] ?>" style="margin-top:14px">Marquer comme termine</button>
    <?php else: ?>
      <p class="kt-muted">Aucun module pour cette formation.</p>
    <?php endif; ?>
  </section>
</div>
<script>
  KTTTS.stop();
  const notes = document.getElementById("notes");
  if (notes) {
    const key = "kt_notes_" + notes.dataset.module;
    notes.value = localStorage.getItem(key) || "";
    notes.addEventListener("input", () => localStorage.setItem(key, notes.value));
  }
  const completeBtn = document.getElementById("completeBtn");
  if (completeBtn) {
    completeBtn.addEventListener("click", async () => {
      await KTApi.post("/progress", { module_id: completeBtn.dataset.module, completed: true });
      location.reload();
    });
  }
</script>
<?php kt_page_end(); ?>
