document.addEventListener("DOMContentLoaded", function () {
  var menus = document.querySelectorAll(".emonks-user-menu-shortcode");
  menus.forEach(function (menu) {
    var trigger = menu.querySelector(".emonks-user-menu-trigger");
    var dropdown = menu.querySelector(".emonks-user-menu-dropdown");
    if (!trigger || !dropdown) {
      return;
    }

    function closeMenu() {
      trigger.setAttribute("aria-expanded", "false");
      dropdown.hidden = true;
    }

    function openMenu() {
      trigger.setAttribute("aria-expanded", "true");
      dropdown.hidden = false;
    }

    trigger.addEventListener("click", function () {
      if (dropdown.hidden) {
        openMenu();
      } else {
        closeMenu();
      }
    });

    document.addEventListener("click", function (event) {
      if (!menu.contains(event.target)) {
        closeMenu();
      }
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeMenu();
      }
    });
  });
});

