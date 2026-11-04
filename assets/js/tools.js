/* Outils interactifs de la formation : chaque outil se monte dans un élément
   via KTTools.mount(element, id). Aucun appel serveur : tout est calculé
   dans le navigateur (le journal est gardé dans localStorage). */
(function () {
  const fmt = (n, d = 2) => Number.isFinite(n) ? n.toLocaleString("fr-FR", { minimumFractionDigits: d, maximumFractionDigits: d }) : "—";
  const num = (el) => parseFloat(String(el.value).replace(",", ".")) || 0;
  const esc = (v) => String(v == null ? "" : v).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" })[c]);
  const store = {
    get(key, fallback) { try { const v = localStorage.getItem(key); return v ? JSON.parse(v) : fallback; } catch (e) { return fallback; } },
    set(key, value) { try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* stockage indisponible */ } }
  };
  const field = (id, label, value, attrs = "") =>
    `<label class="kt-field"><span class="kt-label">${label}</span><input class="kt-input kt-num" data-k="${id}" value="${value}" inputmode="decimal" ${attrs}></label>`;

  /* ── Calculateur de taille de lot ─────────────────────────────────── */
  function lot(root) {
    root.innerHTML = `
      <div class="kt-tool-grid">
        <div class="kt-form">
          ${field("capital", "Capital du compte ($)", 200)}
          <label class="kt-field"><span class="kt-label">Risque par trade : <b data-o="riskLabel">1 %</b></span>
            <input type="range" data-k="risk" min="0.25" max="5" step="0.25" value="1"></label>
          ${field("sl", "Stop loss (pips)", 15)}
          <label class="kt-field"><span class="kt-label">Instrument</span>
            <select class="kt-select" data-k="pip">
              <option value="10">EUR/USD, GBP/USD… (10 $ / pip / lot)</option>
              <option value="10">XAU/USD — or (10 $ / pip / lot)</option>
              <option value="6.5">USD/JPY (≈ 6,5 $ / pip / lot)</option>
              <option value="1">Indice synthétique (1 $ / point / lot)</option>
            </select></label>
        </div>
        <div class="kt-tool-out">
          <div class="kt-kpi"><span>Montant risqué</span><strong class="kt-num" data-o="riskUsd"></strong></div>
          <div class="kt-kpi accent"><span>Lot à utiliser</span><strong class="kt-num" data-o="lot"></strong></div>
          <div class="kt-kpi"><span>Lot exact calculé</span><strong class="kt-num" data-o="exact"></strong></div>
          <ol class="kt-steps" data-o="steps"></ol>
          <p class="kt-tool-warn" data-o="warn" hidden></p>
        </div>
      </div>`;
    const q = (k) => root.querySelector(`[data-k="${k}"]`);
    const o = (k) => root.querySelector(`[data-o="${k}"]`);
    function update() {
      const capital = num(q("capital")), risk = num(q("risk")), sl = num(q("sl")), pip = num(q("pip"));
      o("riskLabel").textContent = fmt(risk, 2).replace(/,00$/, "") + " %";
      const riskUsd = capital * risk / 100;
      const exact = sl > 0 && pip > 0 ? riskUsd / (sl * pip) : 0;
      const rounded = Math.max(0.01, Math.floor(exact * 100) / 100);
      const realRisk = rounded * sl * pip;
      o("riskUsd").textContent = fmt(riskUsd) + " $";
      o("exact").textContent = fmt(exact, 3);
      o("lot").textContent = fmt(rounded, 2);
      o("steps").innerHTML = `
        <li>Risque = ${fmt(risk, 2)} % × ${fmt(capital)} $ = <b>${fmt(riskUsd)} $</b></li>
        <li>Valeur du pip pour 0,01 lot = <b>${fmt(pip / 100, 3)} $</b></li>
        <li>${fmt(sl, 1)} pips × ${fmt(pip / 100, 3)} $ = ${fmt(sl * pip / 100, 3)} $ pour 0,01 lot</li>
        <li>${fmt(riskUsd)} $ ÷ ${fmt(sl * pip / 100, 3)} $ × 0,01 = <b>${fmt(exact, 3)} lot</b> → arrondi à <b>${fmt(rounded, 2)}</b></li>`;
      const warn = o("warn");
      if (exact < 0.01 && exact > 0) {
        warn.hidden = false;
        warn.textContent = `Attention : même le lot minimum (0,01) risque ${fmt(realRisk)} $, soit ${fmt(realRisk / capital * 100, 1)} % du compte. Réduis ton stop loss ou augmente ton capital.`;
      } else warn.hidden = true;
    }
    root.addEventListener("input", update);
    update();
  }

  /* ── Ratio risque / rendement ─────────────────────────────────────── */
  function rr(root) {
    root.innerHTML = `
      <div class="kt-seg" role="tablist" data-k="dir">
        <button type="button" class="on" data-v="buy">Achat (Buy)</button><button type="button" data-v="sell">Vente (Sell)</button>
      </div>
      <div class="kt-tool-grid">
        <div class="kt-form">
          ${field("entry", "Prix d'entrée", "1.08500")}
          ${field("sl", "Stop loss", "1.08350")}
          ${field("tp", "Take profit", "1.08950")}
        </div>
        <div class="kt-tool-out">
          <div class="kt-rr-visual" data-o="visual"></div>
          <div class="kt-kpi accent"><span>Ratio R:R</span><strong class="kt-num" data-o="ratio"></strong></div>
          <div class="kt-kpi"><span>Taux de réussite minimum pour être rentable</span><strong class="kt-num" data-o="be"></strong></div>
          <p class="kt-tool-warn" data-o="warn" hidden></p>
        </div>
      </div>`;
    let dir = "buy";
    const q = (k) => root.querySelector(`[data-k="${k}"]`);
    const o = (k) => root.querySelector(`[data-o="${k}"]`);
    q("dir").addEventListener("click", (e) => {
      const b = e.target.closest("button"); if (!b) return;
      dir = b.dataset.v;
      q("dir").querySelectorAll("button").forEach((x) => x.classList.toggle("on", x === b));
      const entry = num(q("entry")), sl = num(q("sl")), tp = num(q("tp"));
      q("sl").value = (2 * entry - sl).toFixed(5); q("tp").value = (2 * entry - tp).toFixed(5);
      update();
    });
    function update() {
      const entry = num(q("entry")), sl = num(q("sl")), tp = num(q("tp"));
      const risk = dir === "buy" ? entry - sl : sl - entry;
      const reward = dir === "buy" ? tp - entry : entry - tp;
      const warn = o("warn");
      if (risk <= 0 || reward <= 0) {
        warn.hidden = false;
        warn.textContent = dir === "buy" ? "En achat, le stop loss doit être sous l'entrée et le take profit au-dessus." : "En vente, le stop loss doit être au-dessus de l'entrée et le take profit en dessous.";
        o("ratio").textContent = "—"; o("be").textContent = "—"; o("visual").innerHTML = ""; return;
      }
      warn.hidden = true;
      const r = reward / risk;
      o("ratio").textContent = "1 : " + fmt(r, 2);
      o("be").textContent = fmt(100 / (1 + r), 1) + " %";
      const total = risk + reward;
      const top = dir === "buy" ? reward : risk;
      o("visual").innerHTML = `
        <div class="zone ${dir === "buy" ? "win" : "loss"}" style="flex:${top / total}"><span>${dir === "buy" ? "TP" : "SL"}</span></div>
        <div class="entry"><span>Entrée</span></div>
        <div class="zone ${dir === "buy" ? "loss" : "win"}" style="flex:${(total - top) / total}"><span>${dir === "buy" ? "SL" : "TP"}</span></div>`;
    }
    root.addEventListener("input", update);
    update();
  }

  /* ── Constructeur de bougie japonaise ─────────────────────────────── */
  function candle(root) {
    const presets = {
      "Marubozu haussier": [12, 92, 94, 10], "Doji": [50, 51, 85, 15], "Marteau": [70, 78, 80, 15],
      "Étoile filante": [30, 22, 85, 20], "Toupie": [45, 55, 80, 20], "Marubozu baissier": [90, 12, 92, 10]
    };
    root.innerHTML = `
      <div class="kt-chips" data-k="presets">${Object.keys(presets).map((p) => `<button type="button" class="kt-chip">${p}</button>`).join("")}</div>
      <div class="kt-tool-grid">
        <div class="kt-form">
          ${["open:Ouverture (O)", "close:Fermeture (C)", "high:Plus haut (H)", "low:Plus bas (L)"].map((s) => {
            const [k, l] = s.split(":");
            return `<label class="kt-field"><span class="kt-label">${l} : <b class="kt-num" data-o="${k}V"></b></span><input type="range" min="0" max="100" data-k="${k}"></label>`;
          }).join("")}
        </div>
        <div class="kt-candle-stage">
          <svg viewBox="0 0 220 210" data-o="svg" role="img" aria-label="Bougie japonaise"></svg>
          <div class="kt-candle-info"><strong data-o="name"></strong><p data-o="desc"></p></div>
        </div>
      </div>`;
    const q = (k) => root.querySelector(`[data-k="${k}"]`);
    const o = (k) => root.querySelector(`[data-o="${k}"]`);
    const set = ([op, cl, hi, lo]) => { q("open").value = op; q("close").value = cl; q("high").value = hi; q("low").value = lo; draw(); };
    q("presets").addEventListener("click", (e) => { const b = e.target.closest("button"); if (b) set(presets[b.textContent]); });
    root.addEventListener("input", (e) => {
      const k = e.target.dataset.k;
      let op = +q("open").value, cl = +q("close").value, hi = +q("high").value, lo = +q("low").value;
      if (k === "high" && hi < Math.max(op, cl)) hi = Math.max(op, cl);
      if (k === "low" && lo > Math.min(op, cl)) lo = Math.min(op, cl);
      hi = Math.max(hi, op, cl); lo = Math.min(lo, op, cl);
      q("high").value = hi; q("low").value = lo;
      draw();
    });
    function classify(op, cl, hi, lo) {
      const range = Math.max(hi - lo, 0.0001), body = Math.abs(cl - op);
      const upper = hi - Math.max(op, cl), lower = Math.min(op, cl) - lo;
      const bull = cl >= op;
      if (body <= range * 0.1) {
        if (upper < range * 0.15) return ["Doji libellule", "Les vendeurs ont poussé le prix très bas mais les acheteurs l'ont ramené à l'ouverture. Possible retournement haussier en bas de tendance."];
        if (lower < range * 0.15) return ["Doji pierre tombale", "Les acheteurs ont échoué à tenir les hauts. Possible retournement baissier en haut de tendance."];
        return ["Doji", "Ouverture ≈ fermeture : indécision totale entre acheteurs et vendeurs. Attends la bougie suivante pour confirmer."];
      }
      if (body >= range * 0.85) return [bull ? "Marubozu haussier" : "Marubozu baissier", (bull ? "Les acheteurs" : "Les vendeurs") + " ont dominé toute la période, presque sans mèches : signal de force directionnelle."];
      if (lower >= body * 2 && upper <= body) return ["Marteau (ou pendu)", "Longue mèche basse : le prix a été rejeté vers le haut. En bas de tendance c'est un marteau, signal de retournement haussier."];
      if (upper >= body * 2 && lower <= body) return ["Étoile filante (ou marteau inversé)", "Longue mèche haute : le prix a été rejeté vers le bas. En haut de tendance, signal de retournement baissier."];
      if (body < range * 0.35) return ["Toupie", "Petit corps et mèches des deux côtés : le marché hésite. Souvent une pause avant la suite du mouvement."];
      return [bull ? "Bougie haussière" : "Bougie baissière", (bull ? "Fermeture au-dessus" : "Fermeture en dessous") + " de l'ouverture : " + (bull ? "les acheteurs" : "les vendeurs") + " ont gagné la période."];
    }
    function draw() {
      const op = +q("open").value, cl = +q("close").value, hi = +q("high").value, lo = +q("low").value;
      ["open", "close", "high", "low"].forEach((k) => { o(k + "V").textContent = q(k).value; });
      const y = (v) => 195 - v * 1.8;
      const bull = cl >= op;
      const color = bull ? "#34d399" : "#f05252";
      const top = y(Math.max(op, cl)), bottom = y(Math.min(op, cl));
      // Étiquettes de prix espacées d'au moins 13 px pour ne pas se chevaucher.
      const marks = [[hi, "H"], [op, "O"], [cl, "C"], [lo, "L"]].filter((m, i) => !(m[1] === "C" && op === cl)).sort((a, b) => b[0] - a[0]);
      let lastY = -Infinity;
      const labels = marks.map(([v, t]) => { const ty = Math.max(y(v) + 4, lastY + 13); lastY = ty; return [v, t, ty]; });
      const label = (v, t) => { const ty = labels.find((l) => l[1] === t)[2]; return `<line x1="122" x2="150" y1="${y(v)}" y2="${y(v)}" stroke="rgba(255,255,255,.18)" stroke-dasharray="3 3"/><text x="156" y="${ty}" fill="#8f8d87" font-size="11" font-family="JetBrains Mono, monospace">${t} ${v}</text>`; };
      o("svg").innerHTML = `
        <line x1="85" x2="85" y1="${y(hi)}" y2="${y(lo)}" stroke="${color}" stroke-width="3" stroke-linecap="round"/>
        <rect x="58" y="${top}" width="54" height="${Math.max(bottom - top, 2)}" rx="4" fill="${color}" fill-opacity="${bull ? 0.85 : 0.9}"/>
        ${labels.map(([v, t]) => label(v, t)).join("")}`;
      const [name, desc] = classify(op, cl, hi, lo);
      o("name").textContent = name; o("desc").textContent = desc;
    }
    set(presets["Marteau"]);
  }

  /* ── Sessions de marché en direct ─────────────────────────────────── */
  function sessions(root) {
    const list = [
      { name: "Sydney", tz: "Australia/Sydney", open: 7, close: 16 },
      { name: "Tokyo", tz: "Asia/Tokyo", open: 9, close: 18 },
      { name: "Londres", tz: "Europe/London", open: 8, close: 17 },
      { name: "New York", tz: "America/New_York", open: 8, close: 17 }
    ];
    const zone = store.get("kt_rdc_zone", "Africa/Kinshasa");
    root.innerHTML = `
      <div class="kt-seg" data-k="zone">
        <button type="button" data-v="Africa/Kinshasa">Kinshasa (UTC+1)</button><button type="button" data-v="Africa/Lubumbashi">Lubumbashi (UTC+2)</button>
      </div>
      <div class="kt-clock"><span class="kt-num" data-o="time"></span><small data-o="day"></small></div>
      <div class="kt-sessions" data-o="rows"></div>
      <div class="kt-session-axis"><span>00h</span><span>06h</span><span>12h</span><span>18h</span><span>24h</span></div>
      <p class="kt-muted kt-small" data-o="advice"></p>`;
    let current = zone;
    const seg = root.querySelector('[data-k="zone"]');
    const paint = () => seg.querySelectorAll("button").forEach((b) => b.classList.toggle("on", b.dataset.v === current));
    seg.addEventListener("click", (e) => { const b = e.target.closest("button"); if (!b) return; current = b.dataset.v; store.set("kt_rdc_zone", current); paint(); tick(); });
    paint();
    function parts(tz, date) {
      const p = {};
      new Intl.DateTimeFormat("en-GB", { timeZone: tz, hour12: false, weekday: "short", year: "numeric", month: "2-digit", day: "2-digit", hour: "2-digit", minute: "2-digit", second: "2-digit" })
        .formatToParts(date).forEach((x) => { p[x.type] = x.value; });
      return p;
    }
    function offsetMin(tz, date) {
      const p = parts(tz, date);
      const asUtc = Date.UTC(+p.year, +p.month - 1, +p.day, +p.hour % 24, +p.minute, +p.second);
      return Math.round((asUtc - date.getTime()) / 60000);
    }
    function tick() {
      if (!root.isConnected) { clearInterval(timer); return; }
      const now = new Date();
      const local = parts(current, now);
      const localMin = (+local.hour % 24) * 60 + +local.minute;
      root.querySelector('[data-o="time"]').textContent = `${local.hour}:${local.minute}:${local.second}`;
      root.querySelector('[data-o="day"]').textContent = now.toLocaleDateString("fr-FR", { timeZone: current, weekday: "long", day: "numeric", month: "long" });
      const weekend = ["Sat", "Sun"].includes(local.weekday);
      const openNow = [];
      root.querySelector('[data-o="rows"]').innerHTML = list.map((s) => {
        const shift = offsetMin(current, now) - offsetMin(s.tz, now);
        const start = ((s.open * 60 + shift) % 1440 + 1440) % 1440;
        const len = (s.close - s.open) * 60;
        const cityP = parts(s.tz, now);
        const cityMin = (+cityP.hour % 24) * 60 + +cityP.minute;
        const isOpen = !["Sat", "Sun"].includes(cityP.weekday) && cityMin >= s.open * 60 && cityMin < s.close * 60;
        if (isOpen) openNow.push(s.name);
        const seg1 = Math.min(len, 1440 - start), seg2 = len - seg1;
        const hh = (m) => String(Math.floor(m / 60) % 24).padStart(2, "0") + "h" + String(m % 60).padStart(2, "0");
        return `<div class="kt-session ${isOpen ? "open" : ""}">
          <div class="kt-session-name"><i></i>${s.name}<small class="kt-num">${hh(start)} – ${hh((start + len) % 1440)}</small></div>
          <div class="kt-session-track">
            <span style="left:${start / 14.4}%;width:${seg1 / 14.4}%"></span>
            ${seg2 > 0 ? `<span style="left:0;width:${seg2 / 14.4}%"></span>` : ""}
            <b class="now" style="left:${localMin / 14.4}%"></b>
          </div></div>`;
      }).join("");
      const advice = root.querySelector('[data-o="advice"]');
      if (weekend) advice.textContent = "Week-end : le Forex est fermé. Profite-en pour faire ton backtesting et relire ton journal.";
      else if (openNow.includes("Londres") && openNow.includes("New York")) advice.textContent = "Chevauchement Londres / New York en cours : volatilité maximale, les meilleurs mouvements de la journée.";
      else if (openNow.length) advice.textContent = "Ouvert maintenant : " + openNow.join(", ") + ".";
      else advice.textContent = "Aucune grande session ouverte : marché calme, spreads souvent plus larges.";
    }
    const timer = setInterval(tick, 1000);
    tick();
  }

  /* ── Simulateur de série de trades ────────────────────────────────── */
  function simulator(root) {
    root.innerHTML = `
      <div class="kt-tool-grid">
        <div class="kt-form">
          ${field("capital", "Capital de départ ($)", 1000)}
          <label class="kt-field"><span class="kt-label">Risque par trade : <b data-o="riskV"></b></span><input type="range" data-k="risk" min="0.5" max="10" step="0.5" value="1"></label>
          <label class="kt-field"><span class="kt-label">Ratio R:R : <b data-o="rrV"></b></span><input type="range" data-k="rr" min="0.5" max="4" step="0.5" value="2"></label>
          <label class="kt-field"><span class="kt-label">Taux de réussite : <b data-o="winV"></b></span><input type="range" data-k="win" min="20" max="80" step="5" value="45"></label>
          <label class="kt-field"><span class="kt-label">Nombre de trades : <b data-o="nV"></b></span><input type="range" data-k="n" min="20" max="200" step="10" value="100"></label>
          <button type="button" class="kt-btn primary" data-k="run">Relancer la simulation</button>
        </div>
        <div class="kt-tool-out">
          <svg class="kt-equity" viewBox="0 0 400 180" preserveAspectRatio="none" data-o="chart"></svg>
          <div class="kt-kpis">
            <div class="kt-kpi accent"><span>Capital final</span><strong class="kt-num" data-o="final"></strong></div>
            <div class="kt-kpi"><span>Drawdown max</span><strong class="kt-num" data-o="dd"></strong></div>
            <div class="kt-kpi"><span>Pertes d'affilée max</span><strong class="kt-num" data-o="streak"></strong></div>
          </div>
          <p class="kt-muted kt-small" data-o="lesson"></p>
        </div>
      </div>`;
    const q = (k) => root.querySelector(`[data-k="${k}"]`);
    const o = (k) => root.querySelector(`[data-o="${k}"]`);
    let seed = Date.now() % 100000;
    const rand = () => { seed = (seed * 16807) % 2147483647; return (seed - 1) / 2147483646; };
    function run() {
      const capital = num(q("capital")) || 1000, risk = +q("risk").value / 100, ratio = +q("rr").value, win = +q("win").value / 100, n = +q("n").value;
      o("riskV").textContent = q("risk").value + " %"; o("rrV").textContent = "1 : " + q("rr").value; o("winV").textContent = q("win").value + " %"; o("nV").textContent = n;
      const start = seed;
      let eq = capital, peak = capital, maxDd = 0, streak = 0, maxStreak = 0;
      const pts = [eq];
      for (let i = 0; i < n; i++) {
        const stake = eq * risk;
        if (rand() < win) { eq += stake * ratio; streak = 0; } else { eq -= stake; streak++; maxStreak = Math.max(maxStreak, streak); }
        peak = Math.max(peak, eq); maxDd = Math.max(maxDd, (peak - eq) / peak);
        pts.push(eq);
      }
      seed = start;
      const max = Math.max(...pts), min = Math.min(...pts);
      const x = (i) => (i / n) * 400, yv = (v) => 170 - ((v - min) / ((max - min) || 1)) * 160;
      const line = pts.map((v, i) => `${x(i).toFixed(1)},${yv(v).toFixed(1)}`).join(" ");
      const up = eq >= capital;
      o("chart").innerHTML = `
        <line x1="0" x2="400" y1="${yv(capital)}" y2="${yv(capital)}" stroke="rgba(255,255,255,.18)" stroke-dasharray="4 4"/>
        <polygon points="0,180 ${line} 400,180" fill="${up ? "rgba(52,211,153,.10)" : "rgba(240,82,82,.10)"}"/>
        <polyline points="${line}" fill="none" stroke="${up ? "#34d399" : "#f05252"}" stroke-width="2" vector-effect="non-scaling-stroke"/>`;
      o("final").textContent = fmt(eq, 0) + " $";
      o("final").style.color = up ? "var(--success)" : "var(--danger)";
      o("dd").textContent = fmt(maxDd * 100, 1) + " %";
      o("streak").textContent = maxStreak;
      const expectancy = win * ratio - (1 - win);
      o("lesson").textContent = expectancy > 0
        ? `Espérance positive (+${fmt(expectancy, 2)} R par trade) : sur la durée, la stratégie gagne — même avec ${maxStreak} pertes d'affilée. Le plus dur, c'est de rester discipliné pendant ces séries.`
        : `Espérance négative (${fmt(expectancy, 2)} R par trade) : avec ce taux de réussite et ce R:R, le compte s'érode à long terme. Il faut un meilleur R:R ou un meilleur taux de réussite.`;
    }
    root.addEventListener("input", run);
    q("run").addEventListener("click", () => { seed = (Date.now() + Math.floor(Math.random() * 1e6)) % 2147483646 + 1; run(); });
    run();
  }

  /* ── Drawdown et récupération ─────────────────────────────────────── */
  function drawdown(root) {
    root.innerHTML = `
      <label class="kt-field"><span class="kt-label">Perte subie : <b class="kt-num" data-o="lossV"></b></span><input type="range" data-k="loss" min="0" max="90" step="5" value="30"></label>
      <div class="kt-dd-bars">
        <div><span>Perte</span><i class="loss" data-o="lossBar"></i><b class="kt-num" data-o="lossT"></b></div>
        <div><span>Gain nécessaire</span><i class="gain" data-o="gainBar"></i><b class="kt-num" data-o="gainT"></b></div>
      </div>
      <p class="kt-muted kt-small" data-o="txt"></p>`;
    const q = root.querySelector('[data-k="loss"]');
    const o = (k) => root.querySelector(`[data-o="${k}"]`);
    function update() {
      const l = +q.value / 100, g = l >= 1 ? Infinity : 1 / (1 - l) - 1;
      o("lossV").textContent = q.value + " %";
      o("lossT").textContent = "-" + q.value + " %";
      o("gainT").textContent = "+" + fmt(g * 100, 0) + " %";
      // Échelle commune aux deux barres : la plus grande valeur (au moins 100 %) remplit la barre.
      const scale = Math.max(g * 100, 100);
      o("lossBar").style.width = (l * 100) / scale * 100 + "%";
      o("gainBar").style.width = (g * 100) / scale * 100 + "%";
      o("txt").textContent = `Si ton compte de 1 000 $ tombe à ${fmt(1000 * (1 - l), 0)} $, il te faut +${fmt(g * 100, 0)} % juste pour revenir au départ. C'est pour ça qu'on protège le capital avant de chercher le gain.`;
    }
    q.addEventListener("input", update);
    update();
  }

  /* ── Calculateur de spread ────────────────────────────────────────── */
  function spread(root) {
    root.innerHTML = `
      <div class="kt-tool-grid">
        <div class="kt-form">
          <label class="kt-field"><span class="kt-label">Instrument</span>
            <select class="kt-select" data-k="pip">
              <option value="0.0001|10">EUR/USD, GBP/USD… (pip = 0,0001)</option>
              <option value="0.01|6.5">USD/JPY (pip = 0,01)</option>
              <option value="0.1|10">XAU/USD — or (pip = 0,1)</option>
            </select></label>
          ${field("bid", "Prix Bid (vente)", "1.08500")}
          ${field("ask", "Prix Ask (achat)", "1.08515")}
          ${field("lot", "Taille de lot", "0.01")}
        </div>
        <div class="kt-tool-out">
          <div class="kt-kpi accent"><span>Spread</span><strong class="kt-num" data-o="pips"></strong></div>
          <div class="kt-kpi"><span>Coût à l'ouverture</span><strong class="kt-num" data-o="cost"></strong></div>
          <p class="kt-muted kt-small" data-o="txt"></p>
        </div>
      </div>`;
    const q = (k) => root.querySelector(`[data-k="${k}"]`);
    const o = (k) => root.querySelector(`[data-o="${k}"]`);
    q("pip").addEventListener("change", () => {
      const presets = { "0.0001": ["1.08500", "1.08515"], "0.01": ["151.200", "151.215"], "0.1": ["2650.10", "2650.40"] };
      const [b, a] = presets[q("pip").value.split("|")[0]]; q("bid").value = b; q("ask").value = a; update();
    });
    function update() {
      const [size, value] = q("pip").value.split("|").map(Number);
      const pips = (num(q("ask")) - num(q("bid"))) / size;
      const cost = pips * value * num(q("lot"));
      o("pips").textContent = fmt(pips, 1) + " pips";
      o("cost").textContent = fmt(cost) + " $";
      o("txt").textContent = pips < 0 ? "Le prix Ask doit être supérieur au prix Bid." : `Dès l'ouverture, ton trade démarre à -${fmt(cost)} $ : le prix doit d'abord parcourir ${fmt(pips, 1)} pips en ta faveur pour atteindre zéro. Comme le cambiste qui achète à 2 800 FC et revend à 2 850 FC.`;
    }
    root.addEventListener("input", update);
    update();
  }

  /* ── Convertisseur de time frames ─────────────────────────────────── */
  function timeframe(root) {
    const tfs = [["MN", 31680], ["W1", 7200], ["D1", 1440], ["H4", 240], ["H1", 60], ["M30", 30], ["M15", 15], ["M5", 5], ["M1", 1]];
    const opts = (sel) => tfs.map(([n]) => `<option${n === sel ? " selected" : ""}>${n}</option>`).join("");
    root.innerHTML = `
      <div class="kt-tf">
        <span>1 bougie</span><select class="kt-select" data-k="big">${opts("D1")}</select>
        <span>=</span><strong class="kt-num" data-o="count"></strong>
        <span>bougies</span><select class="kt-select" data-k="small">${opts("H1")}</select>
      </div>
      <div class="kt-tf-blocks" data-o="blocks"></div>
      <p class="kt-muted kt-small">Semaine de trading = 5 jours, mois ≈ 22 jours de cotation (comme dans le tableau du module).</p>`;
    const q = (k) => root.querySelector(`[data-k="${k}"]`);
    function update() {
      const big = tfs.find((t) => t[0] === q("big").value)[1], small = tfs.find((t) => t[0] === q("small").value)[1];
      const count = big / small;
      root.querySelector('[data-o="count"]').textContent = count >= 1 ? fmt(count, count % 1 ? 1 : 0) : "—";
      const shown = Math.min(Math.round(count), 96);
      root.querySelector('[data-o="blocks"]').innerHTML = count >= 1
        ? Array.from({ length: shown }, (_, i) => `<i style="animation-delay:${i * 8}ms"></i>`).join("") + (count > 96 ? `<em>+ ${fmt(count - 96, 0)}</em>` : "")
        : '<em>Choisis un time frame plus petit à droite.</em>';
    }
    root.addEventListener("change", update);
    update();
  }

  /* ── Mini journal de trading ──────────────────────────────────────── */
  function journal(root) {
    const KEY = "kt_journal_v1";
    root.innerHTML = `
      <form class="kt-journal-form" data-k="form">
        <input class="kt-input" name="pair" placeholder="Paire (ex. EUR/USD)" required maxlength="20">
        <select class="kt-select" name="dir"><option value="Achat">Achat</option><option value="Vente">Vente</option></select>
        <input class="kt-input kt-num" name="r" placeholder="Résultat en R (ex. 2 ou -1)" required inputmode="decimal">
        <input class="kt-input" name="note" placeholder="Pourquoi ce trade ? (setup, émotion…)" maxlength="140">
        <button class="kt-btn primary" type="submit">Ajouter</button>
      </form>
      <div class="kt-kpis" data-o="stats"></div>
      <div class="kt-md-table"><table><thead><tr><th>Date</th><th>Paire</th><th>Sens</th><th>R</th><th>Note</th><th></th></tr></thead><tbody data-o="rows"></tbody></table></div>
      <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap"><button type="button" class="kt-btn sm" data-k="csv">Exporter en CSV</button></div>`;
    let rows = store.get(KEY, []);
    const o = (k) => root.querySelector(`[data-o="${k}"]`);
    function render() {
      const n = rows.length, wins = rows.filter((r) => r.r > 0).length, total = rows.reduce((a, r) => a + r.r, 0);
      o("stats").innerHTML = `
        <div class="kt-kpi"><span>Trades</span><strong class="kt-num">${n}</strong></div>
        <div class="kt-kpi"><span>Taux de réussite</span><strong class="kt-num">${n ? fmt(wins / n * 100, 0) + " %" : "—"}</strong></div>
        <div class="kt-kpi accent"><span>Total</span><strong class="kt-num" style="color:${total >= 0 ? "var(--success)" : "var(--danger)"}">${total >= 0 ? "+" : ""}${fmt(total, 1)} R</strong></div>
        <div class="kt-kpi"><span>Espérance / trade</span><strong class="kt-num">${n ? fmt(total / n, 2) + " R" : "—"}</strong></div>`;
      o("rows").innerHTML = rows.length ? rows.slice().reverse().map((r) => `<tr>
        <td class="kt-num">${esc(r.date)}</td><td>${esc(r.pair)}</td><td>${esc(r.dir)}</td>
        <td class="kt-num" style="color:${r.r >= 0 ? "var(--success)" : "var(--danger)"}">${r.r >= 0 ? "+" : ""}${fmt(r.r, 1)}</td>
        <td>${esc(r.note)}</td><td><button type="button" class="kt-btn ghost sm" data-del="${r.id}" aria-label="Supprimer">✕</button></td></tr>`).join("")
        : '<tr><td colspan="6" class="kt-muted">Aucun trade noté. Commence par tes trades sur compte démo.</td></tr>';
    }
    root.querySelector('[data-k="form"]').addEventListener("submit", (e) => {
      e.preventDefault();
      const f = new FormData(e.currentTarget);
      const r = parseFloat(String(f.get("r")).replace(",", "."));
      if (!Number.isFinite(r)) return;
      rows.push({ id: Date.now(), date: new Date().toLocaleDateString("fr-FR"), pair: String(f.get("pair")).toUpperCase(), dir: f.get("dir"), r, note: f.get("note") || "" });
      store.set(KEY, rows); e.currentTarget.reset(); render();
    });
    root.addEventListener("click", (e) => {
      const del = e.target.closest("[data-del]");
      if (del) { rows = rows.filter((r) => String(r.id) !== del.dataset.del); store.set(KEY, rows); render(); }
      if (e.target.closest('[data-k="csv"]')) {
        const csv = "Date;Paire;Sens;R;Note\n" + rows.map((r) => [r.date, r.pair, r.dir, r.r, '"' + String(r.note).replace(/"/g, '""') + '"'].join(";")).join("\n");
        const a = document.createElement("a");
        a.href = URL.createObjectURL(new Blob(["﻿" + csv], { type: "text/csv" }));
        a.download = "journal-de-trading.csv"; a.click();
      }
    });
    render();
  }

  const registry = {
    lot: { name: "Calculateur de lot", desc: "Quelle taille de position pour risquer exactement X % ?", run: lot },
    rr: { name: "Risque / rendement", desc: "Place entrée, SL et TP : obtiens ton R:R.", run: rr },
    candle: { name: "Constructeur de bougie", desc: "Bouge O, H, L, C et reconnais la figure.", run: candle },
    sessions: { name: "Sessions en direct", desc: "Quelles sessions sont ouvertes maintenant en RDC ?", run: sessions },
    simulator: { name: "Simulateur de trades", desc: "Vois l'effet du risque et du R:R sur 100 trades.", run: simulator },
    drawdown: { name: "Drawdown", desc: "Combien faut-il regagner après une perte ?", run: drawdown },
    spread: { name: "Spread", desc: "Le coût caché de chaque ouverture de trade.", run: spread },
    timeframe: { name: "Time frames", desc: "Combien de petites bougies dans une grande ?", run: timeframe },
    journal: { name: "Journal de trading", desc: "Note tes trades et suis tes statistiques.", run: journal }
  };

  // Outils recommandés selon le titre du module.
  const rules = [
    [/spread/, ["spread"]], [/bougie/, ["candle"]], [/time ?frame/, ["timeframe"]],
    [/money management|drawdown/, ["rr", "drawdown"]], [/\blots?\b/, ["lot"]], [/session/, ["sessions"]],
    [/journal/, ["journal"]], [/backtest/, ["simulator", "journal"]], [/plan de trading/, ["lot", "rr"]],
    [/revision|quiz/, ["lot"]], [/psycholog|biais/, ["simulator"]], [/prop firm/, ["drawdown", "simulator"]],
    [/erreurs/, ["drawdown"]], [/compte reel|deposer/, ["lot"]], [/forex|devises/, ["spread", "sessions"]],
    [/demo/, ["journal", "lot"]], [/liquidite/, ["sessions"]], [/broker/, ["spread"]]
  ];
  function suggest(title) {
    const t = String(title || "").toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "");
    const out = [];
    rules.forEach(([re, ids]) => { if (re.test(t)) ids.forEach((id) => { if (!out.includes(id)) out.push(id); }); });
    return out;
  }

  function mount(el, id) {
    const tool = registry[id];
    if (!tool || !el) return;
    el.classList.add("kt-tool-body");
    tool.run(el);
  }

  window.KTTools = { registry, mount, suggest };
})();
