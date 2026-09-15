"use strict";

document.querySelectorAll('.main-nav a[href^="#"]').forEach(function (link) {
  link.addEventListener("click", function () {
    document.querySelectorAll(".main-nav a").forEach(function (item) {
      item.removeAttribute("aria-current");
    });
    link.setAttribute("aria-current", "page");
  });
});

document.getElementById("year").textContent = new Date().getFullYear();
