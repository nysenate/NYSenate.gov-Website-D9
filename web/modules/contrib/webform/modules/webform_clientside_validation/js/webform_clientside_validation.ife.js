/**
 * @file
 * Attaches behaviors for the Clientside Validation jQuery module.
 */

(function ($, drupalSettings) {

  'use strict';

  // Disable clientside validation for webforms submitted using Ajax.
  // This prevents Computed elements with Ajax from breaking.
  // @see \Drupal\clientside_validation_jquery\Form\ClientsideValidationjQuerySettingsForm
  drupalSettings.clientside_validation_jquery.validate_all_ajax_forms = 0;

  /**
   * Add .cv-validate-before-ajax to all webform submit buttons.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformClientSideValidationAjax = {
    attach: function (context) {
      $('form.webform-submission-form .form-actions :submit:not([formnovalidate])')
        .once('webform-clientside-validation-ajax')
        .addClass('cv-validate-before-ajax');
    }
  };

  /**
   * Fix date/time min, max, and step validation issues.
   *
   * @type {Drupal~behavior}
   *
   * @see https://github.com/jquery-validation/jquery-validation/pull/2119/commits
   */
  Drupal.behaviors.webformClientSideValidationDateTimeFix = {
    attach: function (context) {
      $(context).find(':input[type="date"], :input[type="time"], :input[type="datetime"]')
        .removeAttr('step')
        .removeAttr('min')
        .removeAttr('max');
    }
  };

  $(document).once('webform_cvjquery').on('cv-jquery-validate-options-update', function (event, options) {
    options.errorElement = 'strong';
    options.showErrors = function (errorMap, errorList) {
      // Show errors using defaultShowErrors().
      this.defaultShowErrors();

      // Add '.form-item--error-message' class to all errors.
      $(this.currentForm).find('strong.error').addClass('form-item--error-message');

      // Move all radios, checkboxes, and datelist errors to appear after
      // the parent container.
      $(this.currentForm).find('.form-checkboxes, .form-radios, .form-type-datelist .container-inline, .form-type-tel, .webform-type-webform-height .form--inline, .js-webform-tableselect').each(function () {
        var $container = $(this);
        var $errorMessages = $container.find('strong.error.form-item--error-message');
        $errorMessages.insertAfter($container);
      });

      // Move all select2 and chosen errors to appear after the parent container.
      $(this.currentForm).find('.webform-select2 ~ .select2, .webform-chosen ~ .chosen-container').each(function () {
        var $widget = $(this);
        var $select = $widget.parent().find('select');
        var $errorMessages = $widget.parent().find('strong.error.form-item--error-message');
        if ($select.hasClass('error')) {
          $errorMessages.insertAfter($widget);
          $widget.addClass('error');
        }
        else {
          $errorMessages.hide();
          $widget.removeClass('error');
        }
      });

      // Move checkbox errors to appear as the last item in the
      // parent container.
      $(this.currentForm).find('.form-type-checkbox').each(function () {
        var $container = $(this);
        var $errorMessages = $container.find('strong.error.form-item--error-message');
        $container.append($errorMessages);
      });

      // Move all likert errors to question <label>.
      $(this.currentForm).find('.webform-likert-table tbody tr').each(function () {
        var $row = $(this);
        var $errorMessages = $row.find('strong.error.form-item--error-message');
        $errorMessages.appendTo($row.find('td:first-child'));
      });

      // Move error after field suffix.
      $(this.currentForm).find('strong.error.form-item--error-message ~ .field-suffix').each(function () {
        var $fieldSuffix = $(this);
        var $errorMessages = $fieldSuffix.prev('strong.error.form-item--error-message');
        $errorMessages.insertAfter($fieldSuffix);
      });

      // Add custom clear error handling to checkboxes to remove the
      // error message, when any checkbox is checked.
      $(this.currentForm).find('.form-checkboxes').once('webform-clientside-validation-form-checkboxes').each(function () {
        var $container = $(this);
        $container.find('input:checkbox').click( function () {
          var state = $container.find('input:checkbox:checked').length ? 'hide' : 'show';
          var $message = $container.next('strong.error.form-item--error-message');
          $message[state]();

          // Ensure the message is set. This code addresses an expected bug
          // where the error message is emptied when it is toggled.
          var message = $container.find('[data-msg-required]').data('msg-required');
          $message.html(message);
        });
      });
    };
  });

})(jQuery, drupalSettings);
