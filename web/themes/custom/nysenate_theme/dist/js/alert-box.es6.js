/**
 * @file
 * Attach behaviors for the alert.
 */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the alert.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.alertBox = {
    attach: function attach(context) {
      var $alertWrap = $('.alert-box', context);

      if ($alertWrap.length) {
        var $alertClose = $alertWrap.find('.close'); // When the close button is clicked on the alert,
        // fade out the alert

        $alertClose.on('click', function () {
          $(this).closest('.alert-box').fadeOut('fast');
        });
      }
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=alert-box.es6.js.map
