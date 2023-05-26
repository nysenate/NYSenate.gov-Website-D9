/**
 * @file
 * Behaviors for the Page not found.
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
    'use strict';
  
    /**
     * Setup and attach the Page Not Found behaviors.
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.pagenotfound = {
      attach: function(context) {
        if (context !== document) {
          return;
        }

        const path404 = $(location).attr('pathname');
        const elemPath404 = $('#path404');

        $(function() {
          elemPath404.html(path404);
        });
      }
    };
  })(document, Drupal, jQuery);
  