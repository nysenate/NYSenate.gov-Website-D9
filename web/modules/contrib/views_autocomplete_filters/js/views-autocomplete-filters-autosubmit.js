/**
 * @file
 * Extends autocomplete with autosubmit on selection.
 */

(($, Drupal) => {
  const selectHandler = Drupal.autocomplete.options.select;
  /**
   * Inherits default autocompleteselect handler and auto submit.
   *
   * @param {jQuery.Event} event
   *   The event triggered.
   * @param {object} ui
   *   The jQuery UI settings object.
   *
   * @return {boolean}
   *   Returns false to indicate the event status.
   */
  function selectHandlerAutoSubmit(event, ui) {
    selectHandler(event, ui);
    const formElement = $('input.views-ac-autosubmit').parents('form:first');
    let submitElement = formElement.find('[data-bef-auto-submit-click]'); // Work together with better expose form
    if (submitElement.length <= 0) {
      submitElement = formElement.find('.form-submit:first');
    }
    if (submitElement.length > 0) {
      submitElement.click();
    }
    // Return false to tell jQuery UI that we've filled in the value already.
    return false;
  }
  Drupal.autocomplete.options.select = selectHandlerAutoSubmit;
})(jQuery, Drupal);
