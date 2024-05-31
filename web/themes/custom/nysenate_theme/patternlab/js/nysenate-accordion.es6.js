/**
 * @file
 * Behaviors for the Filter Accordion.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Filter Accordion behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.nysenateAccordion = {
    attach: function attach(context) {
      var self = this;
      var $accordions = $('.nysenate-accordion', context);
      var $toggles = $('.nysenate-accordion__toggle', context);
      var $headings = $accordions.find('.nysenate-accordion__heading > span');
      $accordions.each(function () {
        var $accordion = $(this);
        var $heading = $accordion.find('.nysenate-accordion__heading');
        var $itemCount = $accordion.find('.nysenate-accordion__content', context).length;

        if ($accordion.find('.nysenate-accordion__content .c-bill--actions-table-col2 span', context).length) {
          $itemCount = $accordion.find('.nysenate-accordion__content .c-bill--actions-table-col2 span', context).length;
        }

        if (!$heading.hasClass('no-count')) {
          $("<span class=\"count\">(".concat($itemCount, ")</span>")).appendTo($heading);
        } // Attach click handler for accordion.


        var $toggle = $accordion.find('.nysenate-accordion__toggle', context);
        $toggle.on('click', function () {
          self.toggleClickEvent(self, $accordions, $headings, $toggles, $accordion, $heading, $(this));
        });
      });
    },
    toggleClickEvent: function toggleClickEvent(self, $accordions, $headings, $toggles, $accordion, $heading, $toggle) {
      // Identify the matching element.
      var $content = $accordion.find('#' + $toggle.attr('aria-controls')); // all matching elements

      var $contents = $accordions.find('#' + $toggle.attr('aria-controls'));

      if (!$accordion.hasClass('open')) {
        $toggles.attr('aria-expanded', 'false');
        $contents.attr('aria-hidden', 'true');
        self.changeAllTexts($headings);
        self.changeCurrentTexts($heading, 'open'); // Accordion does not have `.open`, so we are opening the accordion.

        $accordion.addClass('open'); // Toggle the `aria-expanded`.

        $toggle.attr('aria-expanded', 'true'); // Toggle the `aria-hidden` attribute on the content.

        $content.attr('aria-hidden', 'false');
      } else {
        // Same as the if, but in reverse.
        self.changeCurrentTexts($heading, 'close');
        $accordion.removeClass('open');
        $toggle.attr('aria-expanded', 'false');
        $content.attr('aria-hidden', 'true');
      }
    },
    changeAllTexts: function changeAllTexts($headings) {
      var textsToReplace = $headings.html();
      var closeAccordionTexts = textsToReplace.replace('Hide', 'View');
      $headings.text(closeAccordionTexts);
      return false;
    },
    changeCurrentTexts: function changeCurrentTexts($heading, status) {
      var $headinIndicator = $heading.find('.indicator');
      var textToReplace = $headinIndicator.html();
      var openAccordionText = textToReplace.replace('View', 'Hide');
      var closeAccordionText = textToReplace.replace('Hide', 'View');

      if (status === 'open') {
        $headinIndicator.text(openAccordionText);
      } else {
        $headinIndicator.text(closeAccordionText);
      }

      return false;
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=nysenate-accordion.es6.js.map
