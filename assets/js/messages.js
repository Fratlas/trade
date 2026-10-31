(function () {
  function escapeHtml(value) {
    return String(value == null ? "" : value).replace(/[&<>"']/g, (char) => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
    })[char]);
  }

  function formatTime(value) {
    if (!value) return "";
    const date = new Date(value.replace(" ", "T"));
    return Number.isNaN(date.getTime()) ? value : date.toLocaleString("fr-FR", { day: "2-digit", month: "2-digit", hour: "2-digit", minute: "2-digit" });
  }

  function renderMessages(container, messages, currentUserId) {
    if (!messages || !messages.length) {
      container.innerHTML = '<p class="kt-muted" style="text-align:center;padding:24px">Aucun message. Envoyez le premier.</p>';
      return;
    }
    container.innerHTML = messages.map((msg) => {
      const mine = Number(msg.sender_id) === Number(currentUserId);
      const cls = mine ? "kt-msg mine" : "kt-msg theirs";
      const img = msg.image_url ? `<a href="${escapeHtml(msg.image_url)}" target="_blank" rel="noopener"><img class="kt-msg-image" src="${escapeHtml(msg.image_url)}" alt="Image"></a>` : "";
      const text = msg.body ? `<p class="kt-msg-text">${escapeHtml(msg.body)}</p>` : "";
      return `<article class="${cls}"><div class="kt-msg-bubble">${text}${img}<span class="kt-msg-meta">${escapeHtml(msg.sender_name)} · ${formatTime(msg.created_at)}</span></div></article>`;
    }).join("");
    container.scrollTop = container.scrollHeight;
  }

  function bindPreview(input, preview) {
    if (!input || !preview) return;
    input.addEventListener("change", () => {
      const file = input.files && input.files[0];
      if (!file) {
        preview.innerHTML = "";
        return;
      }
      const url = URL.createObjectURL(file);
      preview.innerHTML = `<img src="${url}" alt="Apercu" class="kt-msg-preview">`;
    });
  }

  window.KTMessages = { renderMessages, bindPreview, formatTime, escapeHtml };
})();
