/**
 * @file
 * Behaviors for the Nested Accordion.
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
  'use strict';

  /**
   * Setup and attach the Nested Accordion behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.nysenateAccordionNested = {
    attach: function (context) {
      const self = this;
      $('.nysenate-accordion-nested__toggle', context).click(function () {
        const $this = $(this);

        if ($this.next().hasClass('show')) {
          $this.next().removeClass('show');
          $this.removeClass('show');
          self.changeText($this, true);
        }
        else {
          $this
            .parent()
            .parent()
            .find('li .nysenate-accordion-nested--inner')
            .removeClass('show');
          self.changeText(
            $this
              .parent()
              .parent()
              .find('li .nysenate-accordion-nested--inner')
              .prev(),
            true
          );
          $this
            .parent()
            .parent()
            .find('li .nysenate-accordion-nested--inner')
            .prev()
            .removeClass('show');
          $this.next().toggleClass('show');
          $this.toggleClass('show');
          self.changeText($this, false);
        }
      });
    },
    changeText: function ($heading, isOpen) {
      let $headingIndicator = $heading.find('.indicator');
      const textToReplace = $headingIndicator.html();
      const newText = isOpen
        ? textToReplace.replace('Hide', 'View')
        : textToReplace.replace('View', 'Hide');
      $headingIndicator.text(newText);
    }
  };
})(document, Drupal, jQuery);
