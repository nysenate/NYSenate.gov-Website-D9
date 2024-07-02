/**
 * @file
 * Adds JS functionality to the Private Message form.
 */

(($, Drupal, drupalSettings, once) => {
  function submitKeyPress(e) {
    const key = e.key || e.keyCode.toString();
    const supportedKeys = drupalSettings.privateMessageSendKey.split(',');
    // Remove spaces in case people separated keys by "," or ", ".
    for (let i = 0; i < supportedKeys.length; i++) {
      supportedKeys[i] = supportedKeys[i].trim();
    }

    if (supportedKeys.indexOf(key) !== -1) {
      // If it is the send key, just remove that character from the textarea.
      $(this).val((index, value) => {
        return value.substr(0, value.length - 1);
      });

      if ($(this).val() !== '') {
        $(this)
          .parents('.private-message-add-form')
          .find('.form-actions .form-submit')
          .mousedown();
      }
    }
  }

  /**
   * Event handler for the submit button on the private message form.
   * @param {Object} context The context.
   */
  function submitButtonListener(context) {
    $(
      once(
        'private-message-form-submit-button-listener',
        '.private-message-add-form textarea',
        context,
      ),
    ).each(() => {
      $(this).keyup(submitKeyPress);
    });
  }

  Drupal.behaviors.privateMessageForm = {
    attach(context) {
      submitButtonListener(context);
    },
    detach(context) {
      // Remove event handlers when the submit button is removed from the page.
      $(context)
        .find('.private-message-add-form textarea')
        .unbind('keyup', submitKeyPress);
    },
  };
})(jQuery, Drupal, drupalSettings, once);
