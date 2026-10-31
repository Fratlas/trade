/**
 * KTAssistant — Logique complète de l'interface assistant IA Katsuki
 * Gère : rendu markdown, messages, voice, sidebar mobile, auto-resize textarea
 */
(function () {
  "use strict";

  // ── Config ─────────────────────────────────────────────────────────────────
  let _cfg = { role: "student", level: "etudiant", welcomeMsg: "" };

  // ── DOM refs ────────────────────────────────────────────────────────────────
  const $ = (id) => document.getElementById(id);

  // ── Markdown minimal (pas de dépendance externe) ───────────────────────────
  function renderMarkdown(text) {
    if (!text) return "";
    let h = text
      // Escape HTML first
      .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
      // Code blocks
      .replace(/```[\w]*\n?([\s\S]*?)```/g, (_, c) =>
        `<pre><code>${c.trim()}</code></pre>`)
      // Inline code
      .replace(/`([^`]+)`/g, "<code>$1</code>")
      // Headings
      .replace(/^### (.+)$/gm, "<h3>$1</h3>")
      .replace(/^## (.+)$/gm,  "<h2>$1</h2>")
      .replace(/^# (.+)$/gm,   "<h1>$1</h1>")
      // Blockquote
      .replace(/^> (.+)$/gm, "<blockquote>$1</blockquote>")
      // Bold + Italic
      .replace(/\*\*\*(.+?)\*\*\*/g, "<strong><em>$1</em></strong>")
      .replace(/\*\*(.+?)\*\*/g,     "<strong>$1</strong>")
      .replace(/\*(.+?)\*/g,         "<em>$1</em>")
      // HR
      .replace(/^[-─]{3,}$/gm, "<hr>")
      // Tables (simple)
      .replace(/^\|(.+)\|$/gm, (row) => {
        const cells = row.split("|").slice(1, -1);
        return "<tr>" + cells.map(c => `<td>${c.trim()}</td>`).join("") + "</tr>";
      })
      // Unordered lists
      .replace(/^\s*[-*] (.+)$/gm, "<li>$1</li>")
      // Ordered lists
      .replace(/^\s*\d+\. (.+)$/gm, "<li>$1</li>")
      // Paragraph breaks
      .replace(/\n\n+/g, "</p><p>")
      // Line breaks
      .replace(/\n/g, "<br>");

    // Wrap bare li in ul
    h = h.replace(/(<li>[\s\S]*?<\/li>)/g, (m) => `<ul>${m}</ul>`);
    // Clean consecutive ul
    h = h.replace(/<\/ul>\s*<ul>/g, "");
    // Wrap table rows
    h = h.replace(/(<tr>[\s\S]*?<\/tr>)/g, (m) => `<table>${m}</table>`);
    // Fix table header (first row → th)
    h = h.replace(/<table><tr>(.*?)<\/tr>/g, (_, row) => {
      const th = row.replace(/<td>/g, "<th>").replace(/<\/td>/g, "</th>");
      return `<table><tr>${th}</tr>`;
    });
    h = h.replace(/<\/table>\s*<table>/g, "");

    return "<p>" + h + "</p>";
  }

  // ── Heure courte ────────────────────────────────────────────────────────────
  function nowTime() {
    return new Date().toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" });
  }

  // ── Ajouter un message ──────────────────────────────────────────────────────
  function addMessage(type, content, isMarkdown) {
    const msgs = $("messages");
    const row  = document.createElement("div");
    row.className = "kta-msg-row " + type;

    // Avatar
    const av = document.createElement("div");
    av.className = "kta-msg-avatar";
    av.textContent = (type === "user")
      ? (_cfg._userInitial || "?")
      : "K";

    // Bubble
    const bub = document.createElement("div");
    bub.className = "kta-msg-bubble";
    if (isMarkdown) {
      bub.innerHTML = renderMarkdown(content);
    } else {
      bub.textContent = content;
    }

    // Time
    const time = document.createElement("div");
    time.className = "kta-msg-time";
    time.textContent = nowTime();

    const col = document.createElement("div");
    col.style.cssText = "display:flex;flex-direction:column;max-width:min(680px,100%)";
    col.appendChild(bub);
    col.appendChild(time);

    if (type === "user") {
      row.appendChild(col);
      row.appendChild(av);
    } else {
      row.appendChild(av);
      row.appendChild(col);
    }

    msgs.appendChild(row);
    msgs.scrollTop = msgs.scrollHeight;
    return { row, bub };
  }

  // ── Typing indicator ─────────────────────────────────────────────────────────
  function addTyping() {
    const msgs = $("messages");
    const row  = document.createElement("div");
    row.className = "kta-msg-row ai kta-typing";
    row.innerHTML = `
      <div class="kta-msg-avatar">K</div>
      <div class="kta-msg-bubble">
        <span class="kta-typing-dot"></span>
        <span class="kta-typing-dot"></span>
        <span class="kta-typing-dot"></span>
      </div>`;
    msgs.appendChild(row);
    msgs.scrollTop = msgs.scrollHeight;
    return row;
  }

  // ── Envoyer une question ─────────────────────────────────────────────────────
  async function ask(text) {
    if (!text.trim()) return;
    addMessage("user", text, false);
    const typing = addTyping();
    setStatus("Rédaction en cours…");
    setSendDisabled(true);

    try {
      const reply = await window.KTAI.askLesson(text, _cfg.level);
      typing.remove();
      addMessage("ai", reply, true);
      setStatus("Prêt");
    } catch (err) {
      typing.remove();
      addMessage("ai", "⚠ " + err.message, false);
      setStatus("Erreur");
      setTimeout(() => setStatus("Prêt"), 3000);
    } finally {
      setSendDisabled(false);
    }
  }

  // ── Helpers UI ───────────────────────────────────────────────────────────────
  function setStatus(text) {
    const el = $("statusLabel");
    if (el) el.textContent = text;
  }
  function setSendDisabled(disabled) {
    const btn = $("sendBtn");
    if (btn) btn.disabled = disabled;
  }

  // Auto-resize textarea
  function autoResize(el) {
    el.style.height = "auto";
    el.style.height = Math.min(el.scrollHeight, 160) + "px";
  }

  // ── Init ─────────────────────────────────────────────────────────────────────
  function init(cfg) {
    _cfg = Object.assign(_cfg, cfg);

    // Récupère l'initiale depuis l'avatar de la sidebar si disponible
    const av = document.querySelector(".kta-user-avatar");
    _cfg._userInitial = av ? av.textContent.trim() : "?";

    // Message de bienvenue
    addMessage("ai", _cfg.welcomeMsg, true);

    // Form submit
    const form = $("chatForm");
    const input = $("message");
    if (form && input) {
      form.addEventListener("submit", (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        input.value = "";
        autoResize(input);
        ask(text);
      });
      input.addEventListener("input", () => autoResize(input));
      input.addEventListener("keydown", (e) => {
        if (e.key === "Enter" && !e.shiftKey) {
          e.preventDefault();
          form.dispatchEvent(new Event("submit"));
        }
      });
    }

    // Sujets rapides
    document.querySelectorAll("[data-topic]").forEach((btn) =>
      btn.addEventListener("click", () => {
        ask(btn.dataset.topic);
        // Fermer sidebar sur mobile
        closeSidebar();
      })
    );

    // Bouton effacer
    const clearBtn = $("clearBtn");
    if (clearBtn) {
      clearBtn.addEventListener("click", () => {
        const msgs = $("messages");
        msgs.innerHTML = "";
        addMessage("ai", _cfg.welcomeMsg, true);
        setStatus("Prêt");
      });
    }

    // ── Sidebar mobile ──────────────────────────────────────────────────────
    const menuBtn  = $("menuBtn");
    const sidebar  = $("sidebar");
    const overlay  = $("overlay");
    function openSidebar()  { sidebar?.classList.add("open"); overlay?.classList.add("visible"); }
    function closeSidebar() { sidebar?.classList.remove("open"); overlay?.classList.remove("visible"); }
    menuBtn?.addEventListener("click", () =>
      sidebar?.classList.contains("open") ? closeSidebar() : openSidebar()
    );
    overlay?.addEventListener("click", closeSidebar);

    // ── Voice ────────────────────────────────────────────────────────────────
    const voiceBtn        = $("voiceBtn");
    const voiceLive       = $("voiceLive");
    const voiceStopInline = $("voiceStopInline");
    const voiceStatusTxt  = $("voiceStatusText");
    let voiceNode = null;

    if (window.KTVoiceCall) {
      KTVoiceCall.init({
        role: _cfg.role,
        onStateChange(state) {
          const active = state !== "idle";
          voiceBtn?.classList.toggle("active", active);
          if (voiceLive) voiceLive.classList.toggle("active", active && state !== "connecting");
          const labels = {
            idle:       "Prêt",
            connecting: "Connexion…",
            listening:  "En écoute — parlez maintenant",
            speaking:   "L'assistant répond…",
            error:      "Erreur — réessayez",
          };
          setStatus(labels[state] || "");
          if (voiceStatusTxt) voiceStatusTxt.textContent = labels[state] || "";
          if (state === "error") setTimeout(() => setStatus("Prêt"), 3500);
        },
        onTranscript(text, isFinal) {
          const msgs = $("messages");
          if (!voiceNode) {
            const { row, bub } = addMessage("voice-ai", text, false);
            voiceNode = { row, bub };
          } else {
            voiceNode.bub.textContent = text;
            msgs.scrollTop = msgs.scrollHeight;
          }
          if (isFinal) { voiceNode = null; }
        },
        onError(msg) {
          addMessage("ai", "⚠ Appel vocal : " + msg, false);
          voiceNode = null;
        },
      });

      const toggleVoice = () => {
        if (KTVoiceCall.getState() === "idle") KTVoiceCall.start();
        else KTVoiceCall.stop();
      };
      voiceBtn?.addEventListener("click", toggleVoice);
      voiceStopInline?.addEventListener("click", () => KTVoiceCall.stop());
    } else {
      voiceBtn?.setAttribute("title", "Appel vocal indisponible");
      voiceBtn?.setAttribute("disabled", "true");
    }
  }

  window.KTAssistant = { init };
})();
