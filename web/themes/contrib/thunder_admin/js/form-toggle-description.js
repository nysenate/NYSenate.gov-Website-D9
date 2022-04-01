/**
 * @file
 * Toggle visibility of form descriptions.
 *
 */
(function ($, Drupal) {

  'use strict';

  function init(i, toggle) {
    var $toggle = $(toggle);
    var $target = $toggle.closest('[data-form-description-container]');

    function toggleDescription(e) {
      e.preventDefault();
      $target.toggleClass('is-description-visible');
    }

    $toggle.on('click.toggle-description', toggleDescription);

    // Open description on form error
    if ($toggle.parents('.form-item--error').length) {
      $target.toggleClass('is-description-visible');
    }
  }

  /**
   * Initialise the tabs JS.
   */
  Drupal.behaviors.formToggleDescription = {
    attach: function (context) {
      var $toggles = $(context).find('[data-toggle-description]');
      if ($toggles.length) {
        $toggles.once('form-toggle-description').each(init);
      }
    }
  };

})(jQuery, Drupal);
