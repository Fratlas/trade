/**
 * KTVoiceCall — Appel vocal temps réel via OpenAI Realtime API (WebRTC)
 *
 * Utilisation :
 *   KTVoiceCall.init({ role: 'student' | 'admin', onTranscript, onStateChange });
 *   KTVoiceCall.start();
 *   KTVoiceCall.stop();
 *
 * Callbacks :
 *   onTranscript(text, isFinal)   — appelé à chaque fragment ou fin de phrase IA
 *   onStateChange(state)          — 'idle' | 'connecting' | 'listening' | 'speaking' | 'error'
 */
(function () {
  "use strict";

  const REALTIME_BASE_URL = "wss://api.openai.com/v1/realtime";
  const REALTIME_MODEL    = "gpt-4o-realtime-preview-2024-10-01";

  let _options        = {};
  let _pc             = null;   // RTCPeerConnection
  let _dc             = null;   // RTCDataChannel
  let _localStream    = null;   // MediaStream (microphone)
  let _audioEl        = null;   // <audio> pour la réponse vocale IA
  let _state          = "idle";
  let _partialText    = "";

  /* ------------------------------------------------------------------ */
  /*  État                                                                */
  /* ------------------------------------------------------------------ */
  function setState(newState) {
    _state = newState;
    if (typeof _options.onStateChange === "function") {
      _options.onStateChange(newState);
    }
  }

  /* ------------------------------------------------------------------ */
  /*  API publique                                                        */
  /* ------------------------------------------------------------------ */

  /**
   * Initialise le module avec les options.
   * @param {{ role: string, onTranscript: Function, onStateChange: Function }} opts
   */
  function init(opts) {
    _options = Object.assign({ role: "student" }, opts);
  }

  /**
   * Démarre un appel vocal :
   * 1. Demande le micro à l'utilisateur
   * 2. Récupère un token éphémère depuis notre backend PHP
   * 3. Ouvre une RTCPeerConnection vers OpenAI Realtime
   */
  async function start() {
    if (_state !== "idle") return;
    setState("connecting");

    try {
      // 1. Micro
      _localStream = await navigator.mediaDevices.getUserMedia({ audio: true });

      // 2. Token éphémère (notre backend PHP masque la vraie clé)
      const sessionData = await window.KTApi.post("/ai/realtime-session", {
        role: _options.role || "student",
      });
      const ephemeralKey = sessionData.client_secret;
      if (!ephemeralKey) throw new Error("Token éphémère absent de la réponse serveur.");

      // 3. PeerConnection
      _pc = new RTCPeerConnection();

      // Piste audio entrante (réponse vocale de l'IA)
      _audioEl = document.createElement("audio");
      _audioEl.autoplay = true;
      _pc.ontrack = (event) => {
        _audioEl.srcObject = event.streams[0];
      };

      // Piste audio sortante (micro de l'utilisateur)
      _localStream.getTracks().forEach((track) => {
        _pc.addTrack(track, _localStream);
      });

      // DataChannel pour les événements JSON (transcription, etc.)
      _dc = _pc.createDataChannel("oai-events");
      _dc.onmessage = _handleDataChannelMessage;
      _dc.onopen    = () => setState("listening");
      _dc.onclose   = () => { if (_state !== "idle") stop(); };

      // SDP Offer
      const offer = await _pc.createOffer();
      await _pc.setLocalDescription(offer);

      // Envoi de l'offre SDP à OpenAI
      const sdpResponse = await fetch(
        `https://api.openai.com/v1/realtime?model=${encodeURIComponent(REALTIME_MODEL)}`,
        {
          method:  "POST",
          headers: {
            Authorization:  "Bearer " + ephemeralKey,
            "Content-Type": "application/sdp",
          },
          body: offer.sdp,
        }
      );

      if (!sdpResponse.ok) {
        const errText = await sdpResponse.text();
        throw new Error("OpenAI SDP error " + sdpResponse.status + ": " + errText);
      }

      const answerSdp = await sdpResponse.text();
      await _pc.setRemoteDescription({ type: "answer", sdp: answerSdp });

    } catch (err) {
      console.error("[KTVoiceCall] Erreur lors du démarrage:", err);
      _cleanup();
      setState("error");
      if (typeof _options.onError === "function") {
        _options.onError(err.message || String(err));
      }
    }
  }

  /**
   * Stoppe l'appel vocal et libère les ressources.
   */
  function stop() {
    _cleanup();
    setState("idle");
  }

  function getState() {
    return _state;
  }

  /* ------------------------------------------------------------------ */
  /*  Gestion des messages DataChannel                                   */
  /* ------------------------------------------------------------------ */
  function _handleDataChannelMessage(event) {
    let msg;
    try {
      msg = JSON.parse(event.data);
    } catch {
      return;
    }

    switch (msg.type) {
      // Début de la réponse audio IA
      case "response.audio.started":
        setState("speaking");
        _partialText = "";
        break;

      // Delta de transcription (texte partiel en temps réel)
      case "response.audio_transcript.delta":
        _partialText += msg.delta || "";
        if (typeof _options.onTranscript === "function") {
          _options.onTranscript(_partialText, false);
        }
        break;

      // Fin de la transcription IA (phrase complète)
      case "response.audio_transcript.done":
        if (typeof _options.onTranscript === "function") {
          _options.onTranscript(msg.transcript || _partialText, true);
        }
        _partialText = "";
        setState("listening");
        break;

      // L'IA a fini de parler
      case "response.audio.done":
        setState("listening");
        break;

      // Transcription de la voix de l'utilisateur
      case "input_audio_buffer.speech_started":
        // L'utilisateur commence à parler → on peut interrompre l'IA
        if (_state === "speaking") {
          _sendEvent({ type: "response.cancel" });
        }
        setState("listening");
        break;

      // Erreur renvoyée par OpenAI
      case "error":
        console.error("[KTVoiceCall] Erreur OpenAI Realtime:", msg.error);
        if (typeof _options.onError === "function") {
          _options.onError((msg.error && msg.error.message) || "Erreur Realtime API");
        }
        break;

      default:
        break;
    }
  }

  /* ------------------------------------------------------------------ */
  /*  Helpers                                                             */
  /* ------------------------------------------------------------------ */
  function _sendEvent(obj) {
    if (_dc && _dc.readyState === "open") {
      _dc.send(JSON.stringify(obj));
    }
  }

  function _cleanup() {
    if (_localStream) {
      _localStream.getTracks().forEach((t) => t.stop());
      _localStream = null;
    }
    if (_dc) {
      try { _dc.close(); } catch {}
      _dc = null;
    }
    if (_pc) {
      try { _pc.close(); } catch {}
      _pc = null;
    }
    if (_audioEl) {
      _audioEl.srcObject = null;
      _audioEl = null;
    }
    _partialText = "";
  }

  /* ------------------------------------------------------------------ */
  /*  Export global                                                       */
  /* ------------------------------------------------------------------ */
  window.KTVoiceCall = { init, start, stop, getState };
})();
