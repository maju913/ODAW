"use strict";

document.querySelectorAll(".star-rating").forEach(function (rating) {
  var buttons = Array.from(rating.querySelectorAll(".star"));
  var storageKey = "avaliacao-" + rating.dataset.opening;

  function paint(value) {
    buttons.forEach(function (button) {
      var selected = Number(button.dataset.value) <= value;
      button.classList.toggle("selected", selected);
      button.setAttribute("aria-pressed", String(selected));
    });
  }

  buttons.forEach(function (button) {
    button.addEventListener("click", function () {
      var value = Number(button.dataset.value);
      paint(value);
      try {
        localStorage.setItem(storageKey, String(value));
      } catch (error) {
        // A avaliação continua funcionando mesmo se o navegador bloquear o armazenamento.
      }
    });
  });

  var savedValue = 0;
  try {
    savedValue = Number(localStorage.getItem(storageKey)) || 0;
  } catch (error) {
    savedValue = 0;
  }
  paint(savedValue);
});

var year = document.getElementById("year");
if (year) {
  year.textContent = new Date().getFullYear();
}
