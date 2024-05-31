/**
 * @file
 * Defines jQuery to provide summary information inside vertical tabs.
 */

(function ($) {

  'use strict';

  /**
   * Provide summary information for vertical tabs.
   */
  Drupal.behaviors.scheduler_settings = {
    attach: function (context) {

      // Provide summary when editing a node.
      $('details#edit-scheduler-settings', context).drupalSetSummary(function (context) {
        var values = [];
        if ($('#edit-publish-on-0-value-date').val()) {
          values.push(Drupal.t('Scheduled for publishing'));
        }
        if ($('#edit-unpublish-on-0-value-date').val()) {
          values.push(Drupal.t('Scheduled for unpublishing'));
        }
        if (!values.length) {
          values.push(Drupal.t('Not scheduled'));
        }
        return values.join('<br/>');
      });

      // Provide summary during content type configuration.
      $('#edit-scheduler', context).drupalSetSummary(function (context) {
        var values = [];
        if ($('#edit-scheduler-publish-enable', context).is(':checked')) {
          values.push(Drupal.t('Publishing enabled'));
        }
        if ($('#edit-scheduler-unpublish-enable', context).is(':checked')) {
          values.push(Drupal.t('Unpublishing enabled'));
        }
        return values.join('<br/>');
      });
    }
  };

})(jQuery);
