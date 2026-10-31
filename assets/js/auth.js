(function () {
  function getCookie(name) {
    const match = document.cookie.match(new RegExp("(?:^|; )" + name.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + "=([^;]*)"));
    return match ? decodeURIComponent(match[1]) : "";
  }

  function hasToken() {
    return !!(getCookie("katsuki_token") || localStorage.getItem("katsuki_token"));
  }

  function checkAuth(role) {
    if (!hasToken()) {
      window.location.href = "/auth/login.php";
      return false;
    }
    return true;
  }

  function logout() {
    document.cookie = "katsuki_token=; Max-Age=0; path=/";
    localStorage.removeItem("katsuki_token");
    window.location.href = "/auth/login.php";
  }

  window.KTAuth = { checkAuth, logout, hasToken };
})();
