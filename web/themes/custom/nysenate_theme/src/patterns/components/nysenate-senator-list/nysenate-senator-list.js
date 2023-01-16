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
    attach: function() {
      const $committeeFilter = $('#edit-senator-committee-filter');
      const $partyFilter = $('#edit-field-party-value');
      $(document).ready(function() {
        $committeeFilter.add($partyFilter).on('change', function() {
          var $form = $(this).closest('form');
          $form.find('input[type=submit]').click();
        });
      });
    },
  };
})(document, Drupal, jQuery);
