/**
 * @file
 * Behaviors for the Senator List.
 */
/* eslint-disable max-len */
!((document, Drupal, $) => {
  'use strict';

  /**
   * Setup and attach the Senator List behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.senatorList = {
    attach: function(context) {
      // After a Views AJAX filter refresh, Drupal re-calls attach() with the
      // newly rendered view element as context.  Announce the update here so
      // the live region (which lives outside the replaced element) is stable.
      if (context !== document) {
        const $ctx = $(context);
        const isSenatorView = $ctx.hasClass('view-id-senator_and_committee_lists') ||
          $ctx.find('.view-id-senator_and_committee_lists').length > 0;
        if (isSenatorView) {
          Drupal.announce('Senator results updated.');
        }
        return;
      }

      // Initial page load: wire up filter-change announcements.
      const $committeeFilter = $('#edit-senator-committee-filter');
      const $partyFilter = $('#edit-field-party-value');

      $committeeFilter.add($partyFilter).on('change', function () {
        const $form = $(this).closest('form');
        Drupal.announce('Updating senator results.');
        $form.find('input[type=submit]').trigger('click');
      });
    },
  };
})(document, Drupal, jQuery);
