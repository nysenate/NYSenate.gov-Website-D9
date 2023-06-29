/**
 * @file
 * Behaviors for the Dashboard Profile.
 */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Dashboard Profile behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.dashboardProfile = {
    attach: function attach() {
      var modal = $('.msg-senator-modal');
      var modalToggle = $('.c-dash-msg-senator');
      var closeBtn = $('.close');
      modalToggle.on('click', function () {// Comment next line to prevent modal from displaying.
        // modal.css('display', 'block');
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
//# sourceMappingURL=dashboard-profile.js.map
