/**
 * @file
 * Behaviors for the Committees List.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Committees List behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.committeesList = {
    attach: function attach() {
      var commList = $('.comm-widget-wrapper');
      commList.each(function () {
        var unflagBtn = $(this).find('.flag.unflag-action');
        var flagBtn = $(this).find('.flag.flag-action');
        var flagMsg = $(this).find('.flag-message');
        var closeMsg = $(this).find('.flag-message .close-message');
        flagMsg.css('display', 'none');
        unflagBtn.html('<span class="close-message">X</span> Unfollow');
        unflagBtn.attr('href', unflagBtn.data('unfollow-link'));
        flagBtn.html('Follow this committee');
        flagBtn.attr('href', flagBtn.data('follow-link'));
        unflagBtn.add(flagBtn).each(function () {
          $(this).on('click', function () {
            $(this).parent().find('.flag-message').fadeIn();
          });
        });

        if (closeMsg.length) {
          closeMsg.on('click', function () {
            $(this).parent().remove();
          });
        }
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=nysenate-committee-widget.js.map
