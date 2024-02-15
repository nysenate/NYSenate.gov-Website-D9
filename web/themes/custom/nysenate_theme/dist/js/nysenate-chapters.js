/**
 * @file
 * Behaviors for the Chapters.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Chapters behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.chapters = {
    attach: function attach() {
      var chapterBtn = $('.node-chapter .node-title');
      var collapseClass = 'c-chapter__collapsed';
      var openedText = 'Collapse Section';
      var closedText = 'Read More';
      chapterBtn.on('click', function () {
        var parent = $(this).parent('.node-chapter');
        var ctaText = $(this).find('.c-chapter-cta');

        if (parent.hasClass(collapseClass)) {
          parent.removeClass(collapseClass);
          ctaText.text(openedText);
        } else {
          parent.addClass(collapseClass);
          ctaText.text(closedText);
        }
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=nysenate-chapters.js.map
