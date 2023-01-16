/**
 * @file
 * Behaviors for the Chapters.
 */
/* eslint-disable max-len */
!((document, Drupal, $) => {
  'use strict';
  /**
   * Setup and attach the Chapters behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.chapters = {
    attach: function() {
      const chapterBtn = $('.node-chapter .node-title');
      const collapseClass = 'c-chapter__collapsed';
      const openedText = 'Collapse Section';
      const closedText = 'Read More';


      chapterBtn.on('click', function () {
        const parent = $(this).parent('.node-chapter');
        const ctaText = $(this).find('.c-chapter-cta');

        if(parent.hasClass(collapseClass)) {
          parent.removeClass(collapseClass);
          ctaText.text(openedText);
        }
        else{
          parent.addClass(collapseClass);
          ctaText.text(closedText);
        }
      });


    },
  };
})(document, Drupal, jQuery);
