/**
 * @file
 * ckeditor sticky toolbar.
 */
(function ($, CKEDITOR) {

  'use strict';

  /**
   * Make ckeditor toolbar sticky.
   */
  Drupal.behaviors.ckeditorStickyToolbar = {
    attach: function (context) {
      // Apply on ckeditor init.
      CKEDITOR.once('instanceReady', function (event) {
        // Set initially.
        setPosition();
        // Add handler for maximize event.
        event.editor.on('maximize', setPosition);
        // Add handler for toolbar events.
        $(context).once('ckeditorStickyToolbar').on('drupalToolbarOrientationChange drupalToolbarTabChange drupalToolbarTrayChange', setPosition);
      });

      // Fix ckeditor sticky toolbar position.
      function setPosition() {
        var toolBar = $('.cke_top', context);
        toolBar.once('ckeditorStickyToolbarPosition').attr('style', toolBar.attr('style') + 'position: sticky; position: -webkit-sticky;');
        toolBar.css('top', toolBar.parents('.ui-dialog').length ? 0 : $('body').css('padding-top'));
      }
    }
  };
})(jQuery, CKEDITOR);
