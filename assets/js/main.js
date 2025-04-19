/**
 * Main Script for menu by user roles
 *
 * @package MenuByUserRoles
 */

jQuery(document).ready(function ($) {
  // Helper to initialize select2 only if not already initialized
  function initSelect2(context = document) {
    $(context)
      .find(".menu_by-user-roles-dropdown")
      .each(function () {
        if (!$(this).hasClass("select2-hidden-accessible")) {
          $(this).select2({ multiple: true });
        }
      });
  }

  // Initial load
  setTimeout(function () {
    initSelect2();
  }, 500);

  // When menu item is clicked or edited
  $("#menu-to-edit").on(
    "click",
    ".menu-item-edit-active, .item-edit",
    function () {
      const menuItem = $(this).closest(".menu-item");
      initSelect2(menuItem);
    }
  );
});
