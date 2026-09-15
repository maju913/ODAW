"use strict";

var form = document.getElementById("quiz-form");
var steps = Array.from(document.querySelectorAll(".quiz-step"));
var progressLinks = Array.from(document.querySelectorAll(".progress-nav a"));
var nameInput = document.getElementById("nome");
var identityInput = document.getElementById("identidade");
var identityOutput = document.getElementById("identidade-output");
var resultCard = document.getElementById("resultado");

function goToStep(index) {
  if (!steps[index]) {
    return;
  }

  window.setTimeout(function () {
    steps[index].scrollIntoView({ behavior: "smooth", block: "start" });
    history.replaceState(null, "", "#" + steps[index].id);
  }, 220);
}

document.querySelectorAll('input[type="radio"]').forEach(function (radio) {
  radio.addEventListener("change", function () {
    var currentStep = radio.closest(".quiz-step");
    goToStep(steps.indexOf(currentStep) + 1);
  });
});

document.querySelectorAll("[data-auto-next]").forEach(function (field) {
  field.addEventListener("change", function () {
    var currentStep = field.closest(".quiz-step");
    goToStep(steps.indexOf(currentStep) + 1);
  });
});

document.querySelectorAll("[data-next]").forEach(function (button) {
  button.addEventListener("click", function (event) {
    event.preventDefault();
    var currentStep = button.closest(".quiz-step");

    if (button.dataset.validate === "name" && !nameInput.value.trim()) {
      document.getElementById("nome-erro").textContent = "Digite seu nome para continuar.";
      nameInput.focus();
      return;
    }

    document.getElementById("nome-erro").textContent = "";
    goToStep(steps.indexOf(currentStep) + 1);
  });
});

nameInput.addEventListener("keydown", function (event) {
  if (event.key === "Enter") {
    event.preventDefault();
    document.querySelector('[data-validate="name"]').click();
  }
});

identityInput.addEventListener("input", function () {
  identityOutput.value = identityInput.value;
});

var observer = new IntersectionObserver(function (entries) {
  entries.forEach(function (entry) {
    if (entry.isIntersecting) {
      var activeIndex = steps.indexOf(entry.target);
      progressLinks.forEach(function (link, index) {
        link.classList.toggle("active", index === activeIndex);
      });
    }
  });
}, { threshold: 0.55 });

steps.forEach(function (step) {
  observer.observe(step);
});

form.addEventListener("submit", function (event) {
  event.preventDefault();
  var firstName = nameInput.value.trim().split(/\s+/)[0] || "visitante";
  document.getElementById("resultado-texto").textContent =
    "Obrigada, " + firstName + "! Suas respostas foram registradas.";
  resultCard.hidden = false;
  resultCard.scrollIntoView({ behavior: "smooth", block: "center" });
});

document.getElementById("reiniciar").addEventListener("click", function () {
  form.reset();
  identityOutput.value = "50";
  resultCard.hidden = true;
  goToStep(0);
});
