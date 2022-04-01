(($, Drupal, once) => {

  'use strict';

  Drupal.behaviors.paragraphsSorting = {
    attach: function (context, settings) {
      // Support for experimental paragraphs widget and also classic paragraphs widget with patch (deprecated).
      $(context)
        .find('.field-multiple-table--paragraphs--deprecated, .field-multiple-table--paragraphs-experimental--add-in-between')
        .once('init-paragraphs-sorting')
        .each(function () {
          var $table = $(this);
          $table.on('tabledrag-checkbox-start', function (e) {
            $table.find('.add-in-between-row').remove();
            var $rows = $table.find('> tbody > tr.paragraphs-features__add-in-between__row');
            $rows.remove();
          });

          $table.on('tabledrag-checkbox-end', function (e) {
            $table.data('jquery-once-paragraphs-features-add-in-between-init', false);
            once.remove('paragraphs-features-add-in-between-init', $table[0]);
            Drupal.behaviors.paragraphsFeaturesAddInBetweenInit.attach(context, settings);
          });
        });
    }
  };

})(jQuery, Drupal, once);
