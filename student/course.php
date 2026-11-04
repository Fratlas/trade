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

// Un module est déverrouillé quand le précédent est terminé.
$items = [];
$hasChapters = false;
foreach ($modules as $index => $module) {
    $id = (int)$module['id'];
    $title = (string)$module['title'];
    $chapter = '';
    $short = $title;
    if (preg_match('/^CH\s*(\d+)\s*[—–-]\s*(.+)$/u', $title, $m)) {
        $chapter = 'Chapitre ' . $m[1];
        $short = $m[2];
        $hasChapters = true;
    }
    $items[] = [
        'id' => $id,
        'index' => $index,
        'title' => $title,
        'short' => $short,
        'chapter' => $chapter,
        'done' => !empty($doneModules[$id]),
        'locked' => $index > 0 && empty($doneModules[(int)$modules[$index - 1]['id']]),
    ];
}
foreach ($items as &$item) {
    if ($hasChapters && $item['chapter'] === '') {
        $item['chapter'] = 'Pratique et progression';
    }
}
unset($item);

$activeModuleId = (int)($_GET['moduleId'] ?? 0);
$activeIndex = null;
foreach ($items as $item) {
    if ($activeModuleId && $item['id'] === $activeModuleId && !$item['locked']) {
        $activeIndex = $item['index'];
        break;
    }
}
if ($activeIndex === null) {
    foreach ($items as $item) {
        if (!$item['done'] && !$item['locked']) {
            $activeIndex = $item['index'];
            break;
        }
    }
}
if ($activeIndex === null && $items) {
    $activeIndex = 0;
}
$active = $activeIndex !== null ? $modules[$activeIndex] : null;
$activeItem = $activeIndex !== null ? $items[$activeIndex] : null;
$prevItem = $activeIndex !== null && $activeIndex > 0 ? $items[$activeIndex - 1] : null;
$nextItem = $activeIndex !== null && $activeIndex + 1 < count($items) ? $items[$activeIndex + 1] : null;
$doneCount = count(array_filter($items, fn($i) => $i['done']));
$coursePct = kt_percent($doneCount, count($items));

function kt_is_local_media(?string $url): bool {
    return $url && (strpos($url, '/uploads/') === 0 || preg_match('/\.(mp4|webm|mp3|wav|ogg|pdf)(\?|$)/i', $url));
}

function kt_module_url(int $courseId, int $moduleId): string {
    return '/student/course.php?courseId=' . $courseId . '&moduleId=' . $moduleId;
}

$lessonData = $active ? [
    'courseId' => $courseId,
    'moduleId' => (int)$active['id'],
    'title' => $active['title'],
    'content' => (string)($active['content'] ?? ''),
    'done' => $activeItem['done'],
    'index' => $activeIndex,
    'total' => count($items),
    'prevUrl' => $prevItem ? kt_module_url($courseId, $prevItem['id']) : null,
    'nextUrl' => $nextItem ? kt_module_url($courseId, $nextItem['id']) : null,
    'nextTitle' => $nextItem['short'] ?? null,
    'nextLocked' => $nextItem ? ($nextItem['locked'] && !$activeItem['done']) : false,
] : null;

kt_page_start('Formation', 'student', 'course.php', $user);
?>
<link rel="stylesheet" href="/assets/css/lesson.css">
<div class="kt-read-progress" aria-hidden="true"><span id="readProgress"></span></div>

