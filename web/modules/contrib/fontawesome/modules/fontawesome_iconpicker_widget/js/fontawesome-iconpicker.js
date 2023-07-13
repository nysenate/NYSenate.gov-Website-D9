(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.iconPicker = {
    attach: function (context, settings) {
      var $context = $(context);
      // Get icons list.
      var $icons = drupalSettings.fontawesomeIcons.icons;
      var $terms = drupalSettings.fontawesomeIcons.terms;
      const $iconPickerIcon = $(once('iconPickerIcon', 'input.fontawesome-iconpicker-icon', context));
      $iconPickerIcon.each(function (index, element) {
        var $element = $(element);
        if ($icons != 'undefined') {
          $element.fontIconPicker({
            source: $icons,
            searchSource: $terms,
          });
        }
      });
      // Mask.
      const $iconPickerMask = $(once('iconPickerMask', 'input.fontawesome-iconpicker-mask', context));
      $iconPickerMask.each(function (index, element) {
        var $element = $(element);
        if ($icons != 'undefined') {
          $element.fontIconPicker({
            source: $icons,
            searchSourc: $terms,
          });
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings, once);
