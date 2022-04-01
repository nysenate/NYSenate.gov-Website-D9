/**
 * Views data export auto download.
 *
 * Automatically downloads file if downloadEnabled is true.
 */
(function ($, Drupal) {
  Drupal.behaviors.views_data_export_auto_download = {
    attach: function () {
      $('#vde-automatic-download').once().each(
        function () {
          this.focus();
          if (this.dataset.downloadEnabled === 'true') {
            location.href = this.href;
          }
        }
      )
    }
  };
})(jQuery, Drupal);
