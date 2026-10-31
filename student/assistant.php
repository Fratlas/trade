<?php
require_once __DIR__ . '/../src/views/bootstrap.php';
kt_logout_if_requested();
$user = kt_require_role('student');
$userName = kt_h($user['name']);
$userInitial = kt_h(mb_strtoupper(mb_substr($user['name'] ?? 'E', 0, 1)));
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assistant IA | Katsuki Trading</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/shared.css">
  <link rel="stylesheet" href="/assets/css/assistant.css">
</head>
<body class="kt-assistant-page">

  <!-- ── SIDEBAR ── -->
  <aside class="kta-sidebar" id="sidebar">
    <div class="kta-sidebar-top">
      <div class="kta-brand">
        <div class="kta-logo-wrap"><span class="kta-logo-letter">K</span></div>
        <div class="kta-brand-text">
          <span class="kta-brand-name">Katsuki</span>
          <span class="kta-brand-sub">Trading Academy</span>
        </div>
      </div>

      <div class="kta-user-card">
        <div class="kta-user-avatar"><?= $userInitial ?></div>
        <div class="kta-user-info">
          <span class="kta-user-name"><?= $userName ?></span>
          <span class="kta-user-role">Étudiant</span>
        </div>
        <a href="?logout=1" class="kta-logout-btn" title="Déconnexion">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </a>
      </div>

      <a href="/student/dashboard.php" class="kta-back-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        Retour au dashboard
      </a>
    </div>

    <div class="kta-sidebar-section">
      <p class="kta-section-label">Sujets rapides</p>
      <div class="kta-topics">
        <button type="button" class="kta-topic-btn" data-topic="Explique-moi la gestion du risque selon la methode Katsuki (1-2% par trade)">
          <span class="kta-topic-icon">⚖️</span>
          <span>Gestion du risque</span>
        </button>
        <button type="button" class="kta-topic-btn" data-topic="C'est quoi le spread et comment il impacte un petit compte de 10 dollars ?">
          <span class="kta-topic-icon">📊</span>
          <span>Le Spread</span>
        </button>
        <button type="button" class="kta-topic-btn" data-topic="Explique la structure de marche : tendances, supports, resistances et zones de liquidite">
          <span class="kta-topic-icon">📈</span>
          <span>Structure de marché</span>
        </button>
        <button type="button" class="kta-topic-btn" data-topic="Explique les bougies japonaises : doji, engulfing, pin bar et marteau avec des exemples">
          <span class="kta-topic-icon">🕯️</span>
          <span>Bougies japonaises</span>
        </button>
        <button type="button" class="kta-topic-btn" data-topic="Explique les time frames et la regle des 3 TF top-down analysis">
          <span class="kta-topic-icon">⏱️</span>
          <span>Time Frames</span>
        </button>
        <button type="button" class="kta-topic-btn" data-topic="Comment eviter le revenge trading et garder la discipline en trading ?">
          <span class="kta-topic-icon">🧠</span>
          <span>Psychologie</span>
        </button>
        <button type="button" class="kta-topic-btn" data-topic="Prepare-moi un plan de revision pour l'examen Genese du Trader">
          <span class="kta-topic-icon">📝</span>
          <span>Préparer l'examen</span>
        </button>
        <button type="button" class="kta-topic-btn" data-topic="C'est quoi le Forex, les paires majeures et comment trader depuis la RDC ?">
          <span class="kta-topic-icon">🌍</span>
          <span>Forex & RDC</span>
        </button>
      </div>
    </div>

    <div class="kta-sidebar-footer">
      <span class="kta-ai-badge">
        <span class="kta-ai-dot"></span>
        IA active — Qwen · OpenRouter
      </span>
    </div>
  </aside>

  <!-- ── MAIN CHAT ── -->
  <div class="kta-chat-wrap">

    <!-- Topbar mobile -->
    <header class="kta-topbar">
      <button class="kta-menu-btn" id="menuBtn" aria-label="Menu">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="kta-topbar-title">
        <div class="kta-ai-avatar-sm">K</div>
        <div>
          <strong>Assistant Katsuki</strong>
          <span id="statusLabel">Prêt</span>
        </div>
      </div>
      <button type="button" id="clearBtn" class="kta-icon-btn" title="Effacer la conversation">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
      </button>
    </header>

    <!-- Zone messages -->
    <div class="kta-messages" id="messages">
      <!-- Message de bienvenue injecté par JS -->
    </div>

    <!-- Barre de saisie -->
    <div class="kta-composer">
      <div class="kta-composer-inner">
        <div class="kta-voice-live" id="voiceLive">
          <span class="kta-voice-bars">
            <span></span><span></span><span></span><span></span><span></span>
          </span>
          <span id="voiceStatusText" class="kta-voice-live-text">En écoute…</span>
          <button type="button" id="voiceStopInline" class="kta-voice-stop">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>
            Raccrocher
          </button>
        </div>
        <form id="chatForm" class="kta-form">
          <textarea
            id="message"
            class="kta-composer-input"
            placeholder="Posez votre question sur le trading…"
            rows="1"
          ></textarea>
          <div class="kta-composer-actions">
            <button type="button" id="voiceBtn" class="kta-voice-trigger" title="Appel vocal IA">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
            </button>
            <button type="submit" class="kta-send-btn" id="sendBtn">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
          </div>
        </form>
      </div>
      <p class="kta-disclaimer">Katsuki IA · Informations pédagogiques uniquement · Pas de conseil financier</p>
    </div>

  </div>

  <!-- Overlay mobile -->
  <div class="kta-overlay" id="overlay"></div>

  <script src="/assets/js/api.js"></script>
  <script src="/assets/js/auth.js"></script>
  <script src="/assets/js/ui.js"></script>
  <script src="/assets/js/openrouter.js"></script>
  <script src="/assets/js/voicecall.js"></script>
  <script src="/assets/js/assistant.js"></script>
  <script>
    KTAssistant.init({
      role: "student",
      level: "etudiant",
      welcomeMsg: "Bonjour **<?= $userName ?>** 👋\n\nJe suis votre coach Katsuki, spécialisé dans la formation au trading depuis la RDC.\n\nPosez-moi n'importe quelle question sur vos modules — lexique du trading, structure de marché, gestion du risque, bougies, time frames ou préparation aux examens.\n\nComment puis-je vous aider aujourd'hui ?"
    });
  </script>
</body>
</html>
