/* Stl Addons — Product Tabs.
 * Top-level tab switching. (Any FAQ accordion inside a pane uses its own
 * markup/JS — e.g. native <details> or the FAQ plugin's script.)
 */
(function () {
  "use strict";

  function initTabs(root) {
    (root || document).querySelectorAll(".stl-pt").forEach(function (tabs) {
      if (tabs.dataset.stlPt) {
        return;
      }
      tabs.dataset.stlPt = "1";

      var btns = tabs.querySelectorAll(".stl-pt-btn");
      var panes = tabs.querySelectorAll(".stl-pt-pane");

      function activate(btn) {
        var target = btn.getAttribute("data-tab");
        btns.forEach(function (b) {
          var on = b === btn;
          b.classList.toggle("is-on", on);
          b.setAttribute("aria-selected", on ? "true" : "false");
          // Roving tabindex: only the active tab is in the tab order.
          b.setAttribute("tabindex", on ? "0" : "-1");
        });
        panes.forEach(function (p) {
          p.classList.toggle("is-on", p.id === target);
        });
      }

      btns.forEach(function (btn) {
        btn.addEventListener("click", function () {
          activate(btn);
        });

        // Arrow-key navigation between tabs (WAI-ARIA tablist pattern).
        btn.addEventListener("keydown", function (e) {
          if (e.key !== "ArrowRight" && e.key !== "ArrowLeft") {
            return;
          }
          e.preventDefault();
          var list = Array.prototype.slice.call(btns);
          var idx = list.indexOf(btn);
          var next =
            e.key === "ArrowRight"
              ? (idx + 1) % list.length
              : (idx - 1 + list.length) % list.length;
          activate(list[next]);
          list[next].focus();
        });
      });
    });
  }

  function ready(fn) {
    if (document.readyState !== "loading") {
      fn();
    } else {
      document.addEventListener("DOMContentLoaded", fn);
    }
  }

  ready(function () {
    initTabs(document);
  });

  // Re-init inside the Elementor editor preview.
  if (window.jQuery) {
    window.jQuery(window).on("elementor/frontend/init", function () {
      if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction(
          "frontend/element_ready/stl_product_tabs.default",
          function ($scope) {
            initTabs($scope && $scope[0] ? $scope[0] : document);
          },
        );
      }
    });
  }
})();
