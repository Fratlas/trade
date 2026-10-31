(function () {
  async function askLesson(topic, level) {
    const data = await window.KTApi.post("/ai/lesson", { topic, level });
    return data.lesson || data.warning || "Aucune reponse disponible.";
  }

  async function askQuestions(topic, level, count) {
    const data = await window.KTApi.post("/ai/questions", { topic, level, count });
    return data.questions || [];
  }

  window.KTAI = { askLesson, askQuestions };
})();
