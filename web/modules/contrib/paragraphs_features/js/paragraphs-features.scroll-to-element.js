/**
 * @file
 * Provides an AJAX command for scrolling to an element.
 */

(function (Drupal) {

  'use strict';

  /**
   * Command to scroll to an paragraph item.
   *
   * @param {Drupal.Ajax} ajax
   *   The Drupal Ajax object.
   * @param {object} response
   *   Object holding the server response.
   * @param {number} [status]
   *   The HTTP status code.
   */
  Drupal.AjaxCommands.prototype.scrollToElement = function (ajax, response, status) {
    var resizeObserver = new ResizeObserver(function () {
      const elem = document.querySelector('[data-drupal-selector=' + response.drupalElementSelector + ']');
      if (elem) {elem.scrollIntoView({block: 'center'});}
    });

    var parent = document.querySelector('[data-drupal-selector=' + response.drupalParentSelector + ']');
    resizeObserver.observe(parent);

    setTimeout(function () {
      resizeObserver.unobserve(parent);
    }, 500);
  };
}(Drupal));
