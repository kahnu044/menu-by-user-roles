/**
 * Main Script for menu by user roles
 *
 * @package MenuByUserRoles
 */

jQuery(document).ready(function ($) {
  // Namespace - MBUR
  const MBUR = {

    dropdownSelector: '.menu_by-user-roles-dropdown',
    
    // Helper to initialize select2 only if not already initialized
    initSelect2: function(context = document) {
      $(context)
        .find(this.dropdownSelector)
        .each(function () {
          const $dropdown = $(this);
          
          if (!$dropdown.hasClass("select2-hidden-accessible") && 
              $dropdown.hasClass("menu_by-user-roles-dropdown")) {
            try {
              $dropdown.select2({ 
                multiple: true,
                placeholder: "Select user roles...",
                dropdownParent: $dropdown.closest('.menu-item-settings')
              });
            } catch (error) {
              console.warn('MBUR: Failed to initialize Select2:', error);
            }
          }
        });
    },

    bindEvents: function() {

      $(document).on('click.mbur', '#menu-to-edit .menu-item-edit-active, #menu-to-edit .item-edit', function(e) {
        const menuItem = $(this).closest(".menu-item");
        
        // Small delay to ensure DOM is ready
        setTimeout(() => {
          MBUR.initSelect2(menuItem);
        }, 100);
      });

      // Handle new menu items being added
      $(document).on('menu-item-added.mbur', function(e, menuItem) {
        setTimeout(() => {
          MBUR.initSelect2(menuItem);
        }, 200);
      });
    },

    init: function() {
      // Initial Select2
      setTimeout(() => {
        this.initSelect2();
      }, 500);

      // Bind events
      this.bindEvents();
    }
  };

  // Initialize
  MBUR.init();
});
