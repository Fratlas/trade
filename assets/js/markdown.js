/* Rendu Markdown minimal et sûr pour le contenu des modules.
   Tout le texte est échappé avant la mise en forme : aucun HTML saisi
   dans le contenu n'est interprété. */
(function () {
  function esc(value) {
    return String(value == null ? "" : value).replace(/[&<>"']/g, (c) => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
    })[c]);
  }

  function inline(text) {
    let out = esc(text);
    const codes = [];
    out = out.replace(/`([^`]+)`/g, (_, code) => { codes.push(code); return "\u0000" + (codes.length - 1) + "\u0000"; });
    out = out.replace(/\*\*([^*]+?)\*\*/g, '<strong class="kt-term">$1</strong>');
    out = out.replace(/(^|[^*\w])\*([^*\s][^*]*?)\*(?!\w)/g, "$1<em>$2</em>");
    out = out.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');
    out = out.replace(/(^|\s)(https?:\/\/[^\s<]+[^\s<.,;:!?)])/g, '$1<a href="$2" target="_blank" rel="noopener">$2</a>');
    out = out.replace(/\u0000(\d+)\u0000/g, (_, i) => "<code>" + codes[+i] + "</code>");
    return out;
  }

  function slug(text, used) {
    let base = text.toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "")
      .replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "") || "section";
    let id = base, n = 2;
    while (used[id]) id = base + "-" + n++;
    used[id] = true;
    return id;
  }

  function splitRow(line) {
    return line.trim().replace(/^\|/, "").replace(/\|$/, "").split("|").map((c) => c.trim());
  }

  function render(md) {
    const lines = String(md || "").replace(/\r\n?/g, "\n").split("\n");
    const html = [];
    const used = {};
    let i = 0;
    let para = [];
    let taskIndex = 0;

    const flush = () => {
      if (para.length) html.push('<p class="kt-md-block">' + para.map(inline).join("<br>") + "</p>");
      para = [];
    };

    while (i < lines.length) {
      const line = lines[i];
      const trimmed = line.trim();

      if (!trimmed) { flush(); i++; continue; }

      if (/^```/.test(trimmed)) {
        flush();
        const buf = [];
        i++;
        while (i < lines.length && !/^```/.test(lines[i].trim())) buf.push(lines[i++]);
        i++;
        html.push('<pre class="kt-md-pre kt-md-block">' + esc(buf.join("\n")) + "</pre>");
        continue;
      }

      const heading = /^(#{1,4})\s+(.*)$/.exec(trimmed);
      if (heading) {
        flush();
        const level = Math.min(Math.max(heading[1].length, 2), 4);
        const id = slug(heading[2].replace(/\*/g, ""), used);
        html.push(`<h${level} id="${id}" class="kt-md-h kt-md-block">${inline(heading[2])}</h${level}>`);
        i++;
        continue;
      }

      if (/^(-{3,}|\*{3,}|_{3,})$/.test(trimmed)) { flush(); html.push('<hr class="kt-md-hr">'); i++; continue; }

      if (/^>/.test(trimmed)) {
        flush();
        const buf = [];
        while (i < lines.length && /^>/.test(lines[i].trim())) buf.push(lines[i++].trim().replace(/^>\s?/, ""));
        html.push('<blockquote class="kt-md-quote kt-md-block"><span class="kt-md-quote-tag">A retenir</span><p>' + buf.map(inline).join("<br>") + "</p></blockquote>");
        continue;
      }

      if (/^\|/.test(trimmed) && i + 1 < lines.length && /^\|?\s*:?-{2,}/.test(lines[i + 1].trim())) {
        flush();
        const head = splitRow(trimmed);
        i += 2;
        const rows = [];
        while (i < lines.length && /^\|/.test(lines[i].trim())) rows.push(splitRow(lines[i++]));
        html.push('<div class="kt-md-table kt-md-block"><table><thead><tr>' + head.map((c) => "<th>" + inline(c) + "</th>").join("") +
          "</tr></thead><tbody>" + rows.map((r) => "<tr>" + r.map((c) => "<td>" + inline(c) + "</td>").join("") + "</tr>").join("") +
          "</tbody></table></div>");
        continue;
      }

      if (/^([-*+]|\d+[.)])\s+/.test(trimmed)) {
        flush();
        const ordered = /^\d/.test(trimmed);
        const items = [];
        while (i < lines.length && /^\s*([-*+]|\d+[.)])\s+/.test(lines[i])) {
          items.push(lines[i].trim().replace(/^([-*+]|\d+[.)])\s+/, ""));
          i++;
        }
        const isTask = items.every((it) => /^\[( |x|X)\]\s+/.test(it));
        if (isTask) {
          html.push('<ul class="kt-md-tasks kt-md-block">' + items.map((it) => {
            const checked = /^\[(x|X)\]/.test(it);
            const label = it.replace(/^\[( |x|X)\]\s+/, "");
            return `<li><label><input type="checkbox" data-task="${taskIndex++}"${checked ? " checked" : ""}><span>${inline(label)}</span></label></li>`;
          }).join("") + "</ul>");
        } else {
          const tag = ordered ? "ol" : "ul";
          html.push(`<${tag} class="kt-md-list kt-md-block">` + items.map((it) => "<li>" + inline(it) + "</li>").join("") + `</${tag}>`);
        }
        continue;
      }

      para.push(trimmed);
      i++;
    }
    flush();
    return html.join("\n");
  }

  window.KTMarkdown = { render, inline, escape: esc };
})();
