/* Lecteur de module interactif : rendu de la leçon, sommaire, termes clés,
   flashcards, quiz, lecture vocale, notes et validation du module. */
(function () {
  const dataNode = document.getElementById("lessonData");
  const L = dataNode ? JSON.parse(dataNode.textContent || "null") : null;
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const esc = (v) => KTMarkdown.escape(v);
  const store = {
    get(key, fallback) { try { const v = localStorage.getItem(key); return v == null ? fallback : JSON.parse(v); } catch (e) { return fallback; } },
    set(key, value) { try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* stockage indisponible */ } },
    raw(key) { try { return localStorage.getItem(key) || ""; } catch (e) { return ""; } },
    setRaw(key, value) { try { localStorage.setItem(key, value); } catch (e) { /* stockage indisponible */ } }
  };
  const shuffle = (arr) => { const a = arr.slice(); for (let i = a.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [a[i], a[j]] = [a[j], a[i]]; } return a; };
  const norm = (s) => String(s).toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "").trim();

  /* ── Anneaux de progression ─────────────────────────────────────── */
  $$("[data-ring]").forEach((ring) => {
    const bar = $(".bar", ring);
    const len = parseFloat(bar.getAttribute("stroke-dasharray"));
    requestAnimationFrame(() => { bar.style.strokeDashoffset = len * (1 - Math.min(100, +ring.dataset.ring) / 100); });
  });

  /* ── Liste des modules : recherche + tiroir mobile ──────────────── */
  const search = $("#moduleSearch");
  if (search) {
    search.addEventListener("input", () => {
      const term = norm(search.value);
      $$(".kt-module").forEach((m) => { m.hidden = term && !norm(m.dataset.title).includes(term); });
      $$(".kt-chapter").forEach((c) => { c.hidden = $$(".kt-module", c).every((m) => m.hidden); });
    });
  }
  const activeModule = $(".kt-module.active");
  // Centre le module actif dans la liste sans faire défiler la page.
  const list = $("#moduleList");
  if (activeModule && list) list.scrollTop = activeModule.offsetTop - list.offsetTop - list.clientHeight / 2 + activeModule.offsetHeight / 2;
  const drawer = $("#moduleDrawer");
  document.addEventListener("click", (e) => {
    if (drawer && drawer.classList.contains("open") && !e.target.closest("#moduleDrawer, .kt-drawer-btn")) drawer.classList.remove("open");
  });
  $$(".kt-module.locked").forEach((m) => m.addEventListener("click", (e) => { e.preventDefault(); KTUi.toast("Termine le module précédent pour déverrouiller celui-ci."); }));

  if (!L) return;

  /* ── Onglets ─────────────────────────────────────────────────────── */
  const tabs = $$(".kt-tabs [data-tab]");
  function openTab(name) {
    tabs.forEach((t) => { const on = t.dataset.tab === name; t.classList.toggle("on", on); t.setAttribute("aria-selected", on); });
    $$(".kt-tabpanel").forEach((p) => { p.hidden = p.dataset.panel !== name; });
    if (name === "outils" && !$("#toolHost").dataset.ready) initToolbox();
    store.set("kt_tab_" + L.moduleId, name);
    const head = $(".kt-tabs");
    if (head.getBoundingClientRect().top < 0) head.scrollIntoView({ behavior: "smooth" });
  }
  tabs.forEach((t) => t.addEventListener("click", () => openTab(t.dataset.tab)));
  $$("[data-go-tab]").forEach((b) => b.addEventListener("click", () => openTab(b.dataset.goTab)));

  /* ── Rendu de la leçon ───────────────────────────────────────────── */
  const body = $("#lessonBody");
  if (body) {
    let html = KTMarkdown.render(L.content);
    body.innerHTML = html;
    // Le premier titre répète souvent le titre du module : on le retire.
    const first = body.firstElementChild;
    if (first && first.tagName === "H2" && norm(L.title).includes(norm(first.textContent).slice(0, 12))) first.remove();
    $$("table", body).forEach((t) => t.classList.add("kt-md-tbl"));
  }
  const words = body ? body.textContent.split(/\s+/).filter(Boolean).length : 0;
  $("#readTime").textContent = words ? "≈ " + Math.max(1, Math.round(words / 200)) + " min de lecture" : "";

  const fontKey = "kt_lesson_font";
  let fontStep = store.get(fontKey, 0);
  const applyFont = () => { if (body) body.style.fontSize = (16 + fontStep) + "px"; };
  applyFont();
  $$("[data-font]").forEach((b) => b.addEventListener("click", () => { fontStep = Math.max(-2, Math.min(4, fontStep + +b.dataset.font)); store.set(fontKey, fontStep); applyFont(); }));

  /* ── Sommaire + suivi de lecture ─────────────────────────────────── */
  const headings = body ? $$("h2, h3", body) : [];
  const tocHtml = headings.map((h) => `<a href="#${h.id}" class="lvl-${h.tagName.toLowerCase()}" data-id="${h.id}">${esc(h.textContent)}</a>`).join("");
  $$(".kt-toc").forEach((n) => { n.innerHTML = tocHtml || '<span class="kt-muted kt-small">Pas de sous-parties.</span>'; });
  if (!headings.length) { const inline = $("#tocInline"); if (inline) inline.hidden = true; }
  if (headings.length && "IntersectionObserver" in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting) $$(".kt-toc a").forEach((a) => a.classList.toggle("on", a.dataset.id === e.target.id));
      });
    }, { rootMargin: "-10% 0px -75% 0px" });
    headings.forEach((h) => io.observe(h));
  }
  const bar = $("#readProgress");
  const onScroll = () => {
    if (!body || $('[data-panel="lecon"]').hidden) { bar.style.width = "0"; return; }
    const r = body.getBoundingClientRect();
    const pct = Math.min(1, Math.max(0, (innerHeight * 0.6 - r.top) / Math.max(r.height, 1)));
    bar.style.width = (pct * 100) + "%";
  };
  addEventListener("scroll", onScroll, { passive: true });
  onScroll();

  /* ── Listes de vérification ──────────────────────────────────────── */
  const taskKey = "kt_tasks_" + L.moduleId;
  const tasks = store.get(taskKey, {});
  $$("[data-task]").forEach((cb) => {
    if (tasks[cb.dataset.task] !== undefined) cb.checked = tasks[cb.dataset.task];
    const li = cb.closest("li");
    li.classList.toggle("checked", cb.checked);
    cb.addEventListener("change", () => {
      tasks[cb.dataset.task] = cb.checked; store.set(taskKey, tasks);
      li.classList.toggle("checked", cb.checked);
      const all = $$("[data-task]", cb.closest("ul"));
      if (cb.checked && all.every((x) => x.checked)) KTUi.toast("Checklist complétée ✓");
    });
  });

  /* ── Termes clés (glossaire extrait de la leçon) ─────────────────── */
  const glossary = [];
  const seen = {};
  function addTerm(term, def, el) {
    term = term.replace(/\s+/g, " ").trim().replace(/[:：]$/, "").trim();
    def = def.replace(/\s+/g, " ").trim().replace(/^[:—–=→\-\s]+/, "");
    const key = norm(term);
    if (!term || term.length > 48 || term.split(" ").length > 6 || def.length < 18 || seen[key]) return;
    seen[key] = true;
    glossary.push({ term, def, el });
  }
  if (body) {
    $$("tr", body).forEach((tr) => {
      const cells = $$("td", tr);
      if (cells.length >= 2 && $("strong", cells[0]) && cells[0].textContent.trim() === $("strong", cells[0]).textContent.trim()) {
        const head = $$("th", tr.closest("table"));
        const def = cells.slice(1).map((c, i) => (head[i + 1] && cells.length > 2 ? head[i + 1].textContent.trim() + " : " : "") + c.textContent.trim()).join(" · ");
        addTerm(cells[0].textContent, def, cells[0]);
      }
    });
    $$("p, li", body).forEach((block) => {
      if (block.closest("table, .kt-md-tasks")) return;
      $$("strong", block).forEach((s) => {
        const term = s.textContent;
        const text = block.textContent.replace(/\s+/g, " ").trim();
        let def;
        const after = text.split(term).slice(1).join(term);
        if (block.tagName === "LI" && /^\s*(\([^)]*\))?\s*[:—–=→-]/.test(after)) def = after;
        else {
          const sentences = text.match(/[^.!?]+[.!?]?/g) || [text];
          def = (sentences.find((x) => x.includes(term)) || text).trim();
          if (!/(est |sont |représente|consiste|désigne|signifie|c'est|permet|sert|indique|correspond|=|:)/i.test(def)) return;
        }
        addTerm(term, def, s);
      });
    });
    glossary.forEach((g) => {
      if (g.el.tagName === "STRONG" || $("strong", g.el)) {
        const strong = g.el.tagName === "STRONG" ? g.el : $("strong", g.el);
        strong.classList.add("has-def");
        strong.tabIndex = 0;
        strong.dataset.term = glossary.indexOf(g);
        strong.setAttribute("role", "button");
      }
    });
  }
  $("#termCount").textContent = glossary.length ? glossary.length + " termes clés" : "";
  $("#revCount").textContent = glossary.length || "";

  const pop = $("#termPopover");
  function showTerm(el) {
    const g = glossary[+el.dataset.term];
    if (!g) return;
    const masked = g.def;
    pop.innerHTML = `<strong>${esc(g.term)}</strong><p>${esc(masked)}</p><button type="button" class="kt-btn sm" data-go-rev>Réviser ce terme</button>`;
    pop.hidden = false;
    const r = el.getBoundingClientRect();
    const w = Math.min(320, innerWidth - 24);
    pop.style.width = w + "px";
    pop.style.left = Math.max(12, Math.min(innerWidth - w - 12, r.left + r.width / 2 - w / 2)) + "px";
    const below = r.bottom + 10 + pop.offsetHeight < innerHeight;
    pop.style.top = (below ? r.bottom + 10 : r.top - pop.offsetHeight - 10) + scrollY + "px";
    $("[data-go-rev]", pop).onclick = () => { pop.hidden = true; openTab("revision"); showCard(glossary.indexOf(g)); };
  }
  document.addEventListener("click", (e) => {
    const t = e.target.closest("strong.has-def");
    if (t) { showTerm(t); return; }
    if (!e.target.closest("#termPopover")) pop.hidden = true;
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") pop.hidden = true;
    if ((e.key === "Enter" || e.key === " ") && document.activeElement && document.activeElement.matches("strong.has-def")) { e.preventDefault(); showTerm(document.activeElement); }
  });

  /* ── Flashcards ──────────────────────────────────────────────────── */
  const cardsBox = $("#flashcards");
  const knownKey = "kt_known_" + L.moduleId;
  let known = store.get(knownKey, {});
  let order = glossary.map((_, i) => i);
  let pos = 0;
  function renderCards() {
    if (!glossary.length) {
      cardsBox.innerHTML = '<div class="kt-empty"><strong>Pas de termes clés détectés</strong><p class="kt-muted">Ce module ne contient pas de définitions à réviser. Passe directement aux outils pratiques.</p></div>';
      return;
    }
    const idx = order[pos];
    const g = glossary[idx];
    const knownCount = Object.keys(known).filter((k) => known[k]).length;
    cardsBox.innerHTML = `
      <div class="kt-fc-top"><span class="kt-small kt-muted kt-num">Carte ${pos + 1} / ${order.length}</span>
        <div class="kt-progress" style="flex:1"><span style="width:${knownCount / glossary.length * 100}%"></span></div>
        <span class="kt-small kt-muted">${knownCount} maîtrisé${knownCount > 1 ? "s" : ""}</span></div>
      <button type="button" class="kt-flashcard ${known[idx] ? "is-known" : ""}" aria-label="Retourner la carte">
        <span class="face front"><small>Terme</small><strong>${esc(g.term)}</strong><em>Clique ou appuie sur Espace pour voir la définition</em></span>
        <span class="face back"><small>${esc(g.term)}</small><p>${esc(g.def)}</p></span>
      </button>
      <div class="kt-fc-actions">
        <button type="button" class="kt-btn sm" data-fc="prev" aria-label="Carte précédente">←</button>
        <button type="button" class="kt-btn sm danger" data-fc="again">À revoir</button>
        <button type="button" class="kt-btn sm primary" data-fc="known">Je sais ✓</button>
        <button type="button" class="kt-btn sm" data-fc="next" aria-label="Carte suivante">→</button>
        <button type="button" class="kt-btn ghost sm" data-fc="shuffle">Mélanger</button>
      </div>`;
  }
  function showCard(i) { pos = Math.max(0, order.indexOf(i)); renderCards(); }
  cardsBox.addEventListener("click", (e) => {
    const card = e.target.closest(".kt-flashcard");
    if (card) { card.classList.toggle("flipped"); return; }
    const a = e.target.closest("[data-fc]");
    if (!a) return;
    const act = a.dataset.fc;
    if (act === "prev") pos = (pos - 1 + order.length) % order.length;
    if (act === "next") pos = (pos + 1) % order.length;
    if (act === "known" || act === "again") { known[order[pos]] = act === "known"; store.set(knownKey, known); pos = (pos + 1) % order.length; }
    if (act === "shuffle") { order = shuffle(order); pos = 0; }
    renderCards();
  });
  renderCards();

  /* ── Quiz automatique ────────────────────────────────────────────── */
  const quizBox = $("#quiz");
  const bestKey = "kt_quiz_best_" + L.moduleId;
  let quiz = [], qi = 0, good = 0, answered = false;
  function maskDef(g) {
    const re = new RegExp(g.term.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"), "gi");
    return g.def.replace(re, "_____");
  }
  function startQuiz() {
    const pool = glossary.filter((g) => maskDef(g).includes("_____") || g.def.length > 25);
    quiz = shuffle(pool).slice(0, Math.min(5, pool.length)).map((g) => {
      const others = shuffle(glossary.filter((x) => x !== g)).slice(0, 3).map((x) => x.term);
      return { prompt: maskDef(g), answer: g.term, options: shuffle([g.term, ...others]) };
    });
    qi = 0; good = 0; renderQuiz();
  }
  function renderQuiz() {
    if (glossary.length < 2) { quizBox.innerHTML = ""; return; }
    const best = store.get(bestKey, null);
    if (!quiz.length) {
      quizBox.innerHTML = `<div class="kt-quiz-card kt-card kt-card-pad"><div><h3>Quiz du module</h3><p class="kt-muted">${Math.min(5, glossary.length)} questions générées à partir de la leçon. ${best !== null ? "Ton meilleur score : <b>" + best + " %</b>." : ""}</p></div><button type="button" class="kt-btn primary" data-quiz="start">Lancer le quiz</button></div>`;
      return;
    }
    if (qi >= quiz.length) {
      const pct = Math.round(good / quiz.length * 100);
      const newBest = best === null || pct > best;
      if (newBest) store.set(bestKey, pct);
      quizBox.innerHTML = `<div class="kt-quiz-card kt-card kt-card-pad kt-quiz-result ${pct >= 70 ? "pass" : "fail"}">
        <div class="kt-ring" style="--size:84px" data-ring="${pct}"><svg viewBox="0 0 64 64"><circle class="track" cx="32" cy="32" r="27"/><circle class="bar" cx="32" cy="32" r="27" stroke-dasharray="169.6" stroke-dashoffset="169.6"/></svg><b>${pct}%</b></div>
        <div><h3>${pct >= 70 ? "Bien joué !" : "Encore un effort"}</h3><p class="kt-muted">${good} bonne${good > 1 ? "s" : ""} réponse${good > 1 ? "s" : ""} sur ${quiz.length}.${newBest ? " Nouveau record !" : ""} ${pct >= 70 ? "Tu peux valider le module." : "Relis les termes en rouge, puis réessaie."}</p>
        <div class="kt-finish-actions"><button type="button" class="kt-btn" data-quiz="start">Recommencer</button>${!L.done && pct >= 70 ? '<button type="button" class="kt-btn primary" data-quiz="complete">Valider le module</button>' : ""}</div></div></div>`;
      const ring = $("[data-ring]", quizBox), barEl = $(".bar", ring);
      requestAnimationFrame(() => requestAnimationFrame(() => { barEl.style.strokeDashoffset = 169.6 * (1 - pct / 100); }));
      if (pct >= 70) confetti();
      return;
    }
    const q = quiz[qi];
    answered = false;
    quizBox.innerHTML = `<div class="kt-quiz-card kt-card kt-card-pad kt-quiz-live">
      <div class="kt-fc-top"><span class="kt-small kt-muted kt-num">Question ${qi + 1} / ${quiz.length}</span><div class="kt-progress" style="flex:1"><span style="width:${qi / quiz.length * 100}%"></span></div></div>
      <p class="kt-quiz-q">Quel terme correspond à cette définition ?</p>
      <blockquote class="kt-quiz-def">${esc(q.prompt)}</blockquote>
      <div class="kt-options">${q.options.map((o, i) => `<button type="button" class="kt-option" data-opt="${esc(o)}"><kbd>${i + 1}</kbd>${esc(o)}</button>`).join("")}</div>
      <div class="kt-quiz-feedback" aria-live="polite"></div></div>`;
  }
  function answer(btn) {
    if (answered || !btn) return;
    answered = true;
    const q = quiz[qi];
    const ok = btn.dataset.opt === q.answer;
    if (ok) good++;
    $$(".kt-option", quizBox).forEach((b) => {
      b.disabled = true;
      if (b.dataset.opt === q.answer) b.classList.add("right");
      else if (b === btn) b.classList.add("wrong");
    });
    if (!ok) { const g = glossary.find((x) => x.term === q.answer); if (g) { known[glossary.indexOf(g)] = false; store.set(knownKey, known); } }
    $(".kt-quiz-feedback", quizBox).innerHTML = `<span class="${ok ? "ok" : "ko"}">${ok ? "Correct !" : "Pas tout à fait : la réponse était « " + esc(q.answer) + " »."}</span><button type="button" class="kt-btn sm primary" data-quiz="next">${qi + 1 < quiz.length ? "Question suivante" : "Voir mon score"}</button>`;
    $('[data-quiz="next"]', quizBox).focus();
  }
  quizBox.addEventListener("click", (e) => {
    const opt = e.target.closest(".kt-option");
    if (opt) return answer(opt);
    const a = e.target.closest("[data-quiz]");
    if (!a) return;
    if (a.dataset.quiz === "start") startQuiz();
    if (a.dataset.quiz === "next") { qi++; renderQuiz(); }
    if (a.dataset.quiz === "complete") complete();
  });
  renderQuiz();

  /* ── Outils interactifs ──────────────────────────────────────────── */
  const suggested = KTTools.suggest(L.title);
  const inline = $("#inlineTools");
  if (inline && suggested.length) {
    const id = suggested[0], tool = KTTools.registry[id];
    inline.innerHTML = `<section class="kt-card kt-inline-tool"><header><span class="kt-eyebrow">Mets en pratique</span><h3>${tool.name}</h3><p class="kt-muted kt-small">${tool.desc}</p></header><div data-tool></div>
      ${suggested.length > 1 || Object.keys(KTTools.registry).length > 1 ? '<button type="button" class="kt-btn ghost sm" data-go-tab="outils">Voir tous les outils →</button>' : ""}</section>`;
    KTTools.mount($("[data-tool]", inline), id);
    $("[data-go-tab]", inline).addEventListener("click", () => openTab("outils"));
  }
  function initToolbox() {
    const host = $("#toolHost"), picker = $("#toolPicker");
    host.dataset.ready = "1";
    const ids = Object.keys(KTTools.registry).sort((a, b) => (suggested.includes(b) ? 1 : 0) - (suggested.includes(a) ? 1 : 0));
    picker.innerHTML = ids.map((id) => `<button type="button" class="kt-tool-pick" data-tool-id="${id}"><strong>${KTTools.registry[id].name}${suggested.includes(id) ? ' <span class="kt-badge">Lié au module</span>' : ""}</strong><span>${KTTools.registry[id].desc}</span></button>`).join("");
    const mountTool = (id) => {
      $$(".kt-tool-pick", picker).forEach((b) => b.classList.toggle("on", b.dataset.toolId === id));
      host.innerHTML = `<h3 style="margin-top:0">${KTTools.registry[id].name}</h3><div></div>`;
      KTTools.mount(host.lastElementChild, id);
      store.set("kt_last_tool", id);
    };
    picker.addEventListener("click", (e) => { const b = e.target.closest("[data-tool-id]"); if (b) { mountTool(b.dataset.toolId); if (innerWidth < 900) host.scrollIntoView({ behavior: "smooth" }); } });
    mountTool(suggested[0] || store.get("kt_last_tool", "lot"));
  }

  /* ── Notes ───────────────────────────────────────────────────────── */
  const notes = $("#notes");
  const notesKey = "kt_notes_" + L.moduleId;
  const status = $("#notesStatus");
  const dot = $("#notesDot");
  let saveTimer;
  notes.value = store.raw(notesKey);
  const refreshDot = () => { dot.textContent = notes.value.trim() ? "●" : ""; };
  refreshDot();
  notes.addEventListener("input", () => {
    status.textContent = "Enregistrement…";
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => { store.setRaw(notesKey, notes.value); status.textContent = "Enregistré ✓ · " + notes.value.trim().split(/\s+/).filter(Boolean).length + " mots"; refreshDot(); }, 400);
  });
  $("#exportNotes").addEventListener("click", () => {
    const a = document.createElement("a");
    a.href = URL.createObjectURL(new Blob([L.title + "\n\n" + notes.value], { type: "text/plain;charset=utf-8" }));
    a.download = "notes-" + norm(L.title).replace(/[^a-z0-9]+/g, "-").slice(0, 50) + ".txt";
    a.click();
  });
  const selBtn = $("#selectionBtn");
  let selected = "";
  document.addEventListener("selectionchange", () => {
    const sel = getSelection();
    const text = sel ? sel.toString().trim() : "";
    if (!body || !text || text.length < 4 || !sel.rangeCount || !body.contains(sel.anchorNode)) { selBtn.hidden = true; return; }
    selected = text;
    const r = sel.getRangeAt(0).getBoundingClientRect();
    selBtn.hidden = false;
    selBtn.style.top = (r.top + scrollY - selBtn.offsetHeight - 10) + "px";
    selBtn.style.left = Math.max(12, Math.min(innerWidth - selBtn.offsetWidth - 12, r.left + r.width / 2 - selBtn.offsetWidth / 2)) + "px";
  });
  selBtn.addEventListener("mousedown", (e) => e.preventDefault());
  selBtn.addEventListener("click", () => {
    notes.value = (notes.value.trim() ? notes.value.trimEnd() + "\n\n" : "") + "« " + selected + " »\n";
    store.setRaw(notesKey, notes.value); refreshDot();
    selBtn.hidden = true; getSelection().removeAllRanges();
    KTUi.toast("Ajouté à tes notes (onglet Mes notes).");
  });

  /* ── Lecture vocale paragraphe par paragraphe ────────────────────── */
  const speech = window.speechSynthesis;
  const speechBar = $("#speechBar");
  const blocks = body ? $$(".kt-md-block", body).filter((b) => b.textContent.trim()) : [];
  let sIdx = 0, playing = false, rate = 1;
  const voice = () => (speech.getVoices().find((v) => /^fr/i.test(v.lang)) || null);
  function speak(i) {
    if (!speech || !blocks.length) return;
    speech.cancel();
    sIdx = Math.max(0, Math.min(blocks.length - 1, i));
    $$(".kt-speaking", body).forEach((b) => b.classList.remove("kt-speaking"));
    const el = blocks[sIdx];
    el.classList.add("kt-speaking");
    el.scrollIntoView({ behavior: "smooth", block: "center" });
    const u = new SpeechSynthesisUtterance(el.innerText.replace(/[|#*`>_]/g, " "));
    u.lang = "fr-FR"; u.rate = rate;
    const v = voice(); if (v) u.voice = v;
    u.onend = () => { if (playing && sIdx === i) { if (sIdx + 1 < blocks.length) speak(sIdx + 1); else stopSpeech(); } };
    playing = true;
    speechBar.hidden = false;
    $('[data-speech="toggle"]').textContent = "❚❚";
    $("#speechPos").textContent = (sIdx + 1) + " / " + blocks.length;
    speech.speak(u);
  }
  function stopSpeech() {
    playing = false; if (speech) speech.cancel();
    speechBar.hidden = true;
    $$(".kt-speaking", body || document).forEach((b) => b.classList.remove("kt-speaking"));
  }
  $("#listenBtn").addEventListener("click", () => {
    if (!speech) { KTUi.toast("La lecture vocale n'est pas disponible sur ce navigateur."); return; }
    if (!blocks.length) { KTUi.toast("Aucun texte à lire pour ce module."); return; }
    playing ? stopSpeech() : speak(0);
  });
  speechBar.addEventListener("click", (e) => {
    const b = e.target.closest("[data-speech]"); if (!b) return;
    const act = b.dataset.speech;
    if (act === "stop") stopSpeech();
    if (act === "prev") speak(sIdx - 1);
    if (act === "next") speak(sIdx + 1);
    if (act === "toggle") {
      if (speech.paused) { speech.resume(); b.textContent = "❚❚"; } else { speech.pause(); b.textContent = "▶"; }
    }
  });
  $('[data-speech="rate"]').addEventListener("change", (e) => { rate = +e.target.value; if (playing) speak(sIdx); });
  if (body) body.addEventListener("click", (e) => {
    if (!playing || e.target.closest("strong.has-def, input, a")) return;
    const b = e.target.closest(".kt-md-block");
    if (b && blocks.includes(b)) speak(blocks.indexOf(b));
  });
  addEventListener("beforeunload", () => speech && speech.cancel());

  /* ── Validation du module ────────────────────────────────────────── */
  const completeBtn = $("#completeBtn");
  async function complete() {
    if (L.done) return;
    try {
      if (completeBtn) { completeBtn.disabled = true; completeBtn.textContent = "Validation…"; }
      const best = store.get(bestKey, null);
      await KTApi.post("/progress", best === null ? { module_id: L.moduleId, completed: true } : { module_id: L.moduleId, completed: true, score: best });
      L.done = true;
      confetti();
      $("#statusBadge").textContent = "Terminé"; $("#statusBadge").className = "kt-badge success";
      $("#finishTitle").textContent = "Module terminé !";
      $("#finishText").textContent = L.nextUrl ? "Le module suivant est débloqué : " + L.nextTitle + "." : "Tu as terminé tous les modules de cette formation. Passe l'examen final !";
      if (completeBtn) completeBtn.hidden = true;
      const actions = $("#finishCard .kt-finish-actions");
      actions.innerHTML = L.nextUrl
        ? `<a class="kt-btn primary" href="${L.nextUrl}">Module suivant →</a>`
        : '<a class="kt-btn primary" href="/student/quiz.php">Passer l\'examen final</a>';
      const next = $("#nextLink");
      if (next && L.nextUrl) { next.classList.remove("locked"); next.removeAttribute("aria-disabled"); next.href = L.nextUrl; }
      const current = $(".kt-module.active");
      if (current) { current.classList.remove("open"); current.classList.add("done"); $(".kt-module-dot", current).innerHTML = '<svg viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>'; }
      const nextItem = current && current.nextElementSibling ? current.nextElementSibling : null;
      if (nextItem && nextItem.classList.contains("locked") && L.nextUrl) { nextItem.classList.remove("locked"); nextItem.classList.add("open"); nextItem.href = L.nextUrl; nextItem.removeAttribute("aria-disabled"); nextItem.removeAttribute("tabindex"); }
      renderQuiz();
    } catch (err) {
      KTUi.toast(err.message || "Impossible de valider le module.");
      if (completeBtn) { completeBtn.disabled = false; completeBtn.textContent = "Marquer comme terminé"; }
    }
  }
  if (completeBtn) completeBtn.addEventListener("click", complete);
  $$(".kt-pager-link.locked").forEach((a) => a.addEventListener("click", (e) => { if (!L.done) { e.preventDefault(); KTUi.toast("Valide ce module pour débloquer le suivant."); } }));

  function confetti() {
    if (matchMedia("(prefers-reduced-motion: reduce)").matches) return;
    const layer = document.createElement("div");
    layer.className = "kt-confetti";
    const colors = ["#ecd187", "#d4af55", "#34d399", "#f4f3ef", "#8a6a1f"];
    for (let i = 0; i < 80; i++) {
      const p = document.createElement("i");
      p.style.left = Math.random() * 100 + "%";
      p.style.background = colors[i % colors.length];
      p.style.animationDelay = Math.random() * 0.4 + "s";
      p.style.setProperty("--x", (Math.random() * 2 - 1) * 120 + "px");
      p.style.setProperty("--r", Math.random() * 720 + "deg");
      layer.appendChild(p);
    }
    document.body.appendChild(layer);
    setTimeout(() => layer.remove(), 2600);
  }

  /* ── Raccourcis clavier ──────────────────────────────────────────── */
  document.addEventListener("keydown", (e) => {
    if (e.target.closest("input, textarea, select, [contenteditable]") || e.metaKey || e.ctrlKey || e.altKey) return;
    const revOpen = !$('[data-panel="revision"]').hidden;
    if (revOpen && /^[1-4]$/.test(e.key)) { answer($$(".kt-option", quizBox)[+e.key - 1]); return; }
    if (revOpen && e.key === " " && $(".kt-flashcard")) { e.preventDefault(); $(".kt-flashcard").classList.toggle("flipped"); return; }
    if (e.key === "ArrowLeft" && L.prevUrl) location.href = L.prevUrl;
    if (e.key === "ArrowRight" && L.nextUrl) {
      if (L.done || !L.nextLocked) location.href = L.nextUrl; else KTUi.toast("Valide ce module pour débloquer le suivant.");
    }
  });

  const savedTab = store.get("kt_tab_" + L.moduleId, "lecon");
  if (savedTab !== "lecon" && $(`[data-tab="${savedTab}"]`)) openTab(savedTab);
})();
