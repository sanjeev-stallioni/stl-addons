/* Stl Addons — Product Buy quantity stepper.
 * Wires the − / + buttons to the quantity input, clamped to its min/max/step.
 */
(function () {
  "use strict";

  function ready(fn) {
    if (document.readyState !== "loading") {
      fn();
    } else {
      document.addEventListener("DOMContentLoaded", fn);
    }
  }

  function step(input, dir) {
    var inc = parseFloat(input.getAttribute("step")) || 1;
    var min = parseFloat(input.getAttribute("min"));
    var max = parseFloat(input.getAttribute("max"));
    if (isNaN(min)) {
      min = 1;
    }
    var cur = parseFloat(input.value) || 0;
    var next = cur + dir * inc;

    if (next < min) {
      next = min;
    }
    if (!isNaN(max) && max > 0 && next > max) {
      next = max;
    }
    if (next !== cur) {
      input.value = next;
      input.dispatchEvent(new Event("change", { bubbles: true }));
    }
  }

  function bind(root) {
    (root || document).querySelectorAll(".stl-buy-qty").forEach(function (box) {
      if (box.dataset.stlBuy) {
        return;
      }
      box.dataset.stlBuy = "1";

      var input = box.querySelector(".stl-buy-input");
      var minus = box.querySelector(".stl-buy-minus");
      var plus = box.querySelector(".stl-buy-plus");
      if (!input) {
        return;
      }

      if (minus) {
        minus.addEventListener("click", function () {
          step(input, -1);
        });
      }
      if (plus) {
        plus.addEventListener("click", function () {
          step(input, 1);
        });
      }
    });
  }

  ready(function () {
    bind(document);
  });

  // Re-init inside the Elementor editor preview and on AJAX-injected content.
  if (window.jQuery) {
    window.jQuery(window).on("elementor/frontend/init", function () {
      if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction(
          "frontend/element_ready/stl_product_buy.default",
          function ($scope) {
            bind($scope && $scope[0] ? $scope[0] : document);
          },
        );
      }
    });
  }
})();
