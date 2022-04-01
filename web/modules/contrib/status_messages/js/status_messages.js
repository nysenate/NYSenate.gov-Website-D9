/**
 * @file
 * JS file for status messages block.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.statusMessages = {
    attach: function (context, settings) {
      var time = settings.statusMessages;
      $(document).ready(function () {
        // Close status message after some seconds.
        if (time === null) {
          time = '5000';
        }
        setTimeout(function () {
          $('.simple-status-messages').fadeOut('slow');
        }, time);

        // When a close button is clicked hide this message.
        $('.simple-status-messages .status-messages .status-message-close').click(function () {
          $(this).parent().fadeOut('slow');
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
