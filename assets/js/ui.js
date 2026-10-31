(function () {
  function toast(message) {
    const node = document.createElement("div");
    node.className = "kt-toast";
    node.textContent = message;
    document.body.appendChild(node);
    setTimeout(() => node.remove(), 3600);
  }

  function percent(done, total) {
    return total ? Math.round((Number(done || 0) / Number(total || 1)) * 100) : 0;
  }

  function escapeHtml(value) {
    return String(value == null ? "" : value).replace(/[&<>"']/g, (char) => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
    })[char]);
  }

  function formatDate(value) {
    if (!value) return "-";
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString("fr-FR");
  }

  function sidebar(role, active) {
    const student = [
      ["dashboard.php", "Tableau de bord"],
      ["course.php", "Mes formations"],
      ["progress.php", "Ma progression"],
      ["quiz.php", "Examens"],
      ["messages.php", "Messagerie"],
      ["assistant.php", "Assistant IA"]
    ];
    const admin = [
      ["dashboard.php", "Dashboard"],
      ["students.php", "Etudiants"],
      ["courses.php", "Formations"],
      ["resources.php", "Ressources"],
      ["quizzes.php", "Examens"],
      ["payments.php", "Paiements"],
      ["modules.php", "Modules"],
      ["messages.php", "Messagerie"],
      ["assistant.php", "Assistant IA"]
    ];
    return (role === "admin" ? admin : student).map(([href, label]) => {
      const cls = active === href ? "active" : "";
      return `<a class="${cls}" href="/${role}/${href}">${label}</a>`;
    }).join("");
  }

  window.KTUi = { toast, percent, escapeHtml, formatDate, sidebar };
})();
