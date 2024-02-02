/**
 * @file
 * Behaviors for the Committees List.
 */
/* eslint-disable max-len */
!((document, Drupal, $) => {
  'use strict';
  /**
   * Setup and attach the Committees List behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.committeesList = {
    attach: function () {
      const commList = $('.comm-widget-wrapper');

      commList.each(function () {
        const unflagBtn = $(this).find('.flag.unflag-action');
        const flagBtn = $(this).find('.flag.flag-action');
        const flagMsg = $(this).find('.flag-message');
        const closeMsg = $(this).find('.flag-message .close-message');

        flagMsg.css('display', 'none');

        unflagBtn.html('<span class="close-message">X</span> Unfollow');
        unflagBtn.attr('href', unflagBtn.data('unfollow-link'));

        flagBtn.html('Follow this committee');
        flagBtn.attr('href', flagBtn.data('follow-link'));

        (unflagBtn).add(flagBtn).each(function () {
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
})(document, Drupal, jQuery);
