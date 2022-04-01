(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.iconPicker = {
    attach: function (context, settings) {
      var $context = $(context);
      // Get icons list.
      var $icons = drupalSettings.fontawesomeIcons.icons;
      var $terms = drupalSettings.fontawesomeIcons.terms;
      $context.find('input.fontawesome-iconpicker-icon').once('iconPickerIcon').each(function(index, element) {
        var $element = $(element);
        if ($icons != 'undefined') {
          $element.fontIconPicker({ 
            source: $icons, 
            searchSource: $terms 
          });
        }
      });
      // Mask.
      $context.find('input.fontawesome-iconpicker-mask').once('iconPickerMask').each(function(index, element) {
        var $element = $(element);
        if ($icons != 'undefined') {
          $element.fontIconPicker({
            source: $icons,
            searchSourc: $terms
          });
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
