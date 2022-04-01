/**
 * @file
 * JQuery to set default time for Scheduler DateTime Widget.
 */

(function ($, drupalSettings) {

  'use strict';

  /**
   * Provide default time if schedulerDefaultTime is set.
   *
   * schedulerDefaultTime is defined in scheduler_form_node_form_alter when the
   * user is allowed to enter just a date. The values need to be pre-filled here
   * to avoid the browser validation 'please fill in this field' pop-up error
   * which is produced before the date widget valueCallback() can set the value.
   * @see https://www.drupal.org/project/scheduler/issues/2913829
   */
  Drupal.behaviors.setSchedulerDefaultTime = {
    attach: function (context) {
      if (typeof drupalSettings.schedulerDefaultTime !== "undefined") {
        var operations = ["publish", "unpublish"];
        operations.forEach(function (value) {
          var element = $("input#edit-" + value + "-on-0-value-time", context);
          // Only set the time when there is no value and the field is required.
          if (element.val() === "" && element.prop("required")) {
            element.val(drupalSettings.schedulerDefaultTime);
          }
        });
      }

      // Also use this jQuery behaviors function to set any pre-existing time
      // values with seconds removed if those drupalSettings values exist. This
      // is required by some browsers to make the seconds hidden.
      if (typeof drupalSettings.schedulerHideSecondsPublishOn !== "undefined") {
        var element = $("input#edit-publish-on-0-value-time", context);
        element.val(drupalSettings.schedulerHideSecondsPublishOn);
      }
      if (typeof drupalSettings.schedulerHideSecondsUnpublishOn !== "undefined") {
        var element = $("input#edit-unpublish-on-0-value-time", context);
        element.val(drupalSettings.schedulerHideSecondsUnpublishOn);
      }

    }
  };
})(jQuery, drupalSettings);
