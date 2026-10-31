(function () {
  const API_BASE = "/api";

  function getCookie(name) {
    const match = document.cookie.match(new RegExp("(?:^|; )" + name.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + "=([^;]*)"));
    return match ? decodeURIComponent(match[1]) : "";
  }

  async function request(path, method, data) {
    const headers = { "Content-Type": "application/json" };
    const token = getCookie("katsuki_token") || localStorage.getItem("katsuki_token") || "";
    if (token) {
      headers.Authorization = "Bearer " + token;
    }

    const config = { method, headers, credentials: "same-origin" };
    if (data !== undefined && data !== null) {
      config.body = JSON.stringify(data);
    }

    const response = await fetch(API_BASE + path, config);
    const raw = await response.text();
    let body = null;
    try {
      body = raw ? JSON.parse(raw) : null;
    } catch (error) {
      body = null;
    }

    if (!response.ok) {
      throw new Error((body && (body.error || body.message)) || "Erreur serveur (" + response.status + ")");
    }
    return body;
  }

  async function get(path) {
    return request(path, "GET");
  }

  async function post(path, data) {
    return request(path, "POST", data);
  }

  async function postForm(path, formData) {
    const token = getCookie("katsuki_token") || localStorage.getItem("katsuki_token") || "";
    const headers = {};
    if (token) {
      headers.Authorization = "Bearer " + token;
    }
    const response = await fetch(API_BASE + path, {
      method: "POST",
      headers,
      credentials: "same-origin",
      body: formData
    });
    const raw = await response.text();
    let body = null;
    try {
      body = raw ? JSON.parse(raw) : null;
    } catch (error) {
      body = null;
    }
    if (!response.ok) {
      throw new Error((body && (body.error || body.message)) || "Erreur serveur (" + response.status + ")");
    }
    return body;
  }

  window.KTApi = { get, post, postForm, request };
})();
