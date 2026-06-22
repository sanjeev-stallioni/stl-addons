/* Stl Addons — Archive Products.
 * Auto-submit the "Sort by" dropdown on change (so no submit button is needed),
 * mirroring WooCommerce's native catalog-ordering behaviour.
 */
(function () {
  "use strict";

  document.addEventListener("change", function (e) {
    var el = e.target;
    if (el && el.matches && el.matches(".stl-ap-ordering select.orderby")) {
      var form = el.closest("form");
      if (form) {
        form.submit();
      }
    }
  });
})();
