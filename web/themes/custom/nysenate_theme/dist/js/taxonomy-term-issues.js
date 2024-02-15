/**
 * @file
 * Behaviors for the Taxonomy Term Issues.
 */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Taxonomy Term Issues behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.dashboardProfile = {
    attach: function attach() {
      var modal = $('.user-login-modal');
      var modalToggle = $('.c-user-login');
      var closeBtn = $('.close');
      modalToggle.on('click', function () {
        modal.css('display', 'block');
      });
      closeBtn.on('click', function () {
        modal.css('display', 'none');
      });
      $(window).on('click', function (event) {
        if (event.target === modal[0]) {
          modal.css('display', 'none');
        }
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=taxonomy-term-issues.js.map