<div class="kt-player">
  <aside class="kt-modules kt-card" id="moduleDrawer" aria-label="Modules de la formation">
    <div class="kt-modules-head">
      <div class="kt-ring" style="--size:56px" data-ring="<?= $coursePct ?>">
        <svg viewBox="0 0 64 64"><circle class="track" cx="32" cy="32" r="27"/><circle class="bar" cx="32" cy="32" r="27" stroke-dasharray="169.6" stroke-dashoffset="169.6"/></svg>
        <b><?= $coursePct ?>%</b>
      </div>
      <div style="min-width:0">
        <?php if (count($courses) > 1): ?>
          <select class="kt-course-switch" aria-label="Changer de formation" onchange="location.href='/student/course.php?courseId='+this.value">
            <?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $courseId ? 'selected' : '' ?>><?= kt_h($c['title']) ?></option><?php endforeach; ?>
          </select>
        <?php else: ?>
          <strong><?= kt_h($course['title'] ?? 'Formation') ?></strong>
        <?php endif; ?>
        <span class="kt-muted kt-small"><?= $doneCount ?> / <?= count($items) ?> modules terminés</span>
      </div>
    </div>
    <label class="kt-module-search">
      <svg class="kt-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" id="moduleSearch" placeholder="Rechercher un module…" autocomplete="off">
    </label>
    <nav class="kt-module-list" id="moduleList">
      <?php $currentChapter = null; foreach ($items as $item):
        if ($item['chapter'] !== $currentChapter):
          if ($currentChapter !== null) echo '</div>';
          $currentChapter = $item['chapter']; ?>
          <div class="kt-chapter">
          <?php if ($currentChapter !== ''): ?><p class="kt-chapter-title"><?= kt_h($currentChapter) ?></p><?php endif; ?>
        <?php endif;
        $isActive = $activeItem && $item['id'] === $activeItem['id'];
        $state = $item['done'] ? 'done' : ($item['locked'] ? 'locked' : 'open');
      ?>
        <a class="kt-module <?= $state ?> <?= $isActive ? 'active' : '' ?>"
           data-title="<?= kt_h(mb_strtolower($item['title'])) ?>"
           <?= $item['locked'] ? 'aria-disabled="true" tabindex="-1" title="Termine le module précédent pour déverrouiller"' : 'href="' . kt_h(kt_module_url($courseId, $item['id'])) . '"' ?>
           <?= $isActive ? 'aria-current="page"' : '' ?>>
          <span class="kt-module-dot" aria-hidden="true">
            <?php if ($item['done']): ?><svg viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
            <?php elseif ($item['locked']): ?><svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
            <?php else: ?><?= $item['index'] + 1 ?><?php endif; ?>
          </span>
          <span class="kt-module-text"><?= kt_h($item['short']) ?></span>
        </a>
      <?php endforeach; if ($currentChapter !== null) echo '</div>'; ?>
    </nav>
  </aside>

  <section class="kt-lesson" id="lesson">
    <?php if ($active): ?>
      <header class="kt-lesson-head">
        <div class="kt-lesson-crumbs">
          <button type="button" class="kt-btn sm kt-drawer-btn" onclick="document.getElementById('moduleDrawer').classList.toggle('open')">
            <svg class="kt-icon" viewBox="0 0 24 24"><path d="M4 6h16M4 12h10M4 18h16"/></svg>Modules <?= $doneCount ?>/<?= count($items) ?>
          </button>
          <span class="kt-eyebrow"><?= kt_h($activeItem['chapter'] ?: ($course['title'] ?? '')) ?> · Module <?= $activeIndex + 1 ?>/<?= count($items) ?></span>
        </div>
        <h1 class="kt-title"><?= kt_h($activeItem['short']) ?></h1>
        <div class="kt-lesson-meta">
          <span class="kt-badge <?= $activeItem['done'] ? 'success' : 'neutral' ?>" id="statusBadge"><?= $activeItem['done'] ? 'Terminé' : 'En cours' ?></span>
          <span class="kt-muted" id="readTime"></span>
          <span class="kt-muted" id="termCount"></span>
        </div>
      </header>

      <div class="kt-tabs" role="tablist" aria-label="Contenu du module">
        <button role="tab" class="on" data-tab="lecon" aria-selected="true">Leçon</button>
        <button role="tab" data-tab="revision" aria-selected="false">Révision <span class="kt-tab-count" id="revCount"></span></button>
        <button role="tab" data-tab="outils" aria-selected="false">Outils</button>
        <button role="tab" data-tab="notes" aria-selected="false">Mes notes <span class="kt-tab-count" id="notesDot"></span></button>
      </div>

      <div class="kt-tabpanel" data-panel="lecon">
        <div class="kt-lesson-toolbar">
          <button type="button" class="kt-btn sm" id="listenBtn"><svg class="kt-icon" viewBox="0 0 24 24"><path d="M11 5 6 9H3v6h3l5 4z"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/><path d="M18.5 5.5a9 9 0 0 1 0 13"/></svg>Écouter la leçon</button>
          <div class="kt-font-size" role="group" aria-label="Taille du texte">
            <button type="button" class="kt-btn ghost sm" data-font="-1" aria-label="Réduire le texte">A−</button>
            <button type="button" class="kt-btn ghost sm" data-font="1" aria-label="Agrandir le texte">A+</button>
          </div>
          <span class="kt-muted kt-small kt-hint">Astuce : sélectionne un passage pour l'ajouter à tes notes.</span>
        </div>

        <details class="kt-toc-inline" id="tocInline"><summary>Sommaire</summary><nav class="kt-toc"></nav></details>

        <?php if (!empty($active['video_url'])): ?>
          <div class="kt-media kt-media-video">
            <?php if (kt_is_local_media($active['video_url'])): ?>
              <video controls preload="metadata" src="<?= kt_h($active['video_url']) ?>"></video>
            <?php else: ?>
              <iframe title="Vidéo du module" src="<?= kt_h(kt_embed_video_url($active['video_url'])) ?>" allowfullscreen loading="lazy"></iframe>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($active['audio_url'])): ?>
          <div class="kt-media kt-media-audio">
            <span class="kt-badge">Audio du module</span>
            <audio controls preload="metadata" src="<?= kt_h($active['audio_url']) ?>"></audio>
          </div>
        <?php endif; ?>

        <?php if (!empty($active['content'])): ?>
          <article class="kt-md" id="lessonBody"><noscript><?= nl2br(kt_h($active['content'])) ?></noscript></article>
        <?php elseif (empty($active['video_url']) && empty($active['audio_url']) && empty($active['pdf_url'])): ?>
          <div class="kt-empty"><strong>Contenu bientôt disponible</strong><p class="kt-muted">L'équipe Katsuki prépare ce module.</p></div>
        <?php endif; ?>

        <?php if (!empty($active['pdf_url'])): ?>
          <div class="kt-media kt-media-pdf">
            <div class="kt-media-pdf-head">
              <span class="kt-badge">Document PDF</span>
              <a class="kt-btn sm primary" href="<?= kt_h($active['pdf_url']) ?>" target="_blank" rel="noopener">Ouvrir le PDF</a>
            </div>
            <?php if (kt_is_local_media($active['pdf_url'])): ?>
              <iframe src="<?= kt_h($active['pdf_url']) ?>" title="Document PDF" loading="lazy"></iframe>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div id="inlineTools"></div>

        <section class="kt-finish kt-card" id="finishCard">
          <div>
            <strong id="finishTitle"><?= $activeItem['done'] ? 'Module terminé' : 'Tu as fini de lire ?' ?></strong>
            <p class="kt-muted" id="finishText"><?= $activeItem['done'] ? 'Bravo ! Tu peux passer au module suivant ou réviser.' : 'Teste-toi dans l\'onglet Révision, puis valide le module pour débloquer la suite.' ?></p>
          </div>
          <div class="kt-finish-actions">
            <button type="button" class="kt-btn" data-go-tab="revision">Me tester</button>
            <button type="button" id="completeBtn" class="kt-btn primary" <?= $activeItem['done'] ? 'hidden' : '' ?>>Marquer comme terminé</button>
          </div>
        </section>
      </div>

      <div class="kt-tabpanel" data-panel="revision" hidden>
        <div class="kt-rev-intro">
          <h2>Révision express</h2>
          <p class="kt-muted">Les termes clés de ce module, extraits automatiquement de la leçon. Retourne les cartes, puis fais le quiz.</p>
        </div>
        <div id="flashcards"></div>
        <div id="quiz"></div>
      </div>

      <div class="kt-tabpanel" data-panel="outils" hidden>
        <div class="kt-rev-intro">
          <h2>Boîte à outils du trader</h2>
          <p class="kt-muted">Des simulateurs pour mettre la leçon en pratique. Rien n'est envoyé : tout se calcule ici.</p>
        </div>
        <div class="kt-tool-picker" id="toolPicker"></div>
        <div class="kt-card kt-card-pad kt-tool-host" id="toolHost"></div>
      </div>

      <div class="kt-tabpanel" data-panel="notes" hidden>
        <div class="kt-notes-head">
          <h2>Mes notes</h2>
          <span class="kt-muted kt-small" id="notesStatus">Enregistrées sur cet appareil</span>
        </div>
        <textarea id="notes" class="kt-textarea kt-notes" data-module="<?= (int)$active['id'] ?>" placeholder="Résume la leçon avec tes mots, note tes questions pour l'assistant IA…"></textarea>
        <div class="kt-notes-actions">
          <button type="button" class="kt-btn sm" id="exportNotes">Télécharger mes notes</button>
          <a class="kt-btn sm" href="/student/assistant.php">Poser une question à l'assistant IA</a>
        </div>
      </div>

      <nav class="kt-pager" aria-label="Navigation entre modules">
        <?php if ($prevItem): ?>
          <a class="kt-pager-link" href="<?= kt_h(kt_module_url($courseId, $prevItem['id'])) ?>"><small>← Précédent</small><span><?= kt_h($prevItem['short']) ?></span></a>
        <?php else: ?><span></span><?php endif; ?>
        <?php if ($nextItem): ?>
          <a class="kt-pager-link next <?= $lessonData['nextLocked'] ? 'locked' : '' ?>" id="nextLink" <?= $lessonData['nextLocked'] ? 'aria-disabled="true"' : 'href="' . kt_h(kt_module_url($courseId, $nextItem['id'])) . '"' ?>><small>Suivant →</small><span><?= kt_h($nextItem['short']) ?></span></a>
        <?php endif; ?>
      </nav>
    <?php else: ?>
      <div class="kt-empty kt-card"><strong>Aucun module pour cette formation</strong><p class="kt-muted">Les modules apparaîtront ici dès leur publication.</p></div>
    <?php endif; ?>
  </section>

  <aside class="kt-rail" aria-label="Sommaire du module">
    <div class="kt-rail-inner">
      <p class="kt-nav-label" style="margin-left:0">Dans ce module</p>
      <nav class="kt-toc" id="tocRail"></nav>
      <div class="kt-rail-tip">
        <span class="kt-small kt-muted">Raccourcis</span>
        <p class="kt-small"><kbd>←</kbd> <kbd>→</kbd> module précédent / suivant</p>
      </div>
    </div>
  </aside>
