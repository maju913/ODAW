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

/* ---------- Resultado do quiz ---------- */

// Descrição exibida para cada novela possível
var NOVELAS = {
  "Vale Tudo": "Ambição, dinheiro e caráter em jogo: você não teme tomar decisões duras para chegar aonde quer.",
  "Avenida Brasil": "Vingança bem planejada e reviravoltas em cada capítulo: você tem sangue frio e memória longa.",
  "Senhora do Destino": "Superação, família e um coração enorme: você acredita que a justiça vem para quem luta.",
  "A Favorita": "Ninguém sabe quem é a mocinha e quem é a vilã, e você gosta assim: seu lado misterioso é o seu charme."
};

var imagens = {
  "Vale Tudo": "../ex8/imagens/valetudo_todas.jpg",
  "Avenida Brasil": "../ex8/imagens/carminha2.jpg",
  "Senhora do Destino": "../ex8/imagens/nazare.jpeg",
  "A Favorita": "../ex8/imagens/a_favorita.jpg"
};

// Cada resposta das perguntas 1 a 3 vale 1 ponto para uma novela
var PONTOS = {
  p1: {
    "Maria de Fátima": "Vale Tudo",
    "Carminha": "Avenida Brasil",
    "Protagonista": "Senhora do Destino",
    "Bibi Perigosa": "A Favorita"
  },
  p2: {
    "Tieta": "Vale Tudo",
    "Donatela": "A Favorita",
    "Maria do Carmo": "Senhora do Destino",
    "Nina": "Avenida Brasil"
  },
  p3: {
    "Carminha": "Avenida Brasil",
    "Nazaré": "Senhora do Destino",
    "Odete": "Vale Tudo",
    "Flora": "A Favorita"
  }
};

// Pergunta 5 (0 = mocinho, 100 = vilão): a faixa do controle vale 1 ponto
function novelaPorIdentidade(valor) {
  if (valor <= 25) { return "Senhora do Destino"; }
  if (valor <= 60) { return "A Favorita"; }
  if (valor <= 85) { return "Vale Tudo"; }
  return "Avenida Brasil";
}

function calcularResultado(dados) {
  var pontos = {};

  function somar(novela) {
    if (novela) {
      pontos[novela] = (pontos[novela] || 0) + 1;
    }
  }

  ["p1", "p2", "p3"].forEach(function (pergunta) {
    somar(PONTOS[pergunta][dados.get(pergunta)]);
  });
  somar(novelaPorIdentidade(Number(dados.get("identidade"))));

  // Em caso de empate, vence a novela que pontuou primeiro (a da pergunta 1)
  var vencedora = null;
  Object.keys(pontos).forEach(function (novela) {
    if (vencedora === null || pontos[novela] > pontos[vencedora]) {
      vencedora = novela;
    }
  });
  return vencedora;
}

form.addEventListener("submit", function (event) {
  event.preventDefault();
  var firstName = nameInput.value.trim().split(/\s+/)[0] || "visitante";
  var novela = calcularResultado(new FormData(form));

  document.getElementById("resultado-texto").textContent =
    firstName + ", a novela que mais combina com você é:";
  document.getElementById("resultado-novela").textContent = novela;
  document.getElementById("resultado-descricao").textContent = NOVELAS[novela];
  document.getElementById("resultado-imagem").src = imagens[novela];
  resultCard.hidden = false;
  resultCard.scrollIntoView({ behavior: "smooth", block: "center" });
});

document.getElementById("reiniciar").addEventListener("click", function () {
  form.reset();
  identityOutput.value = "50";
  resultCard.hidden = true;
  goToStep(0);
});