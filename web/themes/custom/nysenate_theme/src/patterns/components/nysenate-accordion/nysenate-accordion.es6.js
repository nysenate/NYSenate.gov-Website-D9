/**
 * @file
 * Behaviors for the Filter Accordion.
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
  'use strict';

  /**
   * Setup and attach the Filter Accordion behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.nysenateAccordion = {
    attach: function(context) {

      const self = this;
      const $accordions = $('.nysenate-accordion', context);
      const $toggles = $('.nysenate-accordion__toggle', context);
      const $headings = $accordions.find('.nysenate-accordion__heading > span');

      $accordions.each(function() {
        const $accordion = $(this);
        const $heading = $accordion.find('.nysenate-accordion__heading');
        let $itemCount = $accordion.find('.nysenate-accordion__content', context).length;
        if ($accordion.find('.nysenate-accordion__content .c-bill--actions-table-col2 span', context).length) {
          $itemCount = $accordion.find('.nysenate-accordion__content .c-bill--actions-table-col2 span', context).length;
        }

        if (!$heading.hasClass('no-count')) {
          $(`<span class="count">(${$itemCount})</span>`).appendTo($heading);
        }

        // Attach click handler for accordion.
        const $toggle = $accordion.find('.nysenate-accordion__toggle', context);
        $toggle.on('click', function() {
          self.toggleClickEvent(self, $accordions, $headings, $toggles, $accordion, $heading, $(this));
        });
      });
    },
    toggleClickEvent: function(self, $accordions, $headings, $toggles, $accordion, $heading, $toggle) {

      // Identify the matching element.
      const $content = $accordion.find('#' + $toggle.attr('aria-controls'));

      // all matching elements
      const $contents = $accordions.find('#' + $toggle.attr('aria-controls'));

      if (!$accordion.hasClass('open')) {
        $toggles.attr('aria-expanded', 'false');
        $contents.attr('aria-hidden', 'true');
        self.changeAllTexts($headings);

        self.changeCurrentTexts($heading, 'open');
        // Accordion does not have `.open`, so we are opening the accordion.
        $accordion.addClass('open');
        // Toggle the `aria-expanded`.
        $toggle.attr('aria-expanded', 'true');
        // Toggle the `aria-hidden` attribute on the content.
        $content.attr('aria-hidden', 'false');
      }
      else {
        // Same as the if, but in reverse.
        self.changeCurrentTexts($heading, 'close');
        $accordion.removeClass('open');
        $toggle.attr('aria-expanded', 'false');
        $content.attr('aria-hidden', 'true');
      }
    },
    changeAllTexts: function ($headings) {
      const textsToReplace = $headings.html();
      const closeAccordionTexts = textsToReplace.replace('Hide', 'View');

      $headings.text(closeAccordionTexts);

      return false;
    },
    changeCurrentTexts: function ($heading, status) {
      const $headinIndicator = $heading.find('.indicator');
      const textToReplace = $headinIndicator.html();
      const openAccordionText = textToReplace.replace('View', 'Hide');
      const closeAccordionText = textToReplace.replace('Hide', 'View');

      if (status === 'open') {
        $headinIndicator.text(openAccordionText);
      }
      else {
        $headinIndicator.text(closeAccordionText);
      }

      return false;
    }
  };
})(document, Drupal, jQuery);
