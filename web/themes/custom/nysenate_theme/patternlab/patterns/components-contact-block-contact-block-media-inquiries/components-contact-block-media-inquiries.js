/**
 * @file
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
  'use strict';

  /**
   * Setup and attach the Carousel behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.contactBlock = {
    attach: function() {
      $('.contact-form__title').slice(1).hide();
    }
  };
})(document, Drupal, jQuery);
