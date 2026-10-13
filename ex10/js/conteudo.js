"use strict";

var menuButton = document.querySelector(".menu-button");
var navigation = document.querySelector(".main-nav");
var backToTop = document.querySelector(".back-to-top");

if (menuButton && navigation) {
  menuButton.addEventListener("click", function () {
    var isOpen = navigation.classList.toggle("open");
    menuButton.setAttribute("aria-expanded", String(isOpen));
  });

  navigation.addEventListener("click", function () {
    navigation.classList.remove("open");
    menuButton.setAttribute("aria-expanded", "false");
  });
}

if (backToTop) {
  window.addEventListener("scroll", function () {
    backToTop.classList.toggle("visible", window.scrollY > 500);
  });

  backToTop.addEventListener("click", function () {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
}

var year = document.getElementById("year");
if (year) {
  year.textContent = new Date().getFullYear();
}
