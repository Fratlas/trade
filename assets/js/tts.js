(function () {
  let speaking = false;
  let utterance = null;

  function pickFrenchVoice() {
    const voices = window.speechSynthesis ? speechSynthesis.getVoices() : [];
    return voices.find((v) => v.lang && v.lang.toLowerCase().startsWith("fr"))
      || voices.find((v) => /french|français/i.test(v.name))
      || voices[0]
      || null;
  }

  function read(elementId) {
    if (!window.speechSynthesis) {
      alert("La synthese vocale n est pas supportee par ce navigateur.");
      return;
    }
    const node = document.getElementById(elementId);
    if (!node) return;
    const text = (node.innerText || node.textContent || "").trim();
    if (!text) return;

    if (speaking) {
      speechSynthesis.cancel();
      speaking = false;
      return;
    }

    utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = "fr-FR";
    utterance.rate = 0.95;
    const voice = pickFrenchVoice();
    if (voice) utterance.voice = voice;
    utterance.onend = () => { speaking = false; };
    utterance.onerror = () => { speaking = false; };
    speaking = true;
    speechSynthesis.speak(utterance);
  }

  function stop() {
    if (window.speechSynthesis) {
      speechSynthesis.cancel();
      speaking = false;
    }
  }

  if (window.speechSynthesis) {
    speechSynthesis.onvoiceschanged = () => pickFrenchVoice();
  }

  window.KTTTS = { read, stop };
})();
