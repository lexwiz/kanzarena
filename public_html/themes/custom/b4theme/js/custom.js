/**
 * @file
 * Global utilities.
 *
 */
(function($, Drupal) {

  'use strict';

  Drupal.behaviors.bootstrap_sass = {
    attach: function(context, settings) {

      // Custom code here

      $('.dropdown-menu a.dropdown-item',context).on('click', function(e) {
        if (!$(this).next().hasClass('show')) {
          $(this).parents('.dropdown-menu').first().find('.show').removeClass("show");
        }
        var $subMenu = $(this).next(".dropdown-menu");
        $subMenu.toggleClass('show');
        $(this).parents('li.nav-item.dropdown.show').on('hidden.bs.dropdown', function(e) {
          $('.dropdown-submenu .show').removeClass("show");
        });
        return false;
      });

    }
  };

  Drupal.behaviors.in_cart = {
    attach: function(context, settings) {

      // Custom code here
      $('form.in-cart input.form-submit').removeClass("btn-primary").addClass("btn-warning");

    }
  };

})(jQuery, Drupal);


