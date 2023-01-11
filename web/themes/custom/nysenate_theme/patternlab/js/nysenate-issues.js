/**
 * @file
 * Behaviors for the Issues List.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Issues List behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.issuesList = {
    attach: function attach() {
      var issuesList = $('.c-block--issue');
      issuesList.each(function () {
        var unflagBtn = $(this).find('.flag.unflag-action');
        var flagBtn = $(this).find('.flag.flag-action');
        var flagMsg = $(this).find('.flag-message');
        var closeMsg = $(this).find('.flag-message .close-message');
        flagMsg.css('display', 'none');
        unflagBtn.html('Unfollow This Issue');
        unflagBtn.attr('href', unflagBtn.data('unfollow-link'));
        flagBtn.html('Follow This Issue');
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
//# sourceMappingURL=nysenate-issues.js.map
