/**
 * @file
 * Behaviors for the Hero.
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
  'use strict';
  /**
   * Setup and attach the Hero behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.dashboardInbox = {
    attach: function (context) {
      const toggleBtn = $('.message-list__td--issue__toggle', context).once('messageList');
      toggleBtn.each(function () {
        const actionBtns = $(this).parent().find('.message-list__td--issue__actions');
        $(this).click(function () {

          if (actionBtns.css('display') === 'flex') {
            actionBtns.css('display', 'none');
            $(this).removeClass('message-list__td--issue__toggle--expanded');
          }
          else {
            actionBtns.css('display', 'flex');
            $(this).addClass('message-list__td--issue__toggle--expanded');
          }
        });
      });
    }
  };
})(document, Drupal, jQuery);
