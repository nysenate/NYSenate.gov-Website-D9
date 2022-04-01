/**
 * @file
 * Adjustments for Paragraphs widget.
 *
 * This adds the "click to edit" functionality for the Paragraph widget.
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.thunderTabledragTabindex = {
    attach: function (context) {
      $('.tabledrag-handle', context).attr('tabindex', -1);
    }
  };

})(jQuery, Drupal);