</div>

<div class="kt-speech" id="speechBar" hidden>
  <button type="button" class="kt-btn sm" data-speech="prev" aria-label="Paragraphe précédent">⏮</button>
  <button type="button" class="kt-btn sm primary" data-speech="toggle" aria-label="Pause">❚❚</button>
  <button type="button" class="kt-btn sm" data-speech="next" aria-label="Paragraphe suivant">⏭</button>
  <label class="kt-small kt-muted">Vitesse
    <select class="kt-select" data-speech="rate"><option value="0.8">0,8×</option><option value="1" selected>1×</option><option value="1.2">1,2×</option><option value="1.5">1,5×</option></select>
  </label>
  <span class="kt-small kt-muted kt-num" id="speechPos"></span>
  <button type="button" class="kt-btn ghost sm" data-speech="stop" aria-label="Arrêter la lecture">✕</button>
</div>

<button type="button" class="kt-selection-btn" id="selectionBtn" hidden>＋ Ajouter aux notes</button>
<div class="kt-popover" id="termPopover" role="dialog" hidden></div>

<script id="lessonData" type="application/json"><?= json_encode($lessonData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<script src="/assets/js/markdown.js"></script>
<script src="/assets/js/tools.js"></script>
<script src="/assets/js/lesson.js"></script>
<?php kt_page_end(); ?>
