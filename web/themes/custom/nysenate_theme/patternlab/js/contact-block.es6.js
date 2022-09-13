/**
 * @file
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Carousel behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.contactBlock = {
    attach: function attach() {
      $('.contact-form__title').slice(1).hide();
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=contact-block.es6.js.map
